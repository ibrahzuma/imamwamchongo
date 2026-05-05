<?php $s = $supplier ?? []; ?>
<form method="POST" action="<?= sanitize($action) ?>">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <?php if (!empty($s['id'])): ?>
        <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Name *</label>
            <input type="text" name="name" class="form-control" required value="<?= sanitize($s['name'] ?? '') ?>">
        </div>
        <div class="col-md-6"><label class="form-label">Contact Person</label>
            <input type="text" name="contact_person" class="form-control" value="<?= sanitize($s['contact_person'] ?? '') ?>">
        </div>
        <div class="col-md-4"><label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?= sanitize($s['phone'] ?? '') ?>">
        </div>
        <div class="col-md-4"><label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= sanitize($s['email'] ?? '') ?>">
        </div>
        <div class="col-md-4"><label class="form-label">Tax ID</label>
            <input type="text" name="tax_id" class="form-control" value="<?= sanitize($s['tax_id'] ?? '') ?>">
        </div>
        <div class="col-12"><label class="form-label">Address</label>
            <textarea name="address" class="form-control" rows="2"><?= sanitize($s['address'] ?? '') ?></textarea>
        </div>
        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                    <?= !isset($s['is_active']) || $s['is_active'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">Active</label>
            </div>
        </div>
    </div>
    <hr>
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> <?= sanitize($submitLabel) ?></button>
    <a href="<?= url('index.php?page=suppliers') ?>" class="btn btn-secondary">Cancel</a>
</form>
