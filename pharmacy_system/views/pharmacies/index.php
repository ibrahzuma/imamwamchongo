<?php $pageTitle = 'Pharmacies'; require __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="bi bi-shop"></i> Pharmacies <small class="text-muted">(<?= count($pharmacies) ?>)</small></h3>
    <a href="<?= url('index.php?page=pharmacies&action=create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Add Pharmacy
    </a>
</div>

<div class="card"><div class="card-body table-responsive">
    <table class="table table-hover align-middle js-datatable" data-order='[[0,"asc"]]'>
        <thead>
            <tr>
                <th>Name</th><th>Slug</th><th>License</th><th>Phone</th>
                <th>Users</th><th>Medicines</th><th class="dt-no-export">API Key</th><th>Status</th>
                <th class="text-end dt-no-sort dt-no-export">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($pharmacies as $p): ?>
            <tr>
                <td><strong><?= sanitize($p['name']) ?></strong></td>
                <td><code><?= sanitize($p['slug']) ?></code></td>
                <td><small><?= sanitize($p['license_number'] ?? '-') ?></small></td>
                <td><?= sanitize($p['phone'] ?? '-') ?></td>
                <td><?= (int)$p['user_count'] ?></td>
                <td><?= (int)$p['medicine_count'] ?></td>
                <td>
                    <small class="text-muted font-monospace" style="word-break:break-all;">
                        <?= sanitize(substr($p['api_key'] ?? '', 0, 12) . '…') ?>
                    </small>
                </td>
                <td>
                    <?php if ($p['is_active']): ?>
                        <span class="badge bg-success">Active</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Disabled</span>
                    <?php endif; ?>
                </td>
                <td class="text-end">
                    <a href="<?= url('index.php?page=pharmacies&action=edit&id=' . $p['id']) ?>"
                       class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                    <form method="POST" action="<?= url('index.php?page=pharmacies&action=rotateKey') ?>" class="d-inline"
                          onsubmit="return confirm('Rotate API key? The current key will stop working immediately.');">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Rotate API Key"><i class="bi bi-key"></i></button>
                    </form>
                    <form method="POST" action="<?= url('index.php?page=pharmacies&action=toggle') ?>" class="d-inline"
                          onsubmit="return confirm('<?= $p['is_active'] ? 'Disable' : 'Enable' ?> this pharmacy? Users will <?= $p['is_active'] ? 'be unable to login' : 'regain access' ?>.');">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button type="submit"
                                class="btn btn-sm btn-outline-<?= $p['is_active'] ? 'danger' : 'success' ?>"
                                title="<?= $p['is_active'] ? 'Disable' : 'Enable' ?>">
                            <i class="bi bi-<?= $p['is_active'] ? 'pause-circle' : 'play-circle' ?>"></i>
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div></div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
