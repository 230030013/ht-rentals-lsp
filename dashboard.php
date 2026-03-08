<?php
require_once 'config.php';

// Redirect ke login jika admin belum login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Hitung statistik ringkas untuk ditampilkan di kartu dashboard
// Jumlah rental yang masih aktif (belum dikembalikan)
$activeRentalsStmt = $pdo->query("SELECT COUNT(*) FROM rentals WHERE status = 'Active'");
$activeRentals = $activeRentalsStmt->fetchColumn();

// Total pemasukan dari seluruh transaksi yang pernah ada
$totalRevenueStmt = $pdo->query("SELECT SUM(total_price) FROM rentals");
$totalRevenue = $totalRevenueStmt->fetchColumn() ?: 0;

// Total seluruh stok HT yang dimiliki
$totalStockStmt = $pdo->query("SELECT SUM(stock_total) FROM items");
$totalStock = $totalStockStmt->fetchColumn() ?: 0;

// Ambil daftar rental aktif (yang sedang dipinjam) untuk ditampilkan di tabel
$recentRentals = $pdo->query("
    SELECT r.*, i.name as item_name 
    FROM rentals r 
    JOIN items i ON r.item_id = i.id 
    WHERE r.status = 'Active' 
    ORDER BY r.rent_date DESC
")->fetchAll();

require_once 'includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Active Rentals</h3>
        <p class="value"><?= number_format($activeRentals) ?> units</p>
    </div>
    <div class="stat-card">
        <h3>Total Revenue</h3>
        <p class="value">Rp <?= number_format($totalRevenue, 0, ',', '.') ?></p>
    </div>
    <div class="stat-card">
        <h3>Total HT Stock</h3>
        <p class="value"><?= number_format($totalStock) ?> units</p>
    </div>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Currently Rented Items</h2>
        <a href="rent.php" class="btn">Process New Rental</a>
    </div>
    
    <?php if (count($recentRentals) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Rent Date</th>
                    <th>Duration</th>
                    <th>Total Price</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentRentals as $rent): ?>
                <tr>
                    <td><?= htmlspecialchars($rent['customer_name']) ?></td>
                    <td><?= htmlspecialchars($rent['item_name']) ?></td>
                    <td><?= $rent['quantity'] ?></td>
                    <td><?= date('d M Y H:i', strtotime($rent['rent_date'])) ?></td>
                    <td><?= $rent['duration_days'] ?> Days</td>
                    <td>Rp <?= number_format($rent['total_price'], 0, ',', '.') ?></td>
                    <td>
                        <form method="POST" action="history.php" style="display:inline;">
                            <input type="hidden" name="action" value="return">
                            <input type="hidden" name="rental_id" value="<?= $rent['id'] ?>">
                            <button type="submit" class="btn btn-success" onclick="return confirm('Confirm return this item?')">Mark Returned</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="color: #666; text-align: center; padding: 20px;">No active rentals at the moment.</p>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
