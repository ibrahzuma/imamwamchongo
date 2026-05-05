<?php $title = 'Expiry Report'; include __DIR__ . '/../layouts/header.php'; ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><i class="bi bi-calendar-x"></i> Expiry Report</h3>
        <div>
            <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer"></i> Print</button>
            <a href="?page=reports" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="page" value="reports">
                <input type="hidden" name="action" value="expiry">
                <div class="col-md-3">
                    <label class="form-label">Show items expiring within</label>
                    <select name="days" class="form-select" onchange="this.form.submit()">
                        <option value="30" <?= $days==30?'selected':'' ?>>30 days</option>
                        <option value="60" <?= $days==60?'selected':'' ?>>60 days</option>
                        <option value="90" <?= $days==90?'selected':'' ?>>90 days</option>
                        <option value="180" <?= $days==180?'selected':'' ?>>180 days</option>
                        <option value="365" <?= $days==365?'selected':'' ?>>1 year</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-light">
            <strong>Medicines Expiring Within <?= $days ?> Days</strong>
            <span class="badge bg-warning ms-2"><?= count($medicines) ?> items</span>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th><th>Medicine</th><th>Batch</th><th>Expiry Date</th>
                        <th class="text-end">Days Remaining</th><th class="text-end">Stock</th>
                        <th class="text-end">Stock Value</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($medicines)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-3">
                            <i class="bi bi-check-circle text-success"></i> No medicines expiring in this period
                        </td></tr>
                    <?php else: $i = 1; foreach ($medicines as $m):
                        $expiry = strtotime($m['expiry_date']);
                        $daysLeft = floor(($expiry - time()) / 86400);
                        $value = ($m['quantity'] ?? 0) * ($m['cost_price'] ?? 0);
                        if ($daysLeft < 0) { $cls = 'table-danger'; $badge = 'bg-danger'; $label = 'EXPIRED'; }
                        elseif ($daysLeft <= 30) { $cls = 'table-warning'; $badge = 'bg-warning text-dark'; $label = 'Critical'; }
                        else { $cls = ''; $badge = 'bg-info text-dark'; $label = 'Warning'; }
                    ?>
                        <tr class="<?= $cls ?>">
                            <td><?= $i++ ?></td>
                            <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
                            <td><?= htmlspecialchars($m['batch_number'] ?? '-') ?></td>
                            <td><?= dateFmt($m['expiry_date']) ?></td>
                            <td class="text-end"><?= $daysLeft ?> days</td>
                            <td class="text-end"><?= $m['quantity'] ?? 0 ?></td>
                            <td class="text-end"><?= money($value) ?></td>
                            <td><span class="badge <?= $badge ?>"><?= $label ?></span></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
