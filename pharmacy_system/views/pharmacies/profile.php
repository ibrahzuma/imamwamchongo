<?php $pageTitle = 'Pharmacy Profile'; require __DIR__ . '/../layouts/header.php'; ?>

<h3 class="mb-3"><i class="bi bi-shop"></i> Pharmacy Profile</h3>
<p class="text-muted">Update your pharmacy's contact and license information. The slug and active status are managed by the platform admin.</p>

<div class="card"><div class="card-body">
<form method="POST" action="<?= url('index.php?page=pharmacies&action=updateProfile') ?>">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Pharmacy Name *</label>
            <input type="text" name="name" class="form-control" required value="<?= sanitize($pharmacy['name'] ?? '') ?>">
        </div>
        <div class="col-md-6"><label class="form-label">License Number</label>
            <input type="text" name="license_number" class="form-control" value="<?= sanitize($pharmacy['license_number'] ?? '') ?>">
        </div>
        <div class="col-md-6"><label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?= sanitize($pharmacy['phone'] ?? '') ?>">
        </div>
        <div class="col-md-6"><label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= sanitize($pharmacy['email'] ?? '') ?>">
        </div>
        <div class="col-12"><label class="form-label">Address</label>
            <textarea name="address" class="form-control" rows="2"><?= sanitize($pharmacy['address'] ?? '') ?></textarea>
        </div>
        <div class="col-md-6"><label class="form-label">Slug <small class="text-muted">(read-only)</small></label>
            <input type="text" class="form-control" disabled value="<?= sanitize($pharmacy['slug'] ?? '') ?>">
        </div>
        <div class="col-md-6"><label class="form-label">Status</label>
            <div>
                <?php if ($pharmacy['is_active']): ?>
                    <span class="badge bg-success">Active</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Disabled</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <hr>
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Save Profile</button>
</form>
</div></div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
