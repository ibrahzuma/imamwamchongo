<?php $title = 'Purchase Details'; include __DIR__ . '/../layouts/header.php'; ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><i class="bi bi-receipt"></i> Purchase #<?= htmlspecialchars($purchase['reference_number']) ?></h3>
        <div>
            <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer"></i> Print</button>
            <a href="?page=purchases" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>Supplier</h5>
                    <p class="mb-1"><strong><?= htmlspecialchars($purchase['supplier_name']) ?></strong></p>
                    <p class="mb-1"><?= htmlspecialchars($purchase['contact_person'] ?? '') ?></p>
                    <p class="mb-1"><?= htmlspecialchars($purchase['supplier_phone'] ?? '') ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h5>Details</h5>
                    <p class="mb-1"><strong>Purchase #:</strong> <?= htmlspecialchars($purchase['reference_number']) ?></p>
                    <p class="mb-1"><strong>Date:</strong> <?= dateFmt($purchase['created_at']) ?></p>
                    <p class="mb-1"><strong>Created by:</strong> <?= htmlspecialchars($purchase['user_name'] ?? '-') ?></p>
                    <p class="mb-1"><strong>Status:</strong>
                        <span class="badge bg-success"><?= htmlspecialchars($purchase['status']) ?></span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-light"><strong>Items Purchased</strong></div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>#</th><th>Medicine</th><th>Batch</th><th>Expiry</th>
                        <th class="text-end">Qty</th><th class="text-end">Cost Price</th><th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($items as $it): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($it['medicine_name'] ?? $it['name']) ?></td>
                            <td><?= htmlspecialchars($it['batch_number'] ?? '-') ?></td>
                            <td><?= $it['expiry_date'] ? dateFmt($it['expiry_date']) : '-' ?></td>
                            <td class="text-end"><?= $it['quantity'] ?></td>
                            <td class="text-end"><?= money($it['unit_cost']) ?></td>
                            <td class="text-end"><?= money($it['subtotal']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light">
                        <th colspan="6" class="text-end">Total:</th>
                        <th class="text-end"><?= money($purchase['total']) ?></th>
                    </tr>
                </tfoot>
            </table>

            <?php if (!empty($purchase['notes'])): ?>
                <div class="mt-3"><strong>Notes:</strong> <?= htmlspecialchars($purchase['notes']) ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
