<?php $pageTitle = 'Dashboard'; require __DIR__ . '/../layouts/header.php'; ?>

<h3 class="mb-4"><i class="bi bi-speedometer2"></i> Dashboard</h3>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card kpi-card success">
            <div class="card-body">
                <div class="kpi-label">Today's Sales</div>
                <div class="kpi-value"><?= money($todaySales['total']) ?></div>
                <small class="text-muted"><?= (int)$todaySales['cnt'] ?> transactions</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card kpi-card info">
            <div class="card-body">
                <div class="kpi-label">This Week</div>
                <div class="kpi-value"><?= money($weekSales) ?></div>
                <small class="text-muted">Last 7 days</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card kpi-card warning">
            <div class="card-body">
                <div class="kpi-label">This Month</div>
                <div class="kpi-value"><?= money($monthSales) ?></div>
                <small class="text-muted"><?= date('F Y') ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card kpi-card">
            <div class="card-body">
                <div class="kpi-label">Inventory Value</div>
                <div class="kpi-value"><?= money($inventoryValue) ?></div>
                <small class="text-muted"><?= $totalMedicines ?> medicines · <?= $totalSuppliers ?> suppliers</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Sales Chart -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-graph-up"></i> Sales — Last 7 Days
            </div>
            <div class="card-body">
                <canvas id="salesChart" height="100"></canvas>
            </div>
        </div>
    </div>

    <!-- Low Stock Alerts -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header text-danger">
                <i class="bi bi-exclamation-triangle"></i> Low Stock Alerts
            </div>
            <div class="card-body p-0">
                <?php if (empty($lowStock)): ?>
                    <p class="text-muted p-3 mb-0">No low-stock items. </p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($lowStock as $m): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?= sanitize($m['name']) ?></span>
                                <span class="badge bg-danger rounded-pill"><?= (int)$m['quantity'] ?> left</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Expiring Soon -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header text-warning">
                <i class="bi bi-calendar-x"></i> Expiring within 60 days
            </div>
            <div class="card-body p-0">
                <?php if (empty($expiringSoon)): ?>
                    <p class="text-muted p-3 mb-0">Nothing expiring soon.</p>
                <?php else: ?>
                    <table class="table mb-0">
                        <thead><tr><th>Medicine</th><th>Batch</th><th>Expiry</th></tr></thead>
                        <tbody>
                        <?php foreach (array_slice($expiringSoon, 0, 6) as $m): ?>
                            <tr>
                                <td><?= sanitize($m['name']) ?></td>
                                <td><small><?= sanitize($m['batch_number'] ?? '-') ?></small></td>
                                <td><span class="badge bg-warning text-dark"><?= dateFmt($m['expiry_date']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Sales -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-receipt"></i> Recent Sales</div>
            <div class="card-body p-0">
                <?php if (empty($recentSales)): ?>
                    <p class="text-muted p-3 mb-0">No sales yet.</p>
                <?php else: ?>
                    <table class="table mb-0">
                        <thead><tr><th>Invoice</th><th>Cashier</th><th>Total</th><th>Time</th></tr></thead>
                        <tbody>
                        <?php foreach ($recentSales as $s): ?>
                            <tr>
                                <td>
                                    <a href="<?= url('index.php?page=sales&action=invoice&id=' . $s['id']) ?>">
                                        <?= sanitize($s['invoice_number']) ?>
                                    </a>
                                </td>
                                <td><?= sanitize($s['user_name']) ?></td>
                                <td><strong><?= money($s['total']) ?></strong></td>
                                <td><small><?= dateFmt($s['created_at'], 'M j, H:i') ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('salesChart');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                label: 'Daily Sales (<?= CURRENCY ?>)',
                data: <?= json_encode($chartData) ?>,
                borderColor: '#088395',
                backgroundColor: 'rgba(8,131,149,0.15)',
                tension: 0.35,
                fill: true,
                borderWidth: 3,
                pointRadius: 5,
                pointBackgroundColor: '#0a4d68'
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
