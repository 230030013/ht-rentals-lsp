<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HT Rental Admin</title>
    <style>
        :root {
            --primary: #3b82f6; --primary-hover: #2563eb;
            --bg: #f3f4f6; --text: #1f2937; --border: #e5e7eb;
        }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--bg); color: var(--text); margin: 0; }
        .navbar { background: #1e293b; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: white; text-decoration: none; margin-left: 1rem; }
        .navbar a:hover { color: #94a3b8; }
        .container { max-width: 1000px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 1.5rem; margin-bottom: 2rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 0.75rem; border-bottom: 1px solid var(--border); text-align: left; }
        th { background: #f8fafc; font-weight: 600; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 0.5rem; border: 1px solid var(--border); border-radius: 4px; box-sizing: border-box; }
        button, .btn { background: var(--primary); color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 0.9rem; }
        button:hover, .btn:hover { background: var(--primary-hover); }
        .btn-danger { background: #ef4444; } .btn-danger:hover { background: #dc2626; }
        .btn-success { background: #10b981; } .btn-success:hover { background: #059669; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 8px; border-left: 4px solid var(--primary); box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stat-card h3 { margin: 0 0 0.5rem 0; font-size: 0.9rem; color: #64748b; }
        .stat-card .value { font-size: 1.5rem; font-weight: bold; margin: 0; }
        .alert { padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
        .alert-success { background: #dcfce7; color: #166534; }
        .alert-danger { background: #fee2e2; color: #991b1b; }
        .badge { display: inline-block; padding: 0.25em 0.4em; font-size: 75%; font-weight: 700; border-radius: 0.25rem; color: #fff; text-align: center; white-space: nowrap; vertical-align: baseline; }
        .bg-success { background-color: #198754; }
        .bg-warning { background-color: #ffc107; color: #000; }
    </style>
</head>
<body>

<?php if (isset($_SESSION['admin_id'])): ?>
<nav class="navbar">
    <div style="font-weight: bold; font-size: 1.2rem;">HT Rental Admin</div>
    <div>
        <a href="dashboard.php">Dashboard</a>
        <a href="items.php">Manage HTs</a>
        <a href="rent.php">New Rental</a>
        <a href="history.php">History</a>
        <a href="admin_manage.php">Manage Admins</a>
        <a href="logout.php">Logout (<?= htmlspecialchars($_SESSION['admin_username']) ?>)</a>
    </div>
</nav>
<?php endif; ?>

<div class="container">
