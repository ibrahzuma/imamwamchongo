# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

**PharmaCare Plus** — a multi-tenant Pharmacy Management System written in vanilla PHP (7.4+) with MySQL/MariaDB. No Composer, no framework, no build step. Bootstrap 5 + jQuery + Chart.js are loaded via CDN. The primary deployment target is XAMPP/WAMP/LAMP.

## Commands

```bash
# Reset / seed the database (drops and recreates pharmacy_db)
mysql -u root -p pharmacy_db < database/pharmacy_db.sql
# or, on a fresh install:
mysql -u root -p -e "CREATE DATABASE pharmacy_db;" && mysql -u root -p pharmacy_db < database/pharmacy_db.sql

# Run the dev server (no build step — just serve)
php -S localhost:8000

# Lint a single PHP file
php -l path/to/file.php

# Lint everything that was changed
for f in config/*.php models/*.php controllers/*.php views/**/*.php api/*.php index.php; do php -l "$f" 2>&1 | grep -v "No syntax errors"; done
```

DB credentials are hardcoded in `config/database.php` (host=localhost, user=root, no password). Edit there if your local MySQL differs.

There is no test suite, linter config, or CI. PHP's built-in lint (`php -l`) is the only static check.

### Optional: install TCPDF for PDF report exports

The Reports → Export PDF buttons need TCPDF. Two install options (the loader at `lib/tcpdf_loader.php` checks both):

```bash
# Option A — Composer (creates vendor/autoload.php)
composer require tecnickcom/tcpdf

# Option B — manual: download https://github.com/tecnickcom/TCPDF
# unzip into:  lib/tcpdf/   (so that lib/tcpdf/tcpdf.php exists)
```

If TCPDF isn't found, clicking Export PDF flashes an error and redirects back to Reports — the rest of the app keeps working.

### Realtime updates (WebSockets via Ratchet)

The app uses a long-running PHP daemon for realtime updates (`sale.created`, `purchase.created`, `stock.adjusted`, `low.stock`). Architecture:

- **`bin/ws-server.php`** — Ratchet daemon. Listens for browsers on `:8080` (WebSocket) and for the PHP web app on `:8081` (TCP, localhost-only). Each browser connection is bound to a single `pharmacy_id` decoded from a signed token; events broadcast to a pharmacy never reach other tenants.
- **`lib/RealtimeHub.php`** — PHP web app side. `signToken()` issues an HMAC token to the browser; `publish($pharmacy_id, $event, $data)` opens a 1-shot TCP connection to `:8081` with line-delimited JSON. Best-effort: if the daemon is offline, requests still succeed silently.
- **`assets/js/realtime.js`** — browser client. Auto-reconnects with exponential backoff. Dispatches `rt:<event>` and `rt:any` CustomEvents on `document`. Supports two no-JS hooks via data attributes:
  - `<div data-rt-refresh="sale.created,purchase.created" hidden>` → reloads the page when one of those events arrives.
  - `<span data-rt-counter="sale.created">0</span>` → increments on each matching event.

**Install + run:**

```bash
# 1. Install dependencies (Composer required — once)
composer install

# 2. Start the WS daemon in a SEPARATE console (keep it running)
#    Optionally set env vars; defaults shown.
set WS_SECRET=change-me-to-a-long-random-string
set WS_PORT=8080
set WS_TCP_PORT=8081
php bin/ws-server.php

# 3. Apache (XAMPP) keeps serving as usual on port 80.
#    Browsers will connect to ws://<host>:8080/ via window.WS_URL,
#    automatically inserted in views/layouts/footer.php for logged-in users.
```

If the daemon isn't running, the rest of the app keeps working — you just don't see live updates.

**Adding a new realtime event:**

1. From the controller after a successful mutation, call `RealtimeHub::publish(currentPharmacyId(), 'my.event', ['field' => $value])`.
2. To make a page auto-refresh on that event, add `<div data-rt-refresh="my.event" hidden></div>` near the top of the view. To show a toast, add a label in `TOAST_LABELS` inside `assets/js/realtime.js`.

The `WS_SECRET` env var must match between the daemon and the web app — it's the HMAC key for the token. If they drift, every browser will be rejected at connect time.

## Architecture

### Routing — single front controller

All HTTP traffic enters `index.php`, which dispatches via `?page=X&action=Y`:

- `$page` → controller class (whitelisted in `$routes`)
- `$action` → method (whitelisted in `$methodMap`)
- Special cases: `pos` always calls `SaleController::pos()`; login `POST` and `logout` short-circuit before the dispatch.

To add a new feature, you typically: (1) add an entry to `$routes` and any new actions to `$methodMap` in `index.php`, (2) create the controller in `controllers/`, (3) create the model in `models/`, (4) create views in `views/<page>/`.

