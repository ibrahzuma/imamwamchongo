<?php $pageTitle='Sales'; require __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="bi bi-receipt"></i> Sales History</h3>
    <a href="<?= url('index.php?page=pos') ?>" class="btn btn-success"><i class="bi bi-cart-plus"></i> New Sale</a>
</div>

<div class="card"><div class="card-body table-responsive">
    <table class="table table-hover align-middle">
        <thead>
        <tr><th>Invoice #</th><th>Customer</th><th>Cashier</th><th class="text-end">Total</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($sales)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">No sales yet.</td></tr>
        <?php else: foreach ($sales as $s): ?>
            <tr>
                <td><strong><?= sanitize($s['invoice_number']) ?></strong></td>
                <td><?= sanitize($s['customer_name'] ?? 'Walk-in') ?></td>
                <td><small><?= sanitize($s['user_name']) ?></small></td>
                <td class="text-end"><strong><?= money($s['total']) ?></strong></td>
                <td><span class="badge bg-light text-dark"><?= sanitize(ucfirst($s['payment_method'])) ?></span></td>
                <td>
                    <?php
                    $cls = ['completed'=>'success','pending'=>'warning','cancelled'=>'danger'][$s['status']] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?= $cls ?>"><?= sanitize(ucfirst($s['status'])) ?></span>
                </td>
                <td><small><?= dateFmt($s['created_at'], 'M j Y, H:i') ?></small></td>
                <td>
                    <a href="<?= url('index.php?page=sales&action=invoice&id=' . $s['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> View</a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div></div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
