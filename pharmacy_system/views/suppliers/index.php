<?php $pageTitle = 'Suppliers'; require __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="bi bi-building"></i> Suppliers</h3>
    <?php if (hasRole(['admin','pharmacist'])): ?>
        <a href="<?= url('index.php?page=suppliers&action=create') ?>" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add Supplier</a>
    <?php endif; ?>
</div>

<div class="card"><div class="card-body table-responsive">
    <table class="table table-hover align-middle js-datatable" data-order='[[0,"asc"]]'>
        <thead><tr><th>Name</th><th>Contact</th><th>Phone</th><th>Email</th><th>Address</th><th class="text-end dt-no-sort dt-no-export">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($suppliers as $s): ?>
            <tr>
                <td><strong><?= sanitize($s['name']) ?></strong>
                    <?php if (!$s['is_active']): ?><span class="badge bg-secondary">inactive</span><?php endif; ?>
                </td>
                <td><?= sanitize($s['contact_person'] ?? '-') ?></td>
                <td><?= sanitize($s['phone'] ?? '-') ?></td>
                <td><?= sanitize($s['email'] ?? '-') ?></td>
                <td><small><?= sanitize($s['address'] ?? '-') ?></small></td>
                <td class="text-end">
                    <?php if (hasRole(['admin','pharmacist'])): ?>
                        <a href="<?= url('index.php?page=suppliers&action=edit&id=' . $s['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                    <?php endif; ?>
                    <?php if (hasRole(['admin'])): ?>
                        <form method="POST" action="<?= url('index.php?page=suppliers&action=delete') ?>" class="d-inline"
                              onsubmit="return confirm('Delete supplier?');">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
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
