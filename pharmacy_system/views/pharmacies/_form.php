<?php $p = $pharmacy ?? []; $isEdit = !empty($p['id']); ?>
<form method="POST" action="<?= sanitize($action) ?>">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
    <?php endif; ?>

    <h5 class="mb-3 text-muted"><i class="bi bi-shop"></i> Pharmacy details</h5>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Name *</label>
            <input type="text" name="name" class="form-control" required value="<?= sanitize($p['name'] ?? '') ?>">
        </div>
        <div class="col-md-6"><label class="form-label">Slug *
                <small class="text-muted">(lowercase, dashes — used in URLs/API)</small></label>
            <input type="text" name="slug" class="form-control" required pattern="[a-z0-9][a-z0-9-]{1,79}"
                   value="<?= sanitize($p['slug'] ?? '') ?>">
        </div>
        <div class="col-md-6"><label class="form-label">License Number</label>
            <input type="text" name="license_number" class="form-control" value="<?= sanitize($p['license_number'] ?? '') ?>">
        </div>
        <div class="col-md-6"><label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?= sanitize($p['phone'] ?? '') ?>">
        </div>
        <div class="col-md-6"><label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= sanitize($p['email'] ?? '') ?>">
        </div>
        <div class="col-md-6"><label class="form-label">Status</label>
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                       <?= (!isset($p['is_active']) || $p['is_active']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">Active</label>
            </div>
        </div>
        <div class="col-12"><label class="form-label">Address</label>
            <textarea name="address" class="form-control" rows="2"><?= sanitize($p['address'] ?? '') ?></textarea>
        </div>
    </div>

    <?php if (!$isEdit): ?>
        <hr class="my-4">
        <h5 class="mb-3 text-muted"><i class="bi bi-person-badge"></i> Initial admin user</h5>
        <p class="text-muted small">This account will be the first admin for this pharmacy. They can create more users afterwards.</p>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Username *</label>
                <input type="text" name="admin_username" class="form-control" required>
            </div>
            <div class="col-md-6"><label class="form-label">Full Name *</label>
                <input type="text" name="admin_full_name" class="form-control" required>
            </div>
            <div class="col-md-6"><label class="form-label">Email</label>
                <input type="email" name="admin_email" class="form-control">
            </div>
            <div class="col-md-6"><label class="form-label">Phone</label>
                <input type="text" name="admin_phone" class="form-control">
            </div>
            <div class="col-md-6"><label class="form-label">Password * <small class="text-muted">(min 6 chars)</small></label>
                <input type="password" name="admin_password" class="form-control" required minlength="6">
            </div>
        </div>
    <?php endif; ?>

    <hr>
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> <?= sanitize($submitLabel) ?></button>
    <a href="<?= url('index.php?page=pharmacies') ?>" class="btn btn-secondary">Cancel</a>
</form>
