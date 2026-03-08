<?php
require_once 'config.php';
use App\Models\HandyTalky;

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tangkap data dari form submit
    $customer_name = htmlspecialchars($_POST['customer_name']);
    $duration_days = (int)$_POST['duration_days'];
    $quantities = $_POST['quantity'] ?? []; // Array data ID HT dan jumlah yang dipinjam
    
    // Fitur Tambahan (Aksesoris Tambahan) - Bukti Overloading OOP
    $accessoriesInput = $_POST['accessories'] ?? '';
    
    $total_rented = 0;
    
    try {
        // Mulai Transaksi Database (Bila gagal, semua kembali seperti semula)
        $pdo->beginTransaction();
        
        foreach ($quantities as $item_id => $quantity) {
            $quantity = (int)$quantity;
            if ($quantity <= 0) continue;
            
            // Validate stock and get price
            $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
            $stmt->execute([$item_id]);
            $itemRow = $stmt->fetch();

            if (!$itemRow || $itemRow['stock_available'] < $quantity) {
                throw new Exception("Not enough stock for ID HT-{$item_id}.");
            }

            // [OOP] Jadikan Data HT dari Database ke wujud Class Objek "HandyTalky"
            $ht = new HandyTalky($itemRow['id'], $itemRow['name'], $itemRow['price_per_day'], $itemRow['stock_available']);
            
            // Penggunaan Magic Methods Overloading
            if (!empty($accessoriesInput)) {
                $ht->addAccessory($accessoriesInput); // Takes string
            }
            $ht->addAccessory(['Charger Unit']); // Takes array
            
            // Hitung harga = jumlah qty * lama sewa * harga HT/hari
            $total_price = $quantity * $duration_days * $ht->getPricePerDay();
            $rent_date = date('Y-m-d H:i:s');
            
            // Simpan Data Peminjaman ke tabel rentals
            $stmtRent = $pdo->prepare("INSERT INTO rentals (customer_name, item_id, quantity, rent_date, duration_days, total_price) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtRent->execute([$customer_name, $ht->getId(), $quantity, $rent_date, $duration_days, $total_price]);
            
            // Deduct Stock
            $stmtUpdateStock = $pdo->prepare("UPDATE items SET stock_available = stock_available - ? WHERE id = ?");
            $stmtUpdateStock->execute([$quantity, $ht->getId()]);
            
            $total_rented++;
        }
        
        if ($total_rented > 0) {
            $pdo->commit(); $total_rented++;
            $message = "<div class='alert alert-success'>$total_rented Item(s) rented successfully! <a href='dashboard.php'>View Dashboard</a></div>";
        } else {
            $pdo->rollBack();
            $message = "<div class='alert alert-danger'>Please enter a valid quantity for at least one item.</div>";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "<div class='alert alert-danger'>Failed to process rental: " . $e->getMessage() . "</div>";
    }
}

// Fetch available items for dropdown (Using Arrays & Hydration)
$rows = $pdo->query("SELECT * FROM items WHERE stock_available > 0")->fetchAll();
$availableItems = HandyTalky::hydrateCollection($rows);

require_once 'includes/header.php';
?>

<h2>Process New Rental</h2>
<?= $message ?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <form method="POST">
        <div class="form-group">
            <label>Customer Name</label>
            <input type="text" name="customer_name" required placeholder="John Doe">
        </div>
        
        <div class="form-group">
            <label>Duration (Days)</label>
            <input type="number" name="duration_days" required min="1" value="1">
        </div>
        
        <div class="form-group">
            <label>Extra Accessories for all rented HT (Optional)</label>
            <input type="text" name="accessories" placeholder="Headset, Extra Antenna">
        </div>

        <h3 style="margin-top: 2rem;">Select Items to Rent</h3>
        <?php if (empty($availableItems)): ?>
            <div class='alert alert-danger'>No hardware currently available in inventory. Please add stock first.</div>
        <?php else: ?>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
                <thead>
                    <tr>
                        <th style="padding: 10px; border-bottom: 2px solid #ddd; text-align: left;">HT Model</th>
                        <th style="padding: 10px; border-bottom: 2px solid #ddd; text-align: center;">Available Stock</th>
                        <th style="padding: 10px; border-bottom: 2px solid #ddd; text-align: center;">Rent Qty</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($availableItems as $item): /* @var $item HandyTalky */ ?>
                    <tr>
                        <td style="padding: 10px; border-bottom: 1px solid #ddd;">
                            <strong><?= htmlspecialchars($item->getSummary()) ?></strong>
                        </td>
                        <td style="padding: 10px; border-bottom: 1px solid #ddd; text-align: center;">
                            <span class="badge bg-success"><?= $item->getStockAvailable() ?> Ready</span>
                        </td>
                        <td style="padding: 10px; border-bottom: 1px solid #ddd; text-align: center;">
                            <input type="number" name="quantity[<?= $item->getId() ?>]" min="0" max="<?= $item->getStockAvailable() ?>" value="0" style="width: 80px; text-align: center; padding: 5px;">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <button type="submit" style="width: 100%; font-size: 1.1rem; padding: 0.75rem;">
                Process Rental
            </button>
        <?php endif; ?>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
