<?php $title = 'Users'; include __DIR__ . '/../layouts/header.php'; ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><i class="bi bi-people"></i> System Users</h3>
        <a href="?page=users&action=create" class="btn btn-success"><i class="bi bi-plus-circle"></i> New User</a>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped js-datatable" data-order='[[1,"asc"]]'>
                <thead>
                    <tr>
                        <th class="dt-no-sort dt-no-export">#</th><th>Username</th><th>Full Name</th><th>Email</th>
                        <th>Role</th><th>Status</th><th>Last Login</th><th class="dt-no-sort dt-no-export">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i=1; foreach ($users as $u): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                            <td><?= htmlspecialchars($u['full_name']) ?></td>
                            <td><?= htmlspecialchars($u['email'] ?? '-') ?></td>
                            <td>
                                <?php $rb = ['admin'=>'danger','pharmacist'=>'primary','cashier'=>'success']; ?>
                                <span class="badge bg-<?= $rb[$u['role']] ?? 'secondary' ?>"><?= htmlspecialchars(ucfirst($u['role'])) ?></span>
                            </td>
                            <td>
                                <?php if ($u['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $u['last_login'] ? dateFmt($u['last_login'], 'd M H:i') : '-' ?></td>
                            <td>
                                <a href="?page=users&action=edit&id=<?= $u['id'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                <?php if ($u['id'] != currentUser()['id']): ?>
                                    <form method="POST" action="?page=users&action=delete" class="d-inline" onsubmit="return confirm('Delete this user?');">
                                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                        <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
