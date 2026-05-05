<?php $pageTitle = 'Add Pharmacy'; require __DIR__ . '/../layouts/header.php'; ?>
<h3 class="mb-3"><i class="bi bi-plus-circle"></i> Add Pharmacy</h3>
<div class="card"><div class="card-body">
<?php
$action = url('index.php?page=pharmacies&action=store');
$submitLabel = 'Create Pharmacy';
$pharmacy = null;
require __DIR__ . '/_form.php';
?>
</div></div>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
