<?php
/**
 * Shared form fragment for Add/Edit medicine.
 * Expects: $medicine (array or null), $categories, $suppliers, $action (URL), $submitLabel.
 */
$m = $medicine ?? [];
?>
<form method="POST" action="<?= sanitize($action) ?>">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <?php if (!empty($m['id'])): ?>
        <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label">Medicine Name *</label>
            <input type="text" name="name" class="form-control" required value="<?= sanitize($m['name'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Barcode</label>
            <input type="text" name="barcode" class="form-control" value="<?= sanitize($m['barcode'] ?? '') ?>">
        </div>

        <div class="col-md-6">
            <label class="form-label">Generic Name</label>
            <input type="text" name="generic_name" class="form-control" value="<?= sanitize($m['generic_name'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-select">
                <option value="">— None —</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($m['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                        <?= sanitize($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Supplier</label>
            <select name="supplier_id" class="form-select">
                <option value="">— None —</option>
                <?php foreach ($suppliers as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= ($m['supplier_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                        <?= sanitize($s['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label">Unit</label>
            <input type="text" name="unit" class="form-control" placeholder="tablet, ml, bottle..." value="<?= sanitize($m['unit'] ?? 'pcs') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Batch Number</label>
            <input type="text" name="batch_number" class="form-control" value="<?= sanitize($m['batch_number'] ?? '') ?>">
        </div>
        <div class="col-md-5">
            <label class="form-label">Expiry Date</label>
            <input type="date" name="expiry_date" class="form-control" value="<?= sanitize($m['expiry_date'] ?? '') ?>">
        </div>

        <div class="col-md-3">
            <label class="form-label">Cost Price *</label>
            <input type="number" step="0.01" name="cost_price" class="form-control" required value="<?= sanitize($m['cost_price'] ?? '0') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Selling Price *</label>
            <input type="number" step="0.01" name="selling_price" class="form-control" required value="<?= sanitize($m['selling_price'] ?? '0') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Quantity *</label>
            <input type="number" name="quantity" class="form-control" required value="<?= sanitize($m['quantity'] ?? '0') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Reorder Level</label>
            <input type="number" name="reorder_level" class="form-control" value="<?= sanitize($m['reorder_level'] ?? '10') ?>">
        </div>

        <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="2"><?= sanitize($m['description'] ?? '') ?></textarea>
        </div>

        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= !isset($m['is_active']) || $m['is_active'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">Active</label>
            </div>
        </div>
    </div>

    <hr>
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> <?= sanitize($submitLabel) ?></button>
    <a href="<?= url('index.php?page=medicines') ?>" class="btn btn-secondary">Cancel</a>
</form>
