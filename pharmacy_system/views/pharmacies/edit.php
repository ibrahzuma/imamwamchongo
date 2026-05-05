<?php $pageTitle = 'Edit Pharmacy'; require __DIR__ . '/../layouts/header.php'; ?>
<h3 class="mb-3"><i class="bi bi-pencil"></i> Edit Pharmacy</h3>
<div class="card"><div class="card-body">
<?php
$action = url('index.php?page=pharmacies&action=update');
$submitLabel = 'Update Pharmacy';
require __DIR__ . '/_form.php';
?>
</div></div>

<div class="card mt-3"><div class="card-body">
    <h5 class="text-muted mb-3"><i class="bi bi-key"></i> API Key</h5>
    <p class="font-monospace text-break"><?= sanitize($pharmacy['api_key'] ?? '') ?></p>
    <form method="POST" action="<?= url('index.php?page=pharmacies&action=rotateKey') ?>"
          onsubmit="return confirm('Rotate API key? The current key will stop working immediately.');">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="id" value="<?= (int)$pharmacy['id'] ?>">
        <button type="submit" class="btn btn-warning btn-sm">
            <i class="bi bi-arrow-clockwise"></i> Rotate Key
        </button>
    </form>
</div></div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
