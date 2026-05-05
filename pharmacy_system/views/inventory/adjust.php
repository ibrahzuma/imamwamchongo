<?php $pageTitle='Stock Adjustment'; require __DIR__ . '/../layouts/header.php'; ?>

<h3 class="mb-3"><i class="bi bi-arrow-left-right"></i> Stock Adjustment</h3>

<div class="card"><div class="card-body">
    <form method="POST" action="<?= url('index.php?page=inventory&action=storeAdjust') ?>">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Medicine *</label>
                <select name="medicine_id" class="form-select" required>
                    <option value="">— Select medicine —</option>
                    <?php foreach ($medicines as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= sanitize($m['name']) ?> (current: <?= (int)$m['quantity'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Type *</label>
                <select name="movement_type" class="form-select" required>
                    <option value="in">Stock In (+)</option>
                    <option value="out">Stock Out (-)</option>
                    <option value="adjustment">Adjustment</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Quantity *</label>
                <input type="number" name="quantity" min="1" required class="form-control">
            </div>
            <div class="col-12">
                <label class="form-label">Notes / Reason</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Damaged, expired, donation, correction..."></textarea>
            </div>
        </div>
        <hr>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Apply Adjustment</button>
        <a href="<?= url('index.php?page=inventory') ?>" class="btn btn-secondary">Cancel</a>
    </form>
</div></div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
