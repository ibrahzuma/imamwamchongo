<?php $title = 'Reports'; include __DIR__ . '/../layouts/header.php'; ?>
<div class="container-fluid">
    <h3 class="mb-4"><i class="bi bi-bar-chart-line"></i> Reports</h3>
    <div class="row g-3">
        <div class="col-md-4">
            <a href="?page=reports&action=sales" class="text-decoration-none">
                <div class="card h-100 shadow-sm report-card">
                    <div class="card-body text-center p-4">
                        <i class="bi bi-cart-check display-3 text-primary"></i>
                        <h4 class="mt-3">Sales Report</h4>
                        <p class="text-muted">Daily, weekly, and monthly sales analytics with charts</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="?page=reports&action=inventory" class="text-decoration-none">
                <div class="card h-100 shadow-sm report-card">
                    <div class="card-body text-center p-4">
                        <i class="bi bi-boxes display-3 text-success"></i>
                        <h4 class="mt-3">Inventory Report</h4>
                        <p class="text-muted">Current stock levels and total inventory valuation</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="?page=reports&action=expiry" class="text-decoration-none">
                <div class="card h-100 shadow-sm report-card">
                    <div class="card-body text-center p-4">
                        <i class="bi bi-calendar-x display-3 text-danger"></i>
                        <h4 class="mt-3">Expiry Report</h4>
                        <p class="text-muted">Medicines nearing or past expiry date</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
<style>
.report-card { transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; }
.report-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.12) !important; }
</style>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
