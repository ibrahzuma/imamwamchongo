<?php $pageTitle = 'Medicines'; require __DIR__ . '/../layouts/header.php'; ?>
<div data-rt-refresh="stock.adjusted,sale.created,purchase.created" hidden></div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="bi bi-capsule"></i> Medicines</h3>
    <?php if (hasRole(['admin','pharmacist'])): ?>
        <a href="<?= url('index.php?page=medicines&action=create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Medicine
        </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <input type="hidden" name="page" value="medicines">
            <div class="col-md-6">
                <input type="text" name="search" value="<?= sanitize($search) ?>" class="form-control" placeholder="Search by name, generic name, or barcode...">
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Search</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle js-datatable" data-order='[[0,"asc"]]'>
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Barcode</th>
                    <th>Batch / Expiry</th>
                    <th class="text-end">Cost</th>
                    <th class="text-end">Price</th>
                    <th class="text-center">Stock</th>
                    <th class="text-end dt-no-sort dt-no-export">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($medicines as $m): ?>
                    <tr>
                        <td>
                            <strong><?= sanitize($m['name']) ?></strong>
                            <?php if (!empty($m['generic_name'])): ?>
                                <br><small class="text-muted"><?= sanitize($m['generic_name']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= sanitize($m['category_name'] ?? '-') ?></td>
                        <td><code><?= sanitize($m['barcode'] ?? '-') ?></code></td>
                        <td>
                            <small><?= sanitize($m['batch_number'] ?? '-') ?></small><br>
                            <?php if (!empty($m['expiry_date'])):
                                $isNear = strtotime($m['expiry_date']) < strtotime('+60 days'); ?>
                                <span class="badge <?= $isNear ? 'bg-warning text-dark' : 'bg-light text-dark' ?>">
                                    <?= dateFmt($m['expiry_date']) ?>
                                </span>
                            <?php else: ?> - <?php endif; ?>
                        </td>
                        <td class="text-end"><?= money($m['cost_price']) ?></td>
                        <td class="text-end"><strong><?= money($m['selling_price']) ?></strong></td>
                        <td class="text-center">
                            <?php
                            $low = $m['quantity'] <= $m['reorder_level'];
                            $cls = $low ? 'bg-danger' : 'bg-success';
                            ?>
                            <span class="badge <?= $cls ?>"><?= (int)$m['quantity'] ?> <?= sanitize($m['unit']) ?></span>
                        </td>
                        <td class="text-end">
                            <?php if (hasRole(['admin','pharmacist'])): ?>
                                <a href="<?= url('index.php?page=medicines&action=edit&id=' . $m['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <?php endif; ?>
                            <?php if (hasRole(['admin'])): ?>
                                <form method="POST" action="<?= url('index.php?page=medicines&action=delete') ?>" class="d-inline"
                                      onsubmit="return confirm('Delete this medicine?');">
                                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
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

<?php require __DIR__ . '/../layouts/footer.php'; ?>
