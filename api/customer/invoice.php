<?php
// api/customer/invoice.php
// GET /api/customer/invoice?customerId=<cId>&itemCode=<code>
// Generates a PDF pledge token identical to the Node.js version

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/cors.php';
require_once __DIR__ . '/../../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError('Method not allowed', 405);

$customerId = $_GET['customerId'] ?? '';
$itemCode   = $_GET['itemCode']   ?? '';

if (!$customerId || !$itemCode) sendError('customerId and itemCode are required', 400);

$stmt = $pdo->prepare('SELECT * FROM customers WHERE customerId = ?');
$stmt->execute([$customerId]);
$c = $stmt->fetch();
if (!$c) sendError('Customer not found', 404);

$stmt = $pdo->prepare('SELECT * FROM customer_items WHERE customer_id = ? AND code = ?');
$stmt->execute([$c['id'], $itemCode]);
$item = $stmt->fetch();
if (!$item) sendError('Item not found', 404);

// ─── PDF Generation using FPDF (or TCPDF) ───────────────────────────────────
// We use raw HTML + browser print fallback if FPDF is unavailable.
// To use FPDF: composer require setasign/fpdf OR place fpdf.php in vendor/

$fpdfPath = __DIR__ . '/../../vendor/fpdf/fpdf.php';

if (!file_exists($fpdfPath)) {
    // Fallback: return JSON with all needed fields for client-side PDF generation
    sendResponse([
        'customer' => [
            'customerId'   => $c['customerId'],
            'name'         => $c['name'],
            'address'      => $c['address'],
            'aadhar_number'=> $c['aadhar_number'],
            'phone_number' => $c['phone_number'],
        ],
        'item' => [
            'code'       => $item['code'],
            'item'       => $item['item'],
            'loanAmount' => $item['loanAmount'],
            'interest'   => $item['interest'],
            'weight'     => $item['weight'],
        ],
        'message' => 'FPDF not installed. Install via: composer require setasign/fpdf'
    ]);
}

require_once $fpdfPath;

class PledgePDF extends FPDF {
    public string $shopHeader = '';
    public function Header() {} // custom drawing below
}

$pdf = new PledgePDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetMargins(10, 10, 10);

$loanDate = date('d/m/Y');
$dueDate  = date('d/m/Y', strtotime('+1 year'));

function drawToken(PledgePDF $pdf, float $yPos, array $c, array $item, string $loanDate, string $dueDate): void {
    $pw = 190;
    $pdf->SetXY(10, $yPos);
    $pdf->Rect(10, $yPos, $pw, 130);

    // Header text (Tamil text requires Unicode font – here we use ASCII fallback)
    $pdf->SetFont('Arial', 'B', 12);
    $header = "Vetri Bankers\n55/3/142(9) Kamarajar Nagar,\nKeelasurandai - 627 859\nPhone: +91 93614 06430";
    $pdf->MultiCell($pw, 5, $header, 0, 'C');

    // Left column
    $pdf->SetFont('Arial', '', 10);
    $leftY = $yPos + 35;
    $pdf->SetXY(12, $leftY);
    $pdf->Cell(0, 5, 'Amount: Rs.' . $item['loanAmount'], 0, 1);
    $pdf->SetX(12); $pdf->Cell(0, 5, 'Interest: ' . $item['interest'] . '% p.a', 0, 1);
    $pdf->SetX(12); $pdf->Cell(0, 5, 'Total Gold Ornaments: 1', 0, 1);

    // Right column
    $rx = 110;
    $ry = $yPos + 35;
    $details = [
        'Cust ID: ' . $c['customerId'],
        'Date of Loan: ' . $loanDate,
        'Due Date: ' . $dueDate,
        'Name: ' . $c['name'],
        'Address: ' . $c['address'],
        'Aadhaar: ' . $c['aadhar_number'],
        'Mobile: ' . $c['phone_number'],
    ];
    foreach ($details as $d) {
        $pdf->SetXY($rx, $ry);
        $pdf->Cell(88, 5, $d, 0, 1);
        $ry += 5;
    }

    // Signatures
    $pdf->SetXY(20, $yPos + 115);
    $pdf->Cell(0, 5, 'Authorized Signatory: ___________        Customer Signature: ___________', 0, 1);
}

drawToken($pdf, 10,  $c, $item, $loanDate, $dueDate);
drawToken($pdf, 148, $c, $item, $loanDate, $dueDate);

// Page 2 – Terms & Ledger
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 8, 'Vetri Bankers – Gold Loan Ledger', 0, 1, 'C');
$pdf->SetFont('Arial', '', 11);
$pdf->Ln(4);
$pdf->Cell(0, 6, 'Customer ID : ' . $c['customerId'],    0, 1);
$pdf->Cell(0, 6, 'Customer    : ' . $c['name'],          0, 1);
$pdf->Cell(0, 6, 'Item        : ' . $item['item'],       0, 1);
$pdf->Cell(0, 6, 'Loan Amount : Rs.' . $item['loanAmount'], 0, 1);
$pdf->Cell(0, 6, 'Interest    : ' . $item['interest'] . '% p.a', 0, 1);
$pdf->Cell(0, 6, 'Address     : ' . $c['address'],       0, 1);
$pdf->Cell(0, 6, 'Mobile      : ' . $c['phone_number'],  0, 1);
$pdf->Ln(6);

$terms = [
    '1. The pledged gold ornaments are my own property (confirmed with signature).',
    '2. Signature of the person redeeming / auction buyer.',
    '3. Signature of the pawnbroker / agent.',
    '4. I have verified and received back the pledged gold after repaying principal and interest.',
];
$pdf->SetFont('Arial', '', 10);
foreach ($terms as $t) {
    $pdf->MultiCell(0, 5, $t, 0, 'L');
    $pdf->Ln(2);
}
$pdf->Ln(10);
$pdf->Cell(90, 6, 'Customer Signature: __________', 0, 0);
$pdf->Cell(90, 6, 'Customer Signature: __________', 0, 1);
$pdf->Cell(90, 6, 'Date: _______________', 0, 0);
$pdf->Cell(90, 6, 'Date: _______________', 0, 1);

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename=pledge-token-' . $c['customerId'] . '-' . $item['code'] . '.pdf');
$pdf->Output('I', 'pledge-token.pdf');
