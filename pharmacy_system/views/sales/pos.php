<?php $pageTitle='POS'; require __DIR__ . '/../layouts/header.php'; ?>

<h3 class="mb-3"><i class="bi bi-cart-plus"></i> Point of Sale</h3>

<div class="pos-grid">
    <!-- LEFT: Search & products -->
    <div>
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-8">
                        <label class="form-label small fw-bold">Search Medicine</label>
                        <div class="position-relative">
                            <input type="text" id="medicineSearch" class="form-control form-control-lg" placeholder="Type name, generic name, or barcode...">
                            <div id="searchResults" class="position-absolute w-100 bg-white border rounded shadow-sm" style="z-index:99; display:none; max-height:300px; overflow:auto;"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Barcode Scanner</label>
                        <input type="text" id="barcodeInput" class="form-control form-control-lg" placeholder="Scan or enter barcode">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-cart"></i> Cart</div>
            <div class="card-body p-0">
                <table class="table pos-cart-table mb-0" id="cartTable">
                    <thead>
                        <tr>
                            <th>Medicine</th>
                            <th width="100" class="text-center">Qty</th>
                            <th width="120" class="text-end">Price</th>
                            <th width="120" class="text-end">Subtotal</th>
                            <th width="50"></th>
                        </tr>
                    </thead>
                    <tbody id="cartBody">
                        <tr id="emptyRow"><td colspan="5" class="pos-cart-empty">Cart is empty. Search for medicines to add.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- RIGHT: Checkout panel -->
    <div>
        <div class="card">
            <div class="card-header bg-primary text-white"><i class="bi bi-receipt"></i> Checkout</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small">Customer Name (optional)</label>
                    <input type="text" id="customerName" class="form-control form-control-sm">
                </div>
                <div class="mb-3">
                    <label class="form-label small">Customer Phone (optional)</label>
                    <input type="text" id="customerPhone" class="form-control form-control-sm">
                </div>

                <hr>

                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal</span>
                    <strong id="subtotalDisplay"><?= money(0) ?></strong>
                </div>
                <div class="row g-2 mb-2 align-items-center">
                    <label class="col-7 mb-0 small">Discount</label>
                    <div class="col-5">
                        <input type="number" id="discount" min="0" step="0.01" value="0" class="form-control form-control-sm text-end">
                    </div>
                </div>
                <div class="row g-2 mb-2 align-items-center">
                    <label class="col-7 mb-0 small">Tax Rate (%)</label>
                    <div class="col-5">
                        <input type="number" id="taxRate" min="0" step="0.01" value="<?= htmlspecialchars($taxRate) ?>" class="form-control form-control-sm text-end">
                    </div>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Tax Amount</span>
                    <strong id="taxDisplay"><?= money(0) ?></strong>
                </div>

                <hr>
                <div class="d-flex justify-content-between mb-2">
                    <h5 class="mb-0">TOTAL</h5>
                    <h5 class="mb-0 text-primary" id="totalDisplay"><?= money(0) ?></h5>
                </div>

                <div class="mb-3">
                    <label class="form-label small">Payment Method</label>
                    <select id="paymentMethod" class="form-select form-select-sm">
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="mobile">Mobile Money</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Amount Paid</label>
                    <input type="number" id="paid" min="0" step="0.01" value="0" class="form-control">
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span>Change</span>
                    <strong id="changeDisplay" class="text-success"><?= money(0) ?></strong>
                </div>

                <button id="completeSale" class="btn btn-success w-100"><i class="bi bi-check-lg"></i> Complete Sale</button>
                <button id="clearCart" class="btn btn-outline-secondary w-100 mt-2"><i class="bi bi-x-circle"></i> Clear Cart</button>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="csrfToken" value="<?= csrfToken() ?>">

<script>
// Pass URLs into JS
window.URLS = {
    search:   "<?= url('index.php?page=medicines&action=ajaxSearch') ?>",
    barcode:  "<?= url('index.php?page=medicines&action=ajaxBarcode') ?>",
    storeSale:"<?= url('index.php?page=sales&action=store') ?>",
    invoice:  "<?= url('index.php?page=sales&action=invoice&id=') ?>"
};
window.CURRENCY = "<?= CURRENCY ?>";
</script>
<script src="<?= url('assets/js/pos.js') ?>"></script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
