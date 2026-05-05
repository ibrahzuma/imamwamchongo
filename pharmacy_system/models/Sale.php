<?php
/**
 * Sale Model — invoice & POS engine. Tenant-scoped.
 */
class Sale {
    private $db;
    private $pharmacyId;

    public function __construct($db, $pharmacyId = null) {
        $this->db         = $db;
        $this->pharmacyId = $pharmacyId === null ? null : (int)$pharmacyId;
    }

    private function tenantClause($alias = 's') {
        return $this->pharmacyId === null ? '' : " AND $alias.pharmacy_id = " . $this->pharmacyId;
    }

    /**
     * Create a sale + items + decrement stock atomically. Stamps pharmacy_id.
     */
    public function create($data, $items) {
        if ($this->pharmacyId === null) {
            throw new Exception('Cannot create a sale without a pharmacy context.');
        }
        if (empty($items)) {
            throw new Exception('Cannot create a sale with no items.');
        }
        $this->db->beginTransaction();
        try {
            $subtotal = 0;
            foreach ($items as &$it) {
                $it['quantity']   = (int)$it['quantity'];
                $it['unit_price'] = (float)$it['unit_price'];
                $it['subtotal']   = $it['quantity'] * $it['unit_price'];
                $subtotal += $it['subtotal'];

                // Lock the row AND verify tenant ownership in the same query
                $stmt = $this->db->prepare("SELECT quantity, name FROM medicines WHERE id = ? AND pharmacy_id = ? FOR UPDATE");
                $stmt->execute([$it['medicine_id'], $this->pharmacyId]);
                $med = $stmt->fetch();
                if (!$med) throw new Exception("Medicine ID {$it['medicine_id']} not found in this pharmacy");
                if ($med['quantity'] < $it['quantity']) {
                    throw new Exception("Insufficient stock for {$med['name']} (available: {$med['quantity']})");
                }
            }
            unset($it);

            $taxRate    = (float)($data['tax_rate'] ?? 0);
            $discount   = (float)($data['discount'] ?? 0);
            $taxableAmt = max(0, $subtotal - $discount);
            $taxAmount  = round($taxableAmt * ($taxRate / 100), 2);
            $total      = round($taxableAmt + $taxAmount, 2);

            $invoice = generateInvoiceNumber();

            $stmt = $this->db->prepare("
                INSERT INTO sales (pharmacy_id, invoice_number, branch_id, user_id, customer_name, customer_phone,
                                   subtotal, tax_rate, tax_amount, discount, total, paid, payment_method, status, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', ?)
            ");
            $stmt->execute([
                $this->pharmacyId,
                $invoice,
                !empty($data['branch_id']) ? (int)$data['branch_id'] : null,
                $data['user_id'],
                $data['customer_name']  ?? null,
                $data['customer_phone'] ?? null,
                $subtotal,
                $taxRate,
                $taxAmount,
                $discount,
                $total,
                (float)($data['paid'] ?? $total),
                $data['payment_method'] ?? 'cash',
                $data['notes'] ?? null,
            ]);
            $saleId = (int)$this->db->lastInsertId();

            $itemStmt = $this->db->prepare("
                INSERT INTO sale_items (sale_id, medicine_id, quantity, unit_price, subtotal)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stockStmt = $this->db->prepare("UPDATE medicines SET quantity = quantity - ? WHERE id = ?");
            $movStmt   = $this->db->prepare("
                INSERT INTO stock_movements (pharmacy_id, medicine_id, movement_type, quantity, reference_type, reference_id, notes, user_id)
                VALUES (?, ?, 'out', ?, 'sale', ?, ?, ?)
            ");
            foreach ($items as $it) {
                $itemStmt->execute([$saleId, $it['medicine_id'], $it['quantity'], $it['unit_price'], $it['subtotal']]);
                $stockStmt->execute([$it['quantity'], $it['medicine_id']]);
                $movStmt->execute([$this->pharmacyId, $it['medicine_id'], $it['quantity'], $saleId, "Sale $invoice", $data['user_id']]);
            }

            $this->db->commit();
            return ['id' => $saleId, 'invoice_number' => $invoice, 'total' => $total];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function find($id) {
        $sql = "SELECT s.*, u.full_name AS user_name, b.name AS branch_name
                FROM sales s
                JOIN users u ON u.id = s.user_id
                LEFT JOIN branches b ON b.id = s.branch_id
                WHERE s.id = ?" . $this->tenantClause('s');
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function items($saleId) {
        // sale_items has no direct pharmacy_id; scope through the parent sale
        $sql = "SELECT si.*, m.name AS medicine_name, m.unit
                FROM sale_items si
                JOIN sales s     ON s.id = si.sale_id
                JOIN medicines m ON m.id = si.medicine_id
                WHERE si.sale_id = ?" . $this->tenantClause('s');
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$saleId]);
        return $stmt->fetchAll();
    }

    public function recent($limit = 10) {
        $sql = "SELECT s.*, u.full_name AS user_name
                FROM sales s
                JOIN users u ON u.id = s.user_id
                WHERE 1=1" . $this->tenantClause('s') . "
                ORDER BY s.created_at DESC LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function byDateRange($from, $to, $userId = null) {
        $sql = "SELECT s.*, u.full_name AS user_name
                FROM sales s
                JOIN users u ON u.id = s.user_id
                WHERE DATE(s.created_at) BETWEEN ? AND ?
                  AND s.status = 'completed'" . $this->tenantClause('s');
        $params = [$from, $to];
        if ($userId) {
            $sql .= " AND s.user_id = ?";
            $params[] = (int)$userId;
        }
        $sql .= " ORDER BY s.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function totalsByDate($from, $to, $userId = null) {
        $sql = "SELECT DATE(created_at) AS day, COUNT(*) AS num_sales, SUM(total) AS revenue
                FROM sales
                WHERE DATE(created_at) BETWEEN ? AND ? AND status='completed'"
             . $this->tenantClause('sales');
        $params = [$from, $to];
        if ($userId) {
            $sql .= " AND user_id = ?";
            $params[] = (int)$userId;
        }
        $sql .= " GROUP BY DATE(created_at) ORDER BY day ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
