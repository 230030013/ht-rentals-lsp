<?php
require_once 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$currentAdminId = (int)$_SESSION['admin_id'];

// ── POST Handler ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // [CREATE] Tambah admin baru   
    if ($_POST['action'] === 'add') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if ($username === '') {
            $message = "<div class='alert alert-danger'>Username tidak boleh kosong.</div>";
        } elseif (strlen($username) > 50) {
            $message = "<div class='alert alert-danger'>Username maksimal 50 karakter.</div>";
        } elseif (strlen($password) < 6) {
            $message = "<div class='alert alert-danger'>Password minimal 6 karakter.</div>";
        } elseif ($password !== $confirm) {
            $message = "<div class='alert alert-danger'>Konfirmasi password tidak cocok.</div>";
        } else {
            // Cek duplikat username
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = ?");
            $stmtCheck->execute([$username]);
            if ($stmtCheck->fetchColumn() > 0) {
                $message = "<div class='alert alert-danger'>Username <strong>" . htmlspecialchars($username) . "</strong> sudah digunakan.</div>";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt   = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
                if ($stmt->execute([$username, $hashed])) {
                    $message = "<div class='alert alert-success'>Admin <strong>" . htmlspecialchars($username) . "</strong> berhasil ditambahkan.</div>";
                } else {
                    $message = "<div class='alert alert-danger'>Gagal menambahkan admin.</div>";
                }
            }
        }

    // [UPDATE] Edit data admin
    } elseif ($_POST['action'] === 'edit') {
        $id       = (int)($_POST['admin_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if ($username === '') {
            $message = "<div class='alert alert-danger'>Username tidak boleh kosong.</div>";
        } elseif (strlen($username) > 50) {
            $message = "<div class='alert alert-danger'>Username maksimal 50 karakter.</div>";
        } elseif ($password !== '' && strlen($password) < 6) {
            $message = "<div class='alert alert-danger'>Password minimal 6 karakter.</div>";
        } elseif ($password !== '' && $password !== $confirm) {
            $message = "<div class='alert alert-danger'>Konfirmasi password tidak cocok.</div>";
        } else {
            // Cek duplikat username (exclude ID sendiri)
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = ? AND id != ?");
            $stmtCheck->execute([$username, $id]);
            if ($stmtCheck->fetchColumn() > 0) {
                $message = "<div class='alert alert-danger'>Username <strong>" . htmlspecialchars($username) . "</strong> sudah digunakan admin lain.</div>";
            } else {
                if ($password !== '') {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt   = $pdo->prepare("UPDATE admins SET username = ?, password = ? WHERE id = ?");
                    $ok     = $stmt->execute([$username, $hashed, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE admins SET username = ? WHERE id = ?");
                    $ok   = $stmt->execute([$username, $id]);
                }
                if ($ok) {
                    // Perbarui session jika admin mengedit akunnya sendiri
                    if ($id === $currentAdminId) {
                        $_SESSION['admin_username'] = $username;
                    }
                    $message = "<div class='alert alert-success'>Admin berhasil diperbarui.</div>";
                } else {
                    $message = "<div class='alert alert-danger'>Gagal memperbarui admin.</div>";
                }
            }
        }

    // [DELETE] Hapus admin
    } elseif ($_POST['action'] === 'delete') {
        $id = (int)($_POST['admin_id'] ?? 0);

        if ($id === $currentAdminId) {
            $message = "<div class='alert alert-danger'>Tidak dapat menghapus akun yang sedang digunakan.</div>";
        } else {
            // Cegah hapus admin terakhir
            $stmtCount = $pdo->query("SELECT COUNT(*) FROM admins");
            $totalAdmin = (int)$stmtCount->fetchColumn();

            if ($totalAdmin <= 1) {
                $message = "<div class='alert alert-danger'>Tidak dapat menghapus satu-satunya admin yang tersisa.</div>";
            } else {
                $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $message = "<div class='alert alert-success'>Admin berhasil dihapus.</div>";
                } else {
                    $message = "<div class='alert alert-danger'>Gagal menghapus admin.</div>";
                }
            }
        }
    }
}

// ── Ambil daftar semua admin ──────────────────────────────────────────────────
$admins = $pdo->query("SELECT id, username FROM admins ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<h2 style="margin-bottom: 1.5rem;">Manage Admins</h2>

<?= $message ?>

<!-- ── Form Tambah Admin ─────────────────────────────────────────────────────── -->
<div class="card">
    <h3 style="margin-top: 0;">Tambah Admin Baru</h3>
    <form method="POST" action="admin_manage.php">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
            <label for="new_username">Username</label>
            <input type="text" id="new_username" name="username" maxlength="50" required placeholder="Masukkan username">
        </div>
        <div class="form-group">
            <label for="new_password">Password</label>
            <input type="password" id="new_password" name="password" required placeholder="Minimal 6 karakter">
        </div>
        <div class="form-group">
            <label for="new_confirm">Konfirmasi Password</label>
            <input type="password" id="new_confirm" name="confirm_password" required placeholder="Ulangi password">
        </div>
        <button type="submit">Tambah Admin</button>
    </form>
</div>

<!-- ── Daftar Admin ─────────────────────────────────────────────────────────── -->
<div class="card">
    <h3 style="margin-top: 0;">Daftar Admin</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($admins as $admin): ?>
            <tr>
                <td><?= (int)$admin['id'] ?></td>
                <td><?= htmlspecialchars($admin['username']) ?></td>
                <td>
                    <?php if ((int)$admin['id'] === $currentAdminId): ?>
                        <span class="badge bg-success">Login Sekarang</span>
                    <?php endif; ?>
                </td>
                <td>
                    <!-- Tombol toggle form Edit -->
                    <button type="button"
                            onclick="toggleEdit(<?= (int)$admin['id'] ?>)"
                            style="margin-right: 0.5rem;">
                        Edit
                    </button>

                    <!-- Tombol Hapus (disable jika diri sendiri) -->
                    <?php if ((int)$admin['id'] !== $currentAdminId): ?>
                        <form method="POST" action="admin_manage.php" style="display:inline;"
                              onsubmit="return confirm('Hapus admin \'<?= htmlspecialchars($admin['username'], ENT_QUOTES) ?>\'?')">
                            <input type="hidden" name="action"   value="delete">
                            <input type="hidden" name="admin_id" value="<?= (int)$admin['id'] ?>">
                            <button type="submit" class="btn-danger">Hapus</button>
                        </form>
                    <?php else: ?>
                        <button type="button" class="btn-danger" disabled title="Tidak dapat menghapus akun sendiri">Hapus</button>
                    <?php endif; ?>
                </td>
            </tr>

            <!-- ── Inline Edit Form ─────────────────────────────────────────── -->
            <tr id="edit-row-<?= (int)$admin['id'] ?>" style="display:none; background:#f8fafc;">
                <td colspan="4" style="padding: 1rem 1.5rem;">
                    <form method="POST" action="admin_manage.php">
                        <input type="hidden" name="action"   value="edit">
                        <input type="hidden" name="admin_id" value="<?= (int)$admin['id'] ?>">
                        <div style="display:grid; grid-template-columns:1fr 1fr 1fr auto; gap:1rem; align-items:end;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Username Baru</label>
                                <input type="text" name="username" maxlength="50" required
                                       value="<?= htmlspecialchars($admin['username']) ?>">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Password Baru <small style="color:#64748b;">(kosongkan jika tidak ganti)</small></label>
                                <input type="password" name="password" placeholder="Minimal 6 karakter">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Konfirmasi Password</label>
                                <input type="password" name="confirm_password" placeholder="Ulangi password baru">
                            </div>
                            <div>
                                <button type="submit" class="btn-success">Simpan</button>
                                <button type="button" onclick="toggleEdit(<?= (int)$admin['id'] ?>)"
                                        style="background:#6b7280; margin-left:0.5rem;">Batal</button>
                            </div>
                        </div>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function toggleEdit(id) {
    var row = document.getElementById('edit-row-' + id);
    row.style.display = (row.style.display === 'none') ? 'table-row' : 'none';
}
</script>

<?php include 'includes/footer.php'; ?>
