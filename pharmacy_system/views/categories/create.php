<?php $pageTitle = 'Add Category'; require __DIR__ . '/../layouts/header.php'; ?>
<h3 class="mb-3"><i class="bi bi-plus-circle"></i> Add Category</h3>
<div class="card"><div class="card-body">
<?php
$action = url('index.php?page=categories&action=store');
$submitLabel = 'Save Category';
$category = null;
require __DIR__ . '/_form.php';
?>
</div></div>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
