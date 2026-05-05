<?php $title = 'Sales Report'; include __DIR__ . '/../layouts/header.php'; ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><i class="bi bi-cart-check"></i> Sales Report</h3>
        <a href="?page=reports" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <?php $selectedUser = (int)($_GET['user_id'] ?? 0); ?>
            <form method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="page" value="reports">
                <input type="hidden" name="action" value="sales">
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cashier / User</label>
                    <select name="user_id" class="form-select">
                        <option value="0">All users</option>
                        <?php foreach ($cashiers as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= $selectedUser === (int)$c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['full_name']) ?> (<?= htmlspecialchars(ucfirst($c['role'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex flex-wrap gap-2">
                    <button class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
                    <a class="btn btn-primary"
                       href="?page=reports&action=exportSalesPdf&from=<?= htmlspecialchars($from) ?>&to=<?= htmlspecialchars($to) ?>&user_id=<?= $selectedUser ?>"
                       target="_blank">
                        <i class="bi bi-file-earmark-pdf"></i> Export PDF
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card text-bg-primary">
                <div class="card-body">
                    <h6>Total Sales</h6>
                    <h3><?= count($sales) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-bg-success">
                <div class="card-body">
                    <h6>Total Revenue</h6>
                    <h3><?= money($totalRevenue) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-bg-info">
                <div class="card-body">
                    <h6>Total Tax</h6>
                    <h3><?= money($totalTax) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-bg-warning">
                <div class="card-body">
                    <h6>Average Sale</h6>
                    <h3><?= count($sales) > 0 ? money($totalRevenue / count($sales)) : money(0) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-light"><strong>Daily Sales Trend</strong></div>
        <div class="card-body">
            <canvas id="salesChart" height="80"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-light"><strong>Sales Transactions</strong></div>
        <div class="card-body">
            <table class="table table-striped table-sm js-datatable" data-order='[[1,"desc"]]'>
                <thead>
                    <tr>
                        <th>Invoice #</th><th>Date</th><th>Customer</th><th>Cashier</th>
                        <th class="text-end">Subtotal</th><th class="text-end">Tax</th>
                        <th class="text-end">Discount</th><th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $s): ?>
                        <tr>
                            <td><a href="?page=sales&action=invoice&id=<?= $s['id'] ?>"><?= htmlspecialchars($s['invoice_number']) ?></a></td>
                            <td><?= dateFmt($s['created_at'], 'd M Y H:i') ?></td>
                            <td><?= htmlspecialchars($s['customer_name'] ?? 'Walk-in') ?></td>
                            <td><?= htmlspecialchars($s['user_name'] ?? '-') ?></td>
                            <td class="text-end"><?= money($s['subtotal']) ?></td>
                            <td class="text-end"><?= money($s['tax_amount']) ?></td>
                            <td class="text-end"><?= money($s['discount']) ?></td>
                            <td class="text-end"><strong><?= money($s['total']) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($byDate, 'day')) ?>,
            datasets: [{
                label: 'Sales (<?= CURRENCY ?>)',
                data: <?= json_encode(array_column($byDate, 'revenue')) ?>,
                backgroundColor: 'rgba(10, 77, 104, 0.7)',
                borderColor: '#0a4d68',
                borderWidth: 1
            }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });
});
</script>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
