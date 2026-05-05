<?php $c = $category ?? []; ?>
<form method="POST" action="<?= sanitize($action) ?>">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <?php if (!empty($c['id'])): ?>
        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Name *</label>
            <input type="text" name="name" class="form-control" required value="<?= sanitize($c['name'] ?? '') ?>">
        </div>
        <div class="col-12"><label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3"><?= sanitize($c['description'] ?? '') ?></textarea>
        </div>
    </div>
    <hr>
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> <?= sanitize($submitLabel) ?></button>
    <a href="<?= url('index.php?page=categories') ?>" class="btn btn-secondary">Cancel</a>
</form>
