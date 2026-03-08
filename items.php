<?php
require_once 'config.php';
use App\Models\HandyTalky;
use App\Storage\DatabaseStorage;

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$storage = new DatabaseStorage($pdo);

// Cek apakah ada request POST dari form (Tambah, Edit, Hapus)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        // [CREATE] Menambah HT Baru ke Database
        $name = htmlspecialchars($_POST['name']);
        $stock = (int)$_POST['stock'];
        $price = (float)$_POST['price'];

        if ($storage->save(['name' => $name, 'stock' => $stock, 'price' => $price])) {
            $message = "<div class='alert alert-success'>HT {$name} added successfully!</div>";
        }
    } elseif ($_POST['action'] === 'add_stock') {
        // [UPDATE] Menambah jumlah stok pada HT yang sudah ada
        $id = (int)$_POST['item_id'];
        $qty = (int)$_POST['add_qty'];
        
        $stmt = $pdo->prepare("UPDATE items SET stock_total = stock_total + ?, stock_available = stock_available + ? WHERE id = ?");
        if ($stmt->execute([$qty, $qty, $id])) {
            $message = "<div class='alert alert-success'>Stock updated successfully!</div>";
        }
    } elseif ($_POST['action'] === 'edit') {
        $id = (int)$_POST['item_id'];
        $name = htmlspecialchars($_POST['name']);
        $price = (float)$_POST['price'];
        
        $stmt = $pdo->prepare("UPDATE items SET name = ?, price_per_day = ? WHERE id = ?");
        if ($stmt->execute([$name, $price, $id])) {
            $message = "<div class='alert alert-success'>Item HT-{$id} updated successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Failed to update item.</div>";
        }
    } elseif ($_POST['action'] === 'delete') {
        // [DELETE] Menghapus HT dari Sistem
        $id = (int)$_POST['item_id'];
        
        // Mencegah penghapusan jika HT masih dipinjam/belum dikembalikan
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE item_id = ? AND status = 'Active'");
        $stmtCheck->execute([$id]);
        if ($stmtCheck->fetchColumn() > 0) {
            $message = "<div class='alert alert-danger'>Cannot delete this item as it is currently being rented out.</div>";
        } else {
            try {
                $pdo->beginTransaction();
                
                // Delete related rental history first to prevent Foreign Key constraint error
                $stmtDelRentals = $pdo->prepare("DELETE FROM rentals WHERE item_id = ?");
                $stmtDelRentals->execute([$id]);
                
                // Delete from items
                $stmtDel = $pdo->prepare("DELETE FROM items WHERE id = ?");
                $stmtDel->execute([$id]);
                
                $pdo->commit();
                
                $message = "<div class='alert alert-success'>Item deleted successfully!</div>";
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "<div class='alert alert-danger'>Server Error Failed to delete item: " . $e->getMessage() . "</div>";
            }
        }
    }
}

// [READ] Ambil semua data HT dari database dan ubah jadi Array Class (Hydration)
$rawItems = $storage->read();
$items = HandyTalky::hydrateCollection($rawItems);

// Cek apakah ada tombol Edit yang ditekan (via URL GET param: ?edit=1)
$editItem = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmtEdit = $pdo->prepare("SELECT * FROM items WHERE id = ?");
    $stmtEdit->execute([$editId]);
    $editItem = $stmtEdit->fetch();
}

require_once 'includes/header.php';
?>

<h2>Manage HT Inventory</h2>
<?= $message ?>

<?php if ($editItem): ?>
<div class="card" style="margin-bottom: 2rem; border: 2px solid #007bff;">
    <h3 style="color: #007bff;">Edit HT Type: HT-<?= str_pad($editItem['id'], 3, '0', STR_PAD_LEFT) ?></h3>
    <form method="POST" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 1rem; align-items: end;">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="item_id" value="<?= $editItem['id'] ?>">
        <div class="form-group" style="margin:0;">
            <label>HT Name/Model</label>
            <input type="text" name="name" required value="<?= htmlspecialchars($editItem['name']) ?>">
        </div>
        <div class="form-group" style="margin:0;">
            <label>Price/Day (Rp)</label>
            <input type="number" name="price" required min="1000" step="1000" value="<?= $editItem['price_per_day'] ?>">
        </div>
        <div>
            <button type="submit" class="btn btn-success" style="margin-bottom: 2px;">Save Changes</button>
            <a href="items.php" class="btn" style="background:#555; color:white; padding: 10px 15px; text-decoration: none; display:inline-block; margin-left: 5px;">Cancel</a>
        </div>
    </form>
</div>
<?php else: ?>
<div class="card" style="margin-bottom: 2rem;">
    <h3>Add New HT Type</h3>
    <form method="POST" style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 1rem; align-items: end;">
        <input type="hidden" name="action" value="add">
        <div class="form-group" style="margin:0;">
            <label>HT Name/Model</label>
            <input type="text" name="name" required placeholder="Baofeng UV-5R">
        </div>
        <div class="form-group" style="margin:0;">
            <label>Initial Stock</label>
            <input type="number" name="stock" required min="1" value="1">
        </div>
        <div class="form-group" style="margin:0;">
            <label>Price/Day (Rp)</label>
            <input type="number" name="price" required min="1000" step="1000" placeholder="25000">
        </div>
        <button type="submit" style="margin-bottom: 2px;">Add HT</button>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <h3>Current Inventory</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama HT</th>
                <th>Total Stock</th>
                <th>Available</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            /* @var $ht HandyTalky */
            foreach ($items as $ht): ?>
            <tr>
                <td>HT-<?= str_pad($ht->getId(), 3, '0', STR_PAD_LEFT) ?></td>
                <td><strong><?= htmlspecialchars($ht->getSummary()) ?></strong></td>
                <td><?= $ht->getStockAvailable() ?></td>
                <td>
                    <?php if($ht->getStockAvailable() > 0): ?>
                        <span class="badge bg-success"><?= $ht->getStockAvailable() ?> Ready</span>
                    <?php else: ?>
                        <span class="badge bg-warning">Empty</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <form method="POST" style="display: flex; gap: 0.5rem; margin: 0;">
                            <input type="hidden" name="action" value="add_stock">
                            <input type="hidden" name="item_id" value="<?= $ht->getId() ?>">
                            <input type="number" name="add_qty" value="0" min="1" style="width: 60px; padding: 0.2rem;" required>
                            <button type="submit" class="btn" style="padding: 0.2rem 0.5rem; font-size: 0.8rem; background: #28a745;">Add Stock</button>
                        </form>
                        <a href="?edit=<?= $ht->getId() ?>" class="btn" style="padding: 0.35rem 0.5rem; font-size: 0.8rem; background: #007bff; color: white; text-decoration: none;">Edit</a>
                        <form method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to delete this HT from inventory?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="item_id" value="<?= $ht->getId() ?>">
                            <button type="submit" class="btn btn-danger" style="padding: 0.2rem 0.5rem; font-size: 0.8rem;">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($items)): ?>
            <tr><td colspan="5" style="text-align: center;">No items found. Add one above.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>
