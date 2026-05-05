<?php $pageTitle='Inventory'; require __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="bi bi-box-seam"></i> Inventory</h3>
    <?php if (hasRole(['admin','pharmacist'])): ?>
        <a href="<?= url('index.php?page=inventory&action=adjust') ?>" class="btn btn-primary"><i class="bi bi-arrow-left-right"></i> Stock Adjustment</a>
    <?php endif; ?>
</div>

<?php if (!empty($lowStock)): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle"></i>
    <strong><?= count($lowStock) ?></strong> item(s) below reorder level.
    <a href="<?= url('index.php?page=reports&action=inventory') ?>" class="alert-link">View report</a>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><i class="bi bi-clock-history"></i> Recent Stock Movements</div>
    <div class="card-body table-responsive">
        <table class="table table-sm">
            <thead><tr><th>Date</th><th>Medicine</th><th>Type</th><th class="text-end">Qty</th><th>Reference</th><th>Notes</th><th>By</th></tr></thead>
            <tbody>
            <?php if (empty($movements)): ?>
                <tr><td colspan="7" class="text-center text-muted py-3">No stock movements yet.</td></tr>
            <?php else: foreach ($movements as $m): ?>
                <tr>
                    <td><small><?= dateFmt($m['created_at'], 'M j H:i') ?></small></td>
                    <td><?= sanitize($m['medicine_name']) ?></td>
                    <td>
                        <?php $cls = ['in'=>'success','out'=>'danger','adjustment'=>'info'][$m['movement_type']]; ?>
                        <span class="badge bg-<?= $cls ?>">
                            <i class="bi bi-arrow-<?= $m['movement_type']==='in'?'up':'down' ?>"></i> <?= sanitize(ucfirst($m['movement_type'])) ?>
                        </span>
                    </td>
                    <td class="text-end"><strong><?= (int)$m['quantity'] ?></strong></td>
                    <td><small><?= sanitize($m['reference_type']) ?> #<?= sanitize($m['reference_id'] ?? '-') ?></small></td>
                    <td><small><?= sanitize($m['notes'] ?? '-') ?></small></td>
                    <td><small><?= sanitize($m['user_name'] ?? '-') ?></small></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
