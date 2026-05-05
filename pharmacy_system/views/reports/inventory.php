<?php $title = 'Inventory Report'; include __DIR__ . '/../layouts/header.php'; ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><i class="bi bi-boxes"></i> Inventory Report</h3>
        <div>
            <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer"></i> Print</button>
            <a href="?page=reports" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card text-bg-primary">
                <div class="card-body">
                    <h6>Total Items</h6>
                    <h3><?= count($medicines) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-bg-success">
                <div class="card-body">
                    <h6>Total Stock Units</h6>
                    <h3><?= number_format($totalUnits) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-bg-info">
                <div class="card-body">
                    <h6>Stock Value (Cost)</h6>
                    <h3><?= money($totalCostValue) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-bg-warning">
                <div class="card-body">
                    <h6>Stock Value (Retail)</h6>
                    <h3><?= money($totalRetailValue) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-light"><strong>Stock Levels</strong></div>
        <div class="card-body">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>#</th><th>Medicine</th><th>Category</th><th>Batch</th>
                        <th class="text-end">Stock</th><th class="text-end">Reorder Lvl</th>
                        <th class="text-end">Cost</th><th class="text-end">Price</th>
                        <th class="text-end">Stock Value</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($medicines as $m):
                        $value = ($m['quantity'] ?? 0) * ($m['cost_price'] ?? 0);
                        $isLow = ($m['quantity'] ?? 0) <= ($m['reorder_level'] ?? 0);
                    ?>
                        <tr class="<?= $isLow ? 'table-warning' : '' ?>">
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($m['name']) ?></td>
                            <td><?= htmlspecialchars($m['category_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($m['batch_number'] ?? '-') ?></td>
                            <td class="text-end"><?= $m['quantity'] ?? 0 ?></td>
                            <td class="text-end"><?= $m['reorder_level'] ?? 0 ?></td>
                            <td class="text-end"><?= money($m['cost_price'] ?? 0) ?></td>
                            <td class="text-end"><?= money($m['price'] ?? 0) ?></td>
                            <td class="text-end"><?= money($value) ?></td>
                            <td>
                                <?php if ($isLow): ?>
                                    <span class="badge bg-danger">Low Stock</span>
                                <?php else: ?>
                                    <span class="badge bg-success">OK</span>
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
