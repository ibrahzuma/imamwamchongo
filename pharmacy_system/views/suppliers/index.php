<?php $pageTitle = 'Suppliers'; require __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="bi bi-building"></i> Suppliers</h3>
    <?php if (hasRole(['admin','pharmacist'])): ?>
        <a href="<?= url('index.php?page=suppliers&action=create') ?>" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add Supplier</a>
    <?php endif; ?>
</div>

<div class="card"><div class="card-body table-responsive">
    <table class="table table-hover align-middle">
        <thead><tr><th>Name</th><th>Contact</th><th>Phone</th><th>Email</th><th>Address</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        <?php if (empty($suppliers)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No suppliers yet.</td></tr>
        <?php else: foreach ($suppliers as $s): ?>
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
                        <a href="<?= url('index.php?page=suppliers&action=delete&id=' . $s['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete supplier?')"><i class="bi bi-trash"></i></a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div></div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
