<?php
// Include FPDF library (assuming it's available, e.g., at ./fpdf/fpdf.php)
// If not, download FPDF from http://www.fpdf.org/ and place it accordingly
require('fpdf/fpdf.php'); // Adjust path if necessary

// Sample student data (in a real scenario, retrieve from database or session)
$studentName = 'John Doe';
$studentID = '12345678';
$logoPath = 'logo.png'; // Assume you have a logo image

// CR80 size: 85.6mm x 54mm
$cardWidth = 85.6;
$cardHeight = 54;

// Create new PDF instance in Landscape orientation
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetAutoPageBreak(false);

// Page dimensions
$pageWidth = 297;
$pageHeight = 210;

// Center positions for front and back cards
$centerX = $pageWidth / 2;
$topY = 30;
$bottomY = $pageHeight / 2 + 30;

// Front card position (top)
$frontX = $centerX - ($cardWidth / 2);
$frontY = $topY;

// Back card position (bottom)
$backX = $centerX - ($cardWidth / 2);
$backY = $bottomY;

// FRONT ID: Place template image as background
$pdf->Image($frontImagePath, $frontX, $frontY, $cardWidth, $cardHeight);

// Add logo (assume empty square at top-left, 20mm x 20mm square at 10mm from card left/top)
$logoSize = 20;
$logoMargin = 10;
$logoPosX = $frontX + $logoMargin;
$logoPosY = $frontY + $logoMargin;
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, $logoPosX, $logoPosY, $logoSize, $logoSize);
}

// Add student name below logo (below the square, at 35mm from card top, left-aligned)
$nameY = $frontY + $logoMargin + $logoSize + 5; // 5mm spacing below logo
$pdf->SetXY($frontX + $logoMargin, $nameY);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, $studentName);

// Add student ID below name (another 15mm down)
$idY = $nameY + 15;
$pdf->SetXY($frontX + $logoMargin, $idY);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 10, 'ID: ' . $studentID);

// You can add more elements like photo if positions are defined, e.g.:
// $photoSize = 30;
// $photoPosX = $frontX + $cardWidth - $photoSize - 10; // Right side
// $photoPosY = $frontY + 10;
// $pdf->Image('photo.jpg', $photoPosX, $photoPosY, $photoSize, $photoSize); // Assuming photo.jpg exists

// BACK ID: Place template image as background
$pdf->Image($backImagePath, $backX, $backY, $cardWidth, $cardHeight);

// For back, perhaps similar or different placeholders. Assuming same for now, but you can customize.
// E.g., add text for "Valid Until: 2025-12-31" or something.

// Example: Below logo position on back.
$backLogoPosX = $backX + $logoMargin;
$backLogoPosY = $backY + $logoMargin;
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, $backLogoPosX, $backLogoPosY, $logoSize, $logoSize);
}

// Add some back info below.
$backTextY = $backY + $logoMargin + $logoSize + 5;
$pdf->SetXY($backX + $logoMargin, $backTextY);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 10, 'Back of ID Card');

// To make it dynamic, you can add more text or fields based on needs.

// Output the PDF as download
$filename = $studentID . '_ID.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
$pdf->Output($filename, 'D');
?>
