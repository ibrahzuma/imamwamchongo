<?php $title = 'New User'; include __DIR__ . '/../layouts/header.php'; ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><i class="bi bi-person-plus"></i> New User</h3>
        <a href="?page=users" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
    <div class="card">
        <div class="card-body">
            <?php $isEdit = false; include __DIR__ . '/_form.php'; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
