<?php $pageTitle='Purchases'; require __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="bi bi-truck"></i> Purchases</h3>
    <?php if (hasRole(['admin','pharmacist'])): ?>
        <a href="<?= url('index.php?page=purchases&action=create') ?>" class="btn btn-primary"><i class="bi bi-plus-circle"></i> New Purchase</a>
    <?php endif; ?>
</div>

<div class="card"><div class="card-body table-responsive">
    <table class="table table-hover align-middle">
        <thead><tr><th>Reference</th><th>Supplier</th><th>By</th><th class="text-end">Total</th><th>Status</th><th>Date</th><th></th></tr></thead>
        <tbody>
        <?php if (empty($purchases)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No purchases yet.</td></tr>
        <?php else: foreach ($purchases as $p): ?>
            <tr>
                <td><strong><?= sanitize($p['reference_number']) ?></strong></td>
                <td><?= sanitize($p['supplier_name']) ?></td>
                <td><small><?= sanitize($p['user_name']) ?></small></td>
                <td class="text-end"><strong><?= money($p['total']) ?></strong></td>
                <td><span class="badge bg-success"><?= sanitize(ucfirst($p['status'])) ?></span></td>
                <td><small><?= dateFmt($p['created_at'], 'M j Y H:i') ?></small></td>
                <td><a href="<?= url('index.php?page=purchases&action=show&id=' . $p['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div></div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
