<?php
// Shared form for create/edit; expects $user (array or null) and $isEdit (bool)
$user = $user ?? [];
$isEdit = $isEdit ?? false;
?>
<form method="POST" action="?page=users&action=<?= $isEdit ? 'update&id=' . $user['id'] : 'store' ?>">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Username <span class="text-danger">*</span></label>
            <input type="text" name="username" class="form-control" required
                   value="<?= htmlspecialchars($user['username'] ?? '') ?>"
                   <?= $isEdit ? 'readonly' : '' ?>>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Full Name <span class="text-danger">*</span></label>
            <input type="text" name="full_name" class="form-control" required
                   value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control"
                   value="<?= htmlspecialchars($user['email'] ?? '') ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control"
                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Role <span class="text-danger">*</span></label>
            <select name="role" class="form-select" required>
                <?php foreach (['admin','pharmacist','cashier'] as $r): ?>
                    <option value="<?= $r ?>" <?= ($user['role'] ?? '') === $r ? 'selected' : '' ?>>
                        <?= ucfirst($r) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Status</label>
            <select name="is_active" class="form-select">
                <option value="1" <?= ($user['is_active'] ?? 1) ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= isset($user['is_active']) && !$user['is_active'] ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Password <?= $isEdit ? '<small class="text-muted">(leave blank to keep current)</small>' : '<span class="text-danger">*</span>' ?></label>
            <input type="password" name="password" class="form-control" <?= $isEdit ? '' : 'required' ?>>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="password_confirm" class="form-control" <?= $isEdit ? '' : 'required' ?>>
        </div>
    </div>
    <button type="submit" class="btn btn-primary"><i class="bi bi-check"></i> <?= $isEdit ? 'Update' : 'Create' ?> User</button>
    <a href="?page=users" class="btn btn-secondary">Cancel</a>
</form>
