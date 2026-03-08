<?php
require_once 'config.php';

// Redirect ke login jika belum login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';

// Proses form kembalikan barang jika ada POST dengan action='return'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'return') {
    $rental_id = (int)$_POST['rental_id'];
    
    // Ambil detail rental yang ingin dikembalikan (hanya yang masih Active)
    $stmtRent = $pdo->prepare("SELECT r.*, i.name as item_name FROM rentals r JOIN items i ON r.item_id = i.id WHERE r.id = ? AND r.status = 'Active'");
    $stmtRent->execute([$rental_id]);
    $rental = $stmtRent->fetch();
    
    if ($rental) {
        try {
            // Mulai transaksi: update status dan kembalikan stok harus bersamaan
            $pdo->beginTransaction();
            
            // Ubah status rental menjadi 'Returned' dan catat tanggal pengembalian
            $returned_date = date('Y-m-d H:i:s');
            $stmtUpdateRental = $pdo->prepare("UPDATE rentals SET status = 'Returned', returned_date = ? WHERE id = ?");
            $stmtUpdateRental->execute([$returned_date, $rental_id]);
            
            // Kembalikan stok +qty ke master data items
            $stmtUpdateStock = $pdo->prepare("UPDATE items SET stock_available = stock_available + ? WHERE id = ?");
            $stmtUpdateStock->execute([$rental['quantity'], $rental['item_id']]);
            
            $pdo->commit();
            
            $message = "<div class='alert alert-success'>Item marked as returned and stock restored.</div>";
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "<div class='alert alert-danger'>Failed to process return.</div>";
        }
    }
}

// Fetch all database history
$history = $pdo->query("
    SELECT r.*, i.name as item_name 
    FROM rentals r 
    JOIN items i ON r.item_id = i.id 
    ORDER BY r.rent_date DESC
")->fetchAll();

require_once 'includes/header.php';
?>

<h2>Rental History & Returns</h2>
<div style="margin-bottom: 20px;">
    <a href="export_pdf.php" target="_blank" class="btn btn-success" style="padding: 10px 15px; text-decoration: none; display: inline-block;">
        Export Laporan PDF
    </a>
</div>
<?= $message ?>

<div class="card" style="margin-bottom: 20px;">
    <h3>Active & Past Rentals (Database)</h3>
    <table>
        <thead>
            <tr>
                <th>Status</th>
                <th>Customer Name</th>
                <th>Item Rented</th>
                <th>Qty</th>
                <th>Rent Date</th>
                <th>Duration</th>
                <th>Total Price</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($history as $rent): ?>
            <tr>
                <td>
                    <?php if ($rent['status'] === 'Active'): ?>
                        <span class="badge bg-warning">Active</span>
                    <?php else: ?>
                        <span class="badge bg-success">Returned</span><br>
                        <small style="color: #666; font-size: 0.8em;"><?= date('d M Y', strtotime($rent['returned_date'])) ?></small>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($rent['customer_name']) ?></td>
                <td><?= htmlspecialchars($rent['item_name']) ?></td>
                <td><?= $rent['quantity'] ?></td>
                <td><?= date('d M Y H:i', strtotime($rent['rent_date'])) ?></td>
                <td><?= $rent['duration_days'] ?> Days</td>
                <td>Rp <?= number_format($rent['total_price'], 0, ',', '.') ?></td>
                <td>
                    <?php if ($rent['status'] === 'Active'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="return">
                            <input type="hidden" name="rental_id" value="<?= $rent['id'] ?>">
                            <button type="submit" class="btn btn-success" style="font-size: 0.8rem; padding: 0.25rem 0.5rem;" onclick="return confirm('Confirm return this item?')">Process Return</button>
                        </form>
                    <?php else: ?>
                        <span style="color: #aaa; font-size: 0.9em;">Completed</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($history)): ?>
            <tr><td colspan="8" style="text-align: center;">No rental history found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>