The `api/` directory is a separate, parallel entry point with its own auth.

### Multi-tenancy — pharmacy is the tenant

This is the most important architectural fact in the codebase:

- The `pharmacies` table is the tenant root. Every business table (`branches`, `users`, `categories`, `suppliers`, `medicines`, `stock_movements`, `sales`, `purchases`, `settings`) carries a `pharmacy_id` FK. `sale_items` and `purchase_items` inherit through their parent — they have no direct column.
- **Models** take `(db, pharmacyId)` in the constructor. Every model has a private `tenantClause($alias)` helper that emits ` AND <alias>.pharmacy_id = N` and is **concatenated into the SQL** (not a parameter — the value is integer-cast in the constructor). Passing `pharmacyId = null` disables scoping; this is for superadmin contexts only.
- **Controllers** read the tenant from the session via `currentPharmacyId()` (defined in `config/config.php`) and pass it into the models. Inline SQL in controllers (e.g. `DashboardController`, `ReportController`) must add `AND pharmacy_id = ?` explicitly.
- **Auth** is per-pharmacy: `User::login()` joins `pharmacies` and rejects if the pharmacy is `is_active = 0`. Session stores `pharmacy_id` and `pharmacy_name`. Login helpers: `requireLogin()`, `requireRole([...])`, `requireSuperadmin()`, `isSuperadmin()`, `currentPharmacyId()`.
- **Roles**: `superadmin` (platform-wide, `pharmacy_id = NULL`) manages pharmacies via `PharmacyController` and lands on `/pharmacies` instead of the dashboard. `admin` / `pharmacist` / `cashier` are scoped to their own pharmacy. A pharmacy admin **cannot** create users in other pharmacies and **cannot** create superadmins — `UserController` enforces this.
- **Uniqueness changed**: `medicines.barcode`, `sales.invoice_number`, and `purchases.reference_number` are unique **per pharmacy**, not globally. Two pharmacies can carry the same product.

When adding a new business table or model, follow the same pattern: `pharmacy_id INT NOT NULL` + FK + index, constructor takes `(db, pharmacyId)`, queries use `tenantClause()`.

### Transactional safety — sales and purchases

Both `Sale::create` and `Purchase::create` run inside a `beginTransaction()`. The sale path uses `SELECT … FOR UPDATE` to lock medicine rows before decrementing, preventing oversells under concurrent POS terminals. Both paths additionally **verify tenant ownership** of every referenced medicine and supplier before mutating — this is the second line of defense against cross-tenant ID forgery (the first being the model's own `tenantClause`).

If you add another mutation that touches stock, mirror this pattern: lock + tenant-check + transactional commit/rollback.

### Security model

- **PDO prepared statements** everywhere — no string concatenation of user input into SQL. The single exception is the integer-cast `pharmacy_id` inside `tenantClause()`, which is safe because it's set in the model constructor from a trusted session value.
- **CSRF** — `csrfToken()` and `verifyCsrf()` in `config/config.php`. Every form must include `<input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">` and the controller must call `verifyCsrf()` before mutating.
- **XSS** — output is passed through `sanitize()` (in views) or `htmlspecialchars()`.
- **`.htaccess`** at the root blocks direct access to `*.sql`, `*.md`, `*.env`, `*.log` and disables directory listing. `config/.htaccess` and `models/.htaccess` block direct access to those directories.

### REST API — per-pharmacy

`api/index.php` is the API entry point. Authentication resolves to a single tenant:

- `X-API-Key: <key>` header (or `?api_key=` query) → looked up against `pharmacies.api_key`. The matched pharmacy becomes the scope.
- An active session cookie also works — uses the user's own `pharmacy_id`.

Every endpoint constructs its model with the resolved `$pharmacyId`, so all results are tenant-scoped automatically. There is **no global API key** — keys are per-pharmacy and rotatable from the superadmin UI (`?page=pharmacies&action=rotateKey`).

### Views layout

Every view starts with `$pageTitle = '...'; require __DIR__ . '/../layouts/header.php';` and ends with `require __DIR__ . '/../layouts/footer.php';`. The header renders the sidebar nav, which branches on role (`isSuperadmin()` → platform nav only; pharmacy users → dashboard/POS/inventory/etc). Forms generally live in shared `_form.php` files included by both `create.php` and `edit.php`.

## Demo accounts

All passwords are `password123`. The seed data (`database/pharmacy_db.sql`) creates:

- `superadmin` (platform-wide)
- `admin` / `pharmacist` / `cashier` — staff at *PharmaCare Plus*
- `medilink_admin` — admin at *MediLink Pharmacy*

Two pharmacies and a small medicine catalog per pharmacy are seeded so cross-tenant isolation can be verified by logging into both accounts in different browsers.
