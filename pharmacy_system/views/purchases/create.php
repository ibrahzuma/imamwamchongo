<?php $title = 'New Purchase'; include __DIR__ . '/../layouts/header.php'; ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><i class="bi bi-cart-plus"></i> New Purchase Order</h3>
        <a href="?page=purchases" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <form id="purchaseForm">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header bg-light"><strong>Purchase Items</strong></div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-5">
                                <label class="form-label">Medicine</label>
                                <select id="medicineSelect" class="form-select">
                                    <option value="">-- Select Medicine --</option>
                                    <?php foreach ($medicines as $m): ?>
                                        <option value="<?= $m['id'] ?>"
                                                data-name="<?= htmlspecialchars($m['name']) ?>"
                                                data-price="<?= $m['cost_price'] ?>">
                                            <?= htmlspecialchars($m['name']) ?> (<?= htmlspecialchars($m['unit'] ?? '') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Qty</label>
                                <input type="number" id="itemQty" class="form-control" value="1" min="1">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Cost Price</label>
                                <input type="number" id="itemCost" class="form-control" step="0.01" min="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="button" id="addItemBtn" class="btn btn-primary w-100">
                                    <i class="bi bi-plus-circle"></i> Add Item
                                </button>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Batch Number (optional)</label>
                                <input type="text" id="itemBatch" class="form-control" placeholder="e.g. B2025-001">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Expiry Date (optional)</label>
                                <input type="date" id="itemExpiry" class="form-control">
                            </div>
                        </div>
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Medicine</th><th>Batch</th><th>Expiry</th>
                                    <th>Qty</th><th>Cost</th><th>Subtotal</th><th></th>
                                </tr>
                            </thead>
                            <tbody id="itemsTable">
                                <tr><td colspan="7" class="text-center text-muted">No items added yet</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header bg-light"><strong>Purchase Details</strong></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Supplier <span class="text-danger">*</span></label>
                            <select name="supplier_id" id="supplierSelect" class="form-select" required>
                                <option value="">-- Select Supplier --</option>
                                <?php foreach ($suppliers as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Purchase Date</label>
                            <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fs-5">
                            <strong>Total:</strong>
                            <strong id="purchaseTotal"><?= CURRENCY ?> 0.00</strong>
                        </div>
                        <button type="submit" id="submitBtn" class="btn btn-success w-100 mt-3" disabled>
                            <i class="bi bi-check-circle"></i> Save Purchase
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
const CURRENCY = '<?= CURRENCY ?>';
const STORE_URL = '?page=purchases&action=store';
let items = [];

document.getElementById('medicineSelect').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (opt.value) document.getElementById('itemCost').value = opt.dataset.price || 0;
});

document.getElementById('addItemBtn').addEventListener('click', function() {
    const sel = document.getElementById('medicineSelect');
    const opt = sel.options[sel.selectedIndex];
    if (!opt.value) { alert('Select a medicine'); return; }
    const qty = parseInt(document.getElementById('itemQty').value) || 0;
    const cost = parseFloat(document.getElementById('itemCost').value) || 0;
    if (qty < 1) { alert('Quantity must be at least 1'); return; }
    if (cost <= 0) { alert('Cost price required'); return; }

    items.push({
        medicine_id: parseInt(opt.value),
        name: opt.dataset.name,
        batch_number: document.getElementById('itemBatch').value,
        expiry_date: document.getElementById('itemExpiry').value,
        quantity: qty,
        cost_price: cost
    });

    document.getElementById('medicineSelect').value = '';
    document.getElementById('itemBatch').value = '';
    document.getElementById('itemExpiry').value = '';
    document.getElementById('itemQty').value = 1;
    document.getElementById('itemCost').value = '';
    renderItems();
});

function renderItems() {
    const tbody = document.getElementById('itemsTable');
    if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No items added yet</td></tr>';
        document.getElementById('submitBtn').disabled = true;
    } else {
        tbody.innerHTML = items.map((it, idx) => {
            const sub = (it.quantity * it.cost_price).toFixed(2);
            return `<tr>
                <td>${escapeHtml(it.name)}</td>
                <td>${escapeHtml(it.batch_number || '-')}</td>
                <td>${escapeHtml(it.expiry_date || '-')}</td>
                <td>${it.quantity}</td>
                <td>${CURRENCY} ${it.cost_price.toFixed(2)}</td>
                <td>${CURRENCY} ${sub}</td>
                <td><button type="button" class="btn btn-sm btn-danger" onclick="removeItem(${idx})"><i class="bi bi-trash"></i></button></td>
            </tr>`;
        }).join('');
        document.getElementById('submitBtn').disabled = false;
    }
    const total = items.reduce((sum, it) => sum + (it.quantity * it.cost_price), 0);
    document.getElementById('purchaseTotal').textContent = CURRENCY + ' ' + total.toFixed(2);
}

function removeItem(idx) { items.splice(idx, 1); renderItems(); }

function escapeHtml(s) {
    const map = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'};
    return String(s).replace(/[&<>"']/g, m => map[m]);
}

document.getElementById('purchaseForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    if (items.length === 0) { alert('Add at least one item'); return; }

    const supplier_id = document.getElementById('supplierSelect').value;
    if (!supplier_id) { alert('Select a supplier'); return; }

    document.getElementById('submitBtn').disabled = true;
    const fd = new FormData(this);
    const payload = {
        csrf_token: fd.get('csrf_token'),
        supplier_id: supplier_id,
        purchase_date: fd.get('purchase_date'),
        notes: fd.get('notes'),
        items: items
    };

    try {
        const res = await fetch(STORE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            window.location = '?page=purchases&action=show&id=' + data.purchase_id;
        } else {
            alert(data.message || 'Error saving purchase');
            document.getElementById('submitBtn').disabled = false;
        }
    } catch (err) {
        alert('Network error: ' + err.message);
        document.getElementById('submitBtn').disabled = false;
    }
});
</script>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
