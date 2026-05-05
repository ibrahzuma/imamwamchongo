<?php $pageTitle = 'Categories'; require __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="bi bi-tags"></i> Medicine Categories</h3>
    <a href="<?= url('index.php?page=categories&action=create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Add Category
    </a>
</div>

<div class="card"><div class="card-body table-responsive">
    <table class="table table-hover align-middle js-datatable" data-order='[[1,"asc"]]'>
        <thead>
            <tr><th class="dt-no-sort dt-no-export">#</th><th>Name</th><th>Description</th><th class="text-end dt-no-sort dt-no-export">Actions</th></tr>
        </thead>
        <tbody>
        <?php $i = 1; foreach ($categories as $c): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><strong><?= sanitize($c['name']) ?></strong></td>
                <td><small class="text-muted"><?= sanitize($c['description'] ?? '-') ?></small></td>
                <td class="text-end">
                    <a href="<?= url('index.php?page=categories&action=edit&id=' . $c['id']) ?>"
                       class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                    <?php if (hasRole(['admin'])): ?>
                        <form method="POST" action="<?= url('index.php?page=categories&action=delete') ?>" class="d-inline"
                              onsubmit="return confirm('Delete this category? Medicines in it will become uncategorized.');">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div></div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
