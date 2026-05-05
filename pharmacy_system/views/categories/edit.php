<?php $pageTitle = 'Edit Category'; require __DIR__ . '/../layouts/header.php'; ?>
<h3 class="mb-3"><i class="bi bi-pencil"></i> Edit Category</h3>
<div class="card"><div class="card-body">
<?php
$action = url('index.php?page=categories&action=update');
$submitLabel = 'Update Category';
require __DIR__ . '/_form.php';
?>
</div></div>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
