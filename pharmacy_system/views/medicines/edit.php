<?php $pageTitle = 'Edit Medicine'; require __DIR__ . '/../layouts/header.php'; ?>
<h3 class="mb-3"><i class="bi bi-pencil"></i> Edit Medicine</h3>
<div class="card"><div class="card-body">
<?php
$action      = url('index.php?page=medicines&action=update');
$submitLabel = 'Update Medicine';
require __DIR__ . '/_form.php';
?>
</div></div>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
