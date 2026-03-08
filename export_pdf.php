<?php
require_once 'config.php';
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch all database history
$history = $pdo->query("
    SELECT r.*, i.name as item_name 
    FROM rentals r 
    JOIN items i ON r.item_id = i.id 
    ORDER BY r.rent_date DESC
")->fetchAll();

$html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laporan Riwayat Penyewaan HT</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <h2>Laporan Riwayat Penyewaan HT</h2>
    <p>Tanggal Cetak: ' . date('d M Y H:i') . '</p>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Status</th>
                <th>Pelanggan</th>
                <th>Item HT</th>
                <th>Qty</th>
                <th>Tgl Sewa</th>
                <th>Tgl Kembali</th>
                <th>Total Harga</th>
            </tr>
        </thead>
        <tbody>';

$no = 1;
$totalPendapatan = 0;
foreach ($history as $rent) {
    if ($rent['status'] === 'Returned') {
        $totalPendapatan += $rent['total_price'];
    }
    
    $returnDate = $rent['status'] === 'Returned' ? date('d M Y H:i', strtotime($rent['returned_date'])) : '-';
    $statusText = $rent['status'] === 'Active' ? 'Dipinjam' : 'Selesai';
    
    $html .= '
            <tr>
                <td class="text-center">' . $no++ . '</td>
                <td>' . $statusText . '</td>
                <td>' . htmlspecialchars($rent['customer_name']) . '</td>
                <td>' . htmlspecialchars($rent['item_name']) . '</td>
                <td class="text-center">' . $rent['quantity'] . '</td>
                <td>' . date('d M Y H:i', strtotime($rent['rent_date'])) . '</td>
                <td>' . $returnDate . '</td>
                <td>Rp ' . number_format($rent['total_price'], 0, ',', '.') . '</td>
            </tr>';
}

$html .= '
            <tr>
                <th colspan="7" style="text-align: right;">Total Pendapatan (Selesai):</th>
                <th>Rp ' . number_format($totalPendapatan, 0, ',', '.') . '</th>
            </tr>
        </tbody>
    </table>
</body>
</html>';

// Setup DOMPDF
$options = new Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Output the generated PDF to Browser
$dompdf->stream("Laporan_Penyewaan_HT_" . date('Ymd') . ".pdf", ["Attachment" => true]);
exit();
