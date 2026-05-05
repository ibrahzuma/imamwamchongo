/**
 * PharmaCare Plus — realtime client.
 *
 * Connects to the WebSocket daemon using window.WS_URL (set by footer.php).
 * Reconnects with exponential backoff. Every inbound message is dispatched
 * twice on `document`:
 *
 *     rt:any                         (always)
 *     rt:<event>   e.g. rt:sale.created
 *
 * Hooks any element on the page can use without writing JS:
 *
 *   <table data-rt-refresh="sale.created,purchase.created">
 *     ... auto-reloads the page when one of those events arrives.
 *
 *   <span data-rt-counter="sale.created">0</span>
 *     ... increments on each matching event.
 *
 * Plus a small toast pops up in the corner so you can see the live signal.
 */
(function () {
    if (!window.WS_URL) return; // Not logged in or daemon URL not set.

    let ws        = null;
    let backoff   = 1000;
    let reconTmr  = null;

    function connect() {
        try {
            ws = new WebSocket(window.WS_URL);
        } catch (e) {
            scheduleReconnect();
            return;
        }
        ws.onopen = () => {
            backoff = 1000;
            window.PharmaWS = ws;
            console.log('[rt] connected');
        };
        ws.onmessage = (e) => {
            let m;
            try { m = JSON.parse(e.data); } catch (_) { return; }
            if (!m || !m.event) return;
            document.dispatchEvent(new CustomEvent('rt:' + m.event, { detail: m.data }));
            document.dispatchEvent(new CustomEvent('rt:any',          { detail: m }));
        };
        ws.onerror = () => { try { ws.close(); } catch (_) {} };
        ws.onclose = (e) => {
            console.warn('[rt] disconnected', e.code, e.reason || '');
            window.PharmaWS = null;
            scheduleReconnect();
        };
    }

    function scheduleReconnect() {
        if (reconTmr) return;
        reconTmr = setTimeout(() => {
            reconTmr = null;
            backoff  = Math.min(backoff * 2, 30000);
            connect();
        }, backoff);
    }

    /* -------- UI hooks -------- */

    const TOAST_LABELS = {
        'sale.created':     d => `New sale: ${d.invoice}  ·  ${d.total}`,
        'purchase.created': d => `New purchase: ${d.reference}`,
        'stock.adjusted':   d => `Stock adjustment: ${d.medicine} (${d.delta})`,
        'low.stock':        d => `Low stock alert: ${d.medicine} (${d.quantity} left)`,
    };

    document.addEventListener('rt:any', (e) => {
        const m = e.detail;
        if (m.event === 'hello' || m.event === 'error') return;
        const label = TOAST_LABELS[m.event];
        if (label) showToast(label(m.data || {}), iconFor(m.event));

        // Update counters
        document.querySelectorAll('[data-rt-counter]').forEach((el) => {
            const events = el.dataset.rtCounter.split(',').map(s => s.trim());
            if (events.includes(m.event) || events.includes('*')) {
                el.textContent = (parseInt(el.textContent, 10) || 0) + 1;
            }
        });

        // Auto-refresh tables / pages
        document.querySelectorAll('[data-rt-refresh]').forEach((el) => {
            const events = el.dataset.rtRefresh.split(',').map(s => s.trim());
            if (events.includes(m.event) || events.includes('*')) {
                // Brief delay so the server-side commit settles + lets the
                // toast be visible before the page rerenders.
                clearTimeout(window.__rtRefreshTimer);
                window.__rtRefreshTimer = setTimeout(() => location.reload(), 800);
            }
        });
    });

    function iconFor(event) {
        if (event === 'sale.created')     return 'bi-cart-check';
        if (event === 'purchase.created') return 'bi-truck';
        if (event === 'stock.adjusted')   return 'bi-arrow-left-right';
        if (event === 'low.stock')        return 'bi-exclamation-triangle';
        return 'bi-broadcast';
    }

    function showToast(text, icon) {
        let host = document.getElementById('rt-toast-host');
        if (!host) {
            host = document.createElement('div');
            host.id = 'rt-toast-host';
            host.style.cssText =
                'position:fixed;top:1rem;right:1rem;z-index:2000;' +
                'display:flex;flex-direction:column;gap:.5rem;max-width:340px;';
            document.body.appendChild(host);
        }
        const el = document.createElement('div');
        el.className = 'alert alert-info shadow-sm py-2 px-3 mb-0';
        el.style.cssText = 'min-width:240px;animation:rtFadeIn .25s ease-out;';
        el.innerHTML = '<i class="bi ' + (icon || 'bi-broadcast') + '"></i> ' + escapeHtml(text);
        host.appendChild(el);
        setTimeout(() => {
            el.style.transition = 'opacity .35s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 400);
        }, 4500);
    }

    function escapeHtml(s) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
        return String(s).replace(/[&<>"']/g, c => map[c]);
    }

    // Inject a tiny keyframe for the toast animation.
    const style = document.createElement('style');
    style.textContent = '@keyframes rtFadeIn { from { opacity:0; transform: translateY(-6px); } to { opacity:1; transform: none; } }';
    document.head.appendChild(style);

    connect();
})();
