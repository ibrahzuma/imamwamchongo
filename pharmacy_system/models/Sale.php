<?php
/**
 * Sale Model — invoice & POS engine.
 */
class Sale {
    private $db;
    public function __construct($db) { $this->db = $db; }

    /**
     * Create a sale record + items + adjust stock atomically.
     *
     * @param array $data    [user_id, branch_id, customer_name, ...]
     * @param array $items   [['medicine_id','quantity','unit_price'], ...]
     */
    public function create($data, $items) {
        if (empty($items)) {
            throw new Exception('Cannot create a sale with no items.');
        }
        $this->db->beginTransaction();
        try {
            // Calculate totals
            $subtotal = 0;
            foreach ($items as &$it) {
                $it['quantity']   = (int)$it['quantity'];
                $it['unit_price'] = (float)$it['unit_price'];
                $it['subtotal']   = $it['quantity'] * $it['unit_price'];
                $subtotal += $it['subtotal'];

                // Verify stock availability
                $stmt = $this->db->prepare("SELECT quantity, name FROM medicines WHERE id = ? FOR UPDATE");
                $stmt->execute([$it['medicine_id']]);
                $med = $stmt->fetch();
                if (!$med) throw new Exception("Medicine ID {$it['medicine_id']} not found");
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
                INSERT INTO sales (invoice_number, branch_id, user_id, customer_name, customer_phone,
                                   subtotal, tax_rate, tax_amount, discount, total, paid, payment_method, status, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', ?)
            ");
            $stmt->execute([
                $invoice,
                $data['branch_id'] ?? 1,
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

            // Insert items + decrement stock + log movement
            $itemStmt = $this->db->prepare("
                INSERT INTO sale_items (sale_id, medicine_id, quantity, unit_price, subtotal)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stockStmt = $this->db->prepare("UPDATE medicines SET quantity = quantity - ? WHERE id = ?");
            $movStmt   = $this->db->prepare("
                INSERT INTO stock_movements (medicine_id, movement_type, quantity, reference_type, reference_id, notes, user_id)
                VALUES (?, 'out', ?, 'sale', ?, ?, ?)
            ");
            foreach ($items as $it) {
                $itemStmt->execute([$saleId, $it['medicine_id'], $it['quantity'], $it['unit_price'], $it['subtotal']]);
                $stockStmt->execute([$it['quantity'], $it['medicine_id']]);
                $movStmt->execute([$it['medicine_id'], $it['quantity'], $saleId, "Sale $invoice", $data['user_id']]);
            }

            $this->db->commit();
            return ['id' => $saleId, 'invoice_number' => $invoice, 'total' => $total];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function find($id) {
        $stmt = $this->db->prepare("
            SELECT s.*, u.full_name AS user_name, b.name AS branch_name
            FROM sales s
            JOIN users u ON u.id = s.user_id
            LEFT JOIN branches b ON b.id = s.branch_id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function items($saleId) {
        $stmt = $this->db->prepare("
            SELECT si.*, m.name AS medicine_name, m.unit
            FROM sale_items si
            JOIN medicines m ON m.id = si.medicine_id
            WHERE si.sale_id = ?
        ");
        $stmt->execute([$saleId]);
        return $stmt->fetchAll();
    }

    public function recent($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT s.*, u.full_name AS user_name
            FROM sales s
            JOIN users u ON u.id = s.user_id
            ORDER BY s.created_at DESC LIMIT ?
        ");
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function byDateRange($from, $to) {
        $stmt = $this->db->prepare("
            SELECT s.*, u.full_name AS user_name
            FROM sales s
            JOIN users u ON u.id = s.user_id
            WHERE DATE(s.created_at) BETWEEN ? AND ?
              AND s.status = 'completed'
            ORDER BY s.created_at DESC
        ");
        $stmt->execute([$from, $to]);
        return $stmt->fetchAll();
    }

    public function totalsByDate($from, $to) {
        $stmt = $this->db->prepare("
            SELECT DATE(created_at) AS day, COUNT(*) AS num_sales, SUM(total) AS revenue
            FROM sales
            WHERE DATE(created_at) BETWEEN ? AND ? AND status='completed'
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ");
        $stmt->execute([$from, $to]);
        return $stmt->fetchAll();
    }
}
