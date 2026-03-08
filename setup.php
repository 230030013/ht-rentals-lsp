<?php
// setup.php
require_once 'config.php';

try {
    // Admin table
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL
    )");

    // Items table
    $pdo->exec("CREATE TABLE IF NOT EXISTS items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        stock_total INT NOT NULL DEFAULT 0,
        stock_available INT NOT NULL DEFAULT 0,
        price_per_day DECIMAL(10,2) NOT NULL DEFAULT 0.00
    )");

    // Rentals table
    $pdo->exec("CREATE TABLE IF NOT EXISTS rentals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_name VARCHAR(100) NOT NULL,
        item_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        rent_date DATETIME NOT NULL,
        duration_days INT NOT NULL DEFAULT 1,
        total_price DECIMAL(10,2) NOT NULL,
        status ENUM('Active', 'Returned') DEFAULT 'Active',
        returned_date DATETIME NULL,
        FOREIGN KEY (item_id) REFERENCES items(id)
    )");

    // Insert default admin 'admin'/'admin123' if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO admins (username, password) VALUES ('admin', '$hashedPassword')");
        echo "Default admin created.<br>";
    }

    echo "Setup successfully completed. Tables created.";
} catch (PDOException $e) {
    die("Setup failed: " . $e->getMessage());
}
?>
