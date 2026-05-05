<?php
/**
 * Tiny TCPDF wrapper that gives every report a consistent header/footer
 * branded with the current pharmacy's name.
 */
require_once __DIR__ . '/tcpdf_loader.php';

class PdfReport extends TCPDF {
    public $reportTitle  = '';
    public $pharmacyName = '';
    public $dateRange    = '';

    public function Header() {
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 8, $this->pharmacyName ?: 'Pharmacy', 0, 1, 'L');

        $this->SetFont('helvetica', '', 10);
        $this->Cell(0, 6, $this->reportTitle, 0, 1, 'L');

        if ($this->dateRange !== '') {
            $this->SetFont('helvetica', 'I', 9);
            $this->SetTextColor(100, 100, 100);
            $this->Cell(0, 5, $this->dateRange, 0, 1, 'L');
            $this->SetTextColor(0, 0, 0);
        }

        // separator line
        $this->Ln(1);
        $this->SetDrawColor(180, 180, 180);
        $this->Line($this->GetX(), $this->GetY(), $this->getPageWidth() - $this->getMargins()['right'], $this->GetY());
        $this->Ln(3);
    }

    public function Footer() {
        $this->SetY(-12);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 6,
            'Generated ' . date('Y-m-d H:i') . '  ·  Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(),
            0, 0, 'C');
    }

    public static function make($title, $dateRange = '') {
        $pdf = new self('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->reportTitle  = $title;
        $pdf->pharmacyName = $_SESSION['pharmacy_name'] ?? 'Pharmacy';
        $pdf->dateRange    = $dateRange;
        $pdf->SetCreator('PharmaCare Plus');
        $pdf->SetAuthor($pdf->pharmacyName);
        $pdf->SetTitle($title);
        $pdf->SetMargins(12, 28, 12);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();
        return $pdf;
    }
}
