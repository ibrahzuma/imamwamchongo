<?php $title = 'Edit User'; include __DIR__ . '/../layouts/header.php'; ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><i class="bi bi-pencil-square"></i> Edit User</h3>
        <a href="?page=users" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
    <div class="card">
        <div class="card-body">
            <?php $isEdit = true; include __DIR__ . '/_form.php'; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
