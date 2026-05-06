<?php $pageTitle = 'Bulk Import Medicines'; require __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="bi bi-cloud-upload"></i> Bulk Import Medicines</h3>
    <a href="<?= url('index.php?page=medicines') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Medicines
    </a>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-file-earmark-spreadsheet"></i> Step 1 — Download Template</h5>
                <p class="text-muted small mb-3">
                    Download the CSV template, open it in Excel (or any spreadsheet app), fill in your medicines, and save it as CSV.
                </p>
                <a href="<?= url('index.php?page=medicines&action=downloadTemplate') ?>" class="btn btn-success">
                    <i class="bi bi-download"></i> Download Template
                </a>

                <hr class="my-4">

                <h6>Column reference</h6>
                <ul class="small mb-2">
                    <li><strong>name</strong> <span class="text-danger">*required</span></li>
                    <li><strong>cost_price</strong> <span class="text-danger">*required</span> — number, e.g. <code>50.00</code></li>
                    <li><strong>selling_price</strong> <span class="text-danger">*required</span> — number</li>
                    <li><strong>quantity</strong> <span class="text-danger">*required</span> — integer</li>
                    <li><strong>generic_name</strong>, <strong>barcode</strong>, <strong>unit</strong>, <strong>batch_number</strong>, <strong>description</strong> — optional text</li>
                    <li><strong>expiry_date</strong> — format <code>YYYY-MM-DD</code></li>
                    <li><strong>reorder_level</strong> — integer (defaults to 10)</li>
                    <li><strong>category</strong> / <strong>supplier</strong> — match by name (case-insensitive). Unknown names are left blank.</li>
                </ul>
                <p class="small text-muted mb-0">
                    Barcodes must be unique within your pharmacy. Delete the example row before uploading.
                </p>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-upload"></i> Step 2 — Upload Filled CSV</h5>
                <p class="text-muted small mb-3">
                    The whole file is imported in a single transaction — if any row has an error, nothing is saved and you can fix and re-upload.
                </p>
                <form method="POST" action="<?= url('index.php?page=medicines&action=bulkStore') ?>" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <div class="mb-3">
                        <label class="form-label">CSV file</label>
                        <input type="file" name="csv_file" accept=".csv,text/csv" class="form-control" required>
                        <div class="form-text">Maximum 5 MB. Must use the template's column headers.</div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-cloud-upload"></i> Upload &amp; Import
                    </button>
                    <a href="<?= url('index.php?page=medicines') ?>" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
