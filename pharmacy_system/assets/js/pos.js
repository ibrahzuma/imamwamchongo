/**
 * POS JavaScript — handles cart, search, barcode, checkout (AJAX).
 */

(function () {
    'use strict';

    const cart = []; // each: {id, name, unit, price, qty, stock}

    const $search       = document.getElementById('medicineSearch');
    const $results      = document.getElementById('searchResults');
    const $barcode      = document.getElementById('barcodeInput');
    const $cartBody     = document.getElementById('cartBody');
    const $emptyRow     = document.getElementById('emptyRow');
    const $subtotal     = document.getElementById('subtotalDisplay');
    const $tax          = document.getElementById('taxDisplay');
    const $total        = document.getElementById('totalDisplay');
    const $change       = document.getElementById('changeDisplay');
    const $discount     = document.getElementById('discount');
    const $taxRate      = document.getElementById('taxRate');
    const $paid         = document.getElementById('paid');
    const $complete     = document.getElementById('completeSale');
    const $clear        = document.getElementById('clearCart');
    const $custName     = document.getElementById('customerName');
    const $custPhone    = document.getElementById('customerPhone');
    const $paymentMode  = document.getElementById('paymentMethod');
    const csrf          = document.getElementById('csrfToken').value;

    /* ---------- Money helpers ---------- */
    const money = n => `${window.CURRENCY} ${(parseFloat(n) || 0).toFixed(2)}`;

    /* ---------- Search (debounced) ---------- */
    let searchTimer = null;
    $search.addEventListener('input', function () {
        clearTimeout(searchTimer);
        const q = this.value.trim();
        if (q.length < 2) { $results.style.display = 'none'; return; }
        searchTimer = setTimeout(() => doSearch(q), 250);
    });

    function doSearch(q) {
        fetch(window.URLS.search + '&q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(items => {
                if (!items.length) {
                    $results.innerHTML = '<div class="p-2 text-muted small">No matches.</div>';
                } else {
                    $results.innerHTML = items.map(m => `
                        <div class="search-result-item" data-id="${m.id}" data-name="${escapeHtml(m.name)}"
                             data-price="${m.selling_price}" data-stock="${m.quantity}" data-unit="${escapeHtml(m.unit || '')}">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${escapeHtml(m.name)}</strong>
                                    <small class="text-muted d-block">${escapeHtml(m.generic_name || '')}</small>
                                </div>
                                <div class="text-end">
                                    <strong>${money(m.selling_price)}</strong>
                                    <small class="d-block text-muted">${m.quantity} ${escapeHtml(m.unit || '')} in stock</small>
                                </div>
                            </div>
                        </div>`).join('');
                }
                $results.style.display = 'block';
            })
            .catch(() => $results.style.display = 'none');
    }

    $results.addEventListener('click', function (e) {
        const item = e.target.closest('.search-result-item');
        if (!item) return;
        addToCart({
            id: parseInt(item.dataset.id),
            name: item.dataset.name,
            price: parseFloat(item.dataset.price),
            stock: parseInt(item.dataset.stock),
            unit: item.dataset.unit
        });
        $search.value = '';
        $results.style.display = 'none';
        $search.focus();
    });

    document.addEventListener('click', function (e) {
        if (!$search.contains(e.target) && !$results.contains(e.target)) {
            $results.style.display = 'none';
        }
    });

    /* ---------- Barcode ---------- */
    $barcode.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const code = this.value.trim();
            if (!code) return;
            fetch(window.URLS.barcode + '&barcode=' + encodeURIComponent(code))
                .then(r => {
                    if (!r.ok) throw new Error('Not found');
                    return r.json();
                })
                .then(m => {
                    addToCart({
                        id: m.id, name: m.name,
                        price: parseFloat(m.selling_price),
                        stock: parseInt(m.quantity), unit: m.unit || ''
                    });
                    this.value = '';
                })
                .catch(() => alert('Barcode not found.'));
        }
    });

    /* ---------- Cart ops ---------- */
    function addToCart(item) {
        if (item.stock <= 0) { alert('Out of stock!'); return; }
        const existing = cart.find(c => c.id === item.id);
        if (existing) {
            if (existing.qty + 1 > item.stock) { alert('Only ' + item.stock + ' available.'); return; }
            existing.qty++;
        } else {
            cart.push({ ...item, qty: 1 });
        }
        renderCart();
    }

    function renderCart() {
        if (cart.length === 0) {
            $cartBody.innerHTML = '<tr id="emptyRow"><td colspan="5" class="pos-cart-empty">Cart is empty. Search for medicines to add.</td></tr>';
        } else {
            $cartBody.innerHTML = cart.map((c, i) => `
                <tr>
                    <td>
                        <strong>${escapeHtml(c.name)}</strong>
                        <small class="d-block text-muted">${escapeHtml(c.unit)} · stock: ${c.stock}</small>
                    </td>
                    <td>
                        <input type="number" min="1" max="${c.stock}" value="${c.qty}"
                               class="form-control form-control-sm text-center qty-input" data-i="${i}">
                    </td>
                    <td class="text-end">${money(c.price)}</td>
                    <td class="text-end"><strong>${money(c.qty * c.price)}</strong></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-danger remove-btn" data-i="${i}"><i class="bi bi-x"></i></button>
                    </td>
                </tr>`).join('');
        }
        recalc();
    }

    $cartBody.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-btn');
        if (btn) {
            cart.splice(+btn.dataset.i, 1);
            renderCart();
        }
    });

    $cartBody.addEventListener('input', function (e) {
        const inp = e.target.closest('.qty-input');
        if (inp) {
            const i = +inp.dataset.i;
            let q = parseInt(inp.value) || 1;
            if (q < 1) q = 1;
            if (q > cart[i].stock) { q = cart[i].stock; inp.value = q; alert('Limit reached'); }
            cart[i].qty = q;
            recalc();
            // Update subtotal cell only (avoid full re-render to keep input focus)
            inp.closest('tr').querySelector('td:nth-child(4) strong').textContent = money(q * cart[i].price);
        }
    });

    /* ---------- Totals ---------- */
    function recalc() {
        const subtotal = cart.reduce((s, c) => s + c.qty * c.price, 0);
        const disc     = parseFloat($discount.value) || 0;
        const taxRate  = parseFloat($taxRate.value)  || 0;
        const taxable  = Math.max(0, subtotal - disc);
        const taxAmt   = taxable * (taxRate / 100);
        const total    = taxable + taxAmt;
        const paid     = parseFloat($paid.value) || 0;

        $subtotal.textContent = money(subtotal);
        $tax.textContent      = money(taxAmt);
        $total.textContent    = money(total);
        $change.textContent   = money(Math.max(0, paid - total));
    }
    [$discount, $taxRate, $paid].forEach(el => el.addEventListener('input', recalc));

    /* ---------- Clear ---------- */
    $clear.addEventListener('click', () => {
        if (cart.length === 0) return;
        if (confirm('Clear the cart?')) { cart.length = 0; renderCart(); }
    });

    /* ---------- Submit ---------- */
    $complete.addEventListener('click', function () {
        if (cart.length === 0) { alert('Cart is empty.'); return; }
        const subtotal = cart.reduce((s, c) => s + c.qty * c.price, 0);
        const disc     = parseFloat($discount.value) || 0;
        const taxRate  = parseFloat($taxRate.value)  || 0;
        const total    = Math.max(0, subtotal - disc) * (1 + taxRate / 100);

        const paid = parseFloat($paid.value) || 0;
        if (paid < total - 0.01) {
            if (!confirm('Amount paid is less than total. Continue anyway?')) return;
        }

        const payload = {
            csrf_token: csrf,
            customer_name:  $custName.value || null,
            customer_phone: $custPhone.value || null,
            discount:       disc,
            tax_rate:       taxRate,
            payment_method: $paymentMode.value,
            paid:           paid,
            items: cart.map(c => ({
                medicine_id: c.id,
                quantity:    c.qty,
                unit_price:  c.price
            }))
        };

        $complete.disabled = true;
        $complete.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

        fetch(window.URLS.storeSale, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                window.location.href = window.URLS.invoice + res.sale.id;
            } else {
                alert('Error: ' + (res.message || 'Unknown'));
                $complete.disabled = false;
                $complete.innerHTML = '<i class="bi bi-check-lg"></i> Complete Sale';
            }
        })
        .catch(err => {
            alert('Network error: ' + err.message);
            $complete.disabled = false;
            $complete.innerHTML = '<i class="bi bi-check-lg"></i> Complete Sale';
        });
    });

    /* ---------- Helpers ---------- */
    function escapeHtml(s) {
        if (s == null) return '';
        return String(s).replace(/[&<>"']/g, c => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
        }[c]));
    }
})();
