<?php $pageTitle='Invoice'; require __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h3 class="mb-0"><i class="bi bi-receipt"></i> Invoice <?= sanitize($sale['invoice_number']) ?></h3>
    <div>
        <a href="<?= url('index.php?page=pos') ?>" class="btn btn-success"><i class="bi bi-plus-circle"></i> New Sale</a>
        <a href="<?= url('index.php?page=sales') ?>" class="btn btn-secondary"><i class="bi bi-list"></i> All Sales</a>
        <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer"></i> Print Receipt</button>
    </div>
</div>

<div class="card mb-3 no-print">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>Customer</h6>
                <p class="mb-1"><?= sanitize($sale['customer_name'] ?? 'Walk-in customer') ?></p>
                <?php if (!empty($sale['customer_phone'])): ?>
                    <p class="mb-1"><small>Phone: <?= sanitize($sale['customer_phone']) ?></small></p>
                <?php endif; ?>
            </div>
            <div class="col-md-6 text-md-end">
                <h6>Sale Information</h6>
                <p class="mb-1"><strong>Invoice:</strong> <?= sanitize($sale['invoice_number']) ?></p>
                <p class="mb-1"><strong>Date:</strong> <?= dateFmt($sale['created_at'], 'M j, Y H:i') ?></p>
                <p class="mb-1"><strong>Cashier:</strong> <?= sanitize($sale['user_name']) ?></p>
                <p class="mb-1"><strong>Payment:</strong> <?= sanitize(ucfirst($sale['payment_method'])) ?></p>
            </div>
        </div>

        <hr>

        <table class="table">
            <thead><tr><th>Item</th><th class="text-center">Qty</th><th class="text-end">Unit Price</th><th class="text-end">Subtotal</th></tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= sanitize($it['medicine_name']) ?> <small class="text-muted">(<?= sanitize($it['unit']) ?>)</small></td>
                    <td class="text-center"><?= (int)$it['quantity'] ?></td>
                    <td class="text-end"><?= money($it['unit_price']) ?></td>
                    <td class="text-end"><?= money($it['subtotal']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr><td colspan="3" class="text-end">Subtotal</td><td class="text-end"><?= money($sale['subtotal']) ?></td></tr>
                <?php if ($sale['discount'] > 0): ?>
                    <tr><td colspan="3" class="text-end">Discount</td><td class="text-end">- <?= money($sale['discount']) ?></td></tr>
                <?php endif; ?>
                <tr><td colspan="3" class="text-end">Tax (<?= number_format($sale['tax_rate'], 2) ?>%)</td><td class="text-end"><?= money($sale['tax_amount']) ?></td></tr>
                <tr class="table-primary"><th colspan="3" class="text-end">TOTAL</th><th class="text-end"><?= money($sale['total']) ?></th></tr>
                <tr><td colspan="3" class="text-end">Paid</td><td class="text-end"><?= money($sale['paid']) ?></td></tr>
                <tr><td colspan="3" class="text-end">Change</td><td class="text-end"><?= money(max(0, $sale['paid'] - $sale['total'])) ?></td></tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Printable receipt (visible only when printing) -->
<div id="receiptPrint" class="receipt">
    <div class="center">
        <h4 class="mb-1"><?= APP_NAME ?></h4>
        <small>Pharmacy Management System</small><br>
        <small>Tel: +255 700 123 456</small>
        <hr>
    </div>
    <table>
        <tr><td>Invoice:</td><td class="text-end"><?= sanitize($sale['invoice_number']) ?></td></tr>
        <tr><td>Date:</td><td class="text-end"><?= dateFmt($sale['created_at'], 'M j Y H:i') ?></td></tr>
        <tr><td>Cashier:</td><td class="text-end"><?= sanitize($sale['user_name']) ?></td></tr>
        <?php if (!empty($sale['customer_name'])): ?>
        <tr><td>Customer:</td><td class="text-end"><?= sanitize($sale['customer_name']) ?></td></tr>
        <?php endif; ?>
    </table>
    <hr>
    <table>
        <?php foreach ($items as $it): ?>
            <tr>
                <td colspan="2"><?= sanitize($it['medicine_name']) ?></td>
            </tr>
            <tr>
                <td><?= (int)$it['quantity'] ?> x <?= money($it['unit_price']) ?></td>
                <td class="text-end"><?= money($it['subtotal']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <hr>
    <table>
        <tr><td>Subtotal:</td><td class="text-end"><?= money($sale['subtotal']) ?></td></tr>
        <?php if ($sale['discount'] > 0): ?>
            <tr><td>Discount:</td><td class="text-end">- <?= money($sale['discount']) ?></td></tr>
        <?php endif; ?>
        <tr><td>Tax (<?= number_format($sale['tax_rate'], 1) ?>%):</td><td class="text-end"><?= money($sale['tax_amount']) ?></td></tr>
        <tr><td><strong>TOTAL:</strong></td><td class="text-end"><strong><?= money($sale['total']) ?></strong></td></tr>
        <tr><td>Paid (<?= sanitize(ucfirst($sale['payment_method'])) ?>):</td><td class="text-end"><?= money($sale['paid']) ?></td></tr>
        <tr><td>Change:</td><td class="text-end"><?= money(max(0, $sale['paid'] - $sale['total'])) ?></td></tr>
    </table>
    <hr>
    <div class="center">
        <small>Thank you for your purchase!</small><br>
        <small>Get well soon ❤</small>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
