<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$isVercel = getenv('VERCEL') === '1';
if (!$isVercel && !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(404);
    exit;
}

$lockFile = __DIR__ . '/.shewa-installed';
$error = '';
$complete = !$isVercel && is_file($lockFile);

if ($isVercel) {
    try {
        $tableExists = db()->query("SHOW TABLES LIKE 'users'")->fetchColumn() !== false;
        $complete = $tableExists
            && (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn() > 0;
    } catch (Throwable $exception) {
        error_log('SHEWA setup status error: ' . $exception->getMessage());
        $error = 'Cannot reach the configured MySQL database. Check your Vercel database environment variables.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$complete) {
    verify_csrf();

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $setupSecret = getenv('SHEWA_SETUP_SECRET');
    $providedSetupSecret = (string) ($_POST['setup_secret'] ?? '');

    if ($isVercel && ($setupSecret === false || $setupSecret === '' || !hash_equals($setupSecret, $providedSetupSecret))) {
        $error = 'Enter the setup secret configured for this Vercel deployment.';
    } elseif ($name === '' || strlen($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $error = 'Enter a name and a valid email address.';
    } elseif (strlen($password) < 12) {
        $error = 'Choose an administrator password with at least 12 characters.';
    } else {
        try {
            if (!$isVercel) {
                $server = db_server();
                $server->exec('CREATE DATABASE IF NOT EXISTS `SHEWA` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            }
            $database = db();
            $database->exec(
                "CREATE TABLE IF NOT EXISTS users (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    email VARCHAR(190) NOT NULL UNIQUE,
                    password_hash VARCHAR(255) NOT NULL,
                    role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB"
            );
            $database->exec(
                "CREATE TABLE IF NOT EXISTS products (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(120) NOT NULL,
                    category VARCHAR(60) NOT NULL,
                    price DECIMAL(10,2) NOT NULL,
                    stock INT UNSIGNED NOT NULL DEFAULT 0,
                    tone VARCHAR(24) NOT NULL DEFAULT 'tone-nude',
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_products_active (is_active, category)
                ) ENGINE=InnoDB"
            );
            $database->exec(
                "CREATE TABLE IF NOT EXISTS orders (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    order_number VARCHAR(32) NOT NULL UNIQUE,
                    user_id BIGINT UNSIGNED NOT NULL,
                    total_amount DECIMAL(10,2) NOT NULL,
                    currency CHAR(3) NOT NULL DEFAULT 'USD',
                    status ENUM('awaiting_payment', 'paid', 'cancelled') NOT NULL DEFAULT 'awaiting_payment',
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id)
                ) ENGINE=InnoDB"
            );
            $database->exec(
                "CREATE TABLE IF NOT EXISTS order_items (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    order_id BIGINT UNSIGNED NOT NULL,
                    product_id BIGINT UNSIGNED NULL,
                    product_name VARCHAR(120) NOT NULL,
                    quantity INT UNSIGNED NOT NULL,
                    unit_price DECIMAL(10,2) NOT NULL,
                    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
                ) ENGINE=InnoDB"
            );

            $database->beginTransaction();
            $adminCount = (int) $database->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
            if ($adminCount > 0) {
                throw new RuntimeException('An administrator already exists. Sign in at the admin page instead.');
            }

            $adminInsert = $database->prepare(
                "INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, 'admin')"
            );
            $adminInsert->execute([
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);

            $productCount = (int) $database->query('SELECT COUNT(*) FROM products')->fetchColumn();
            if ($productCount === 0) {
                $seedProducts = [
                    ['The Sienna Wrap Dress', 'Dresses', 78, 8, 'tone-rose'],
                    ['The Sunday Blazer', 'Tailoring', 96, 5, 'tone-olive'],
                    ['The Afterglow Midi', 'Dresses', 84, 6, 'tone-nude'],
                    ['The Muse Two-Piece', 'Sets', 112, 4, 'tone-black'],
                    ['The Poise Satin Top', 'Tops', 54, 10, 'tone-blue'],
                    ['The Noire Evening Dress', 'Dresses', 128, 3, 'tone-black'],
                ];
                $productInsert = $database->prepare(
                    'INSERT INTO products (name, category, price, stock, tone) VALUES (?, ?, ?, ?, ?)'
                );
                foreach ($seedProducts as $product) {
                    $productInsert->execute($product);
                }
            }
            $database->commit();

            if (!$isVercel && file_put_contents($lockFile, "Installed " . date(DATE_ATOM) . PHP_EOL, LOCK_EX) === false) {
                throw new RuntimeException('The setup lock file could not be written. Check folder permissions.');
            }

            $complete = true;
            $error = '';
        } catch (Throwable $exception) {
            if (isset($database) && $database instanceof PDO && $database->inTransaction()) {
                $database->rollBack();
            }
            error_log('SHEWA setup error: ' . $exception->getMessage());
            $error = $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'Setup could not connect to MySQL or create the store tables. Check XAMPP MySQL and your database configuration.';
        }
    }
}

$token = csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Set up Ewa_luxe Closet</title>
  <style>
    body{margin:0;padding:40px 18px;background:#f5f0e8;color:#191714;font:15px Arial,sans-serif}
    main{max-width:560px;margin:6vh auto;padding:36px;background:#fbf9f5;border-top:4px solid #aa8959;box-shadow:0 15px 50px #33251b16}
    h1{font:400 38px Georgia,serif}p{line-height:1.7;color:#665d53}label{display:block;margin:16px 0;color:#665d53;font-size:12px}
    input{box-sizing:border-box;width:100%;margin-top:7px;padding:12px;border:1px solid #dfd7ca;background:white;font-size:15px}
    button,a.button{display:inline-block;margin-top:12px;padding:12px 18px;border:0;background:#191714;color:white;text-decoration:none;cursor:pointer}
    .error{padding:12px;background:#f7e9e5;color:#8d3930}.success{padding:14px;background:#ece5d9;color:#574632}
  </style>
</head>
<body>
<main>
  <p><?= $isVercel ? 'VERCEL DEPLOYMENT · MYSQL DATABASE' : 'LOCAL XAMPP INSTALLATION · DATABASE SHEWA' ?></p>
  <h1>Set up your store.</h1>
  <?php if ($complete): ?>
    <div class="success">Setup is complete. Your SHEWA database, sample catalog, and admin account are ready.</div>
    <p>Sign in to the protected catalog dashboard with the admin email and password you chose.</p>
    <a class="button" href="<?= e(app_url('admin/login.php')) ?>">Go to admin sign in</a>
    <a class="button" href="<?= e(app_url('index.php')) ?>">View storefront</a>
  <?php else: ?>
    <p><?= $isVercel
        ? 'This initializes the configured MySQL database, adds sample products, and creates the first administrator. Enter the one-time setup secret saved in your Vercel environment variables.'
        : 'This creates the <strong>SHEWA</strong> database, adds sample products, and creates the first administrator. Setup runs only once. Keep your admin password private.' ?></p>
    <?php if ($error !== ''): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
      <?php if ($isVercel): ?><label>Vercel setup secret<input name="setup_secret" type="password" required autocomplete="off"></label><?php endif; ?>
      <label>Administrator name<input name="name" required maxlength="100" autocomplete="name"></label>
      <label>Administrator email<input name="email" type="email" required maxlength="190" autocomplete="email"></label>
      <label>Administrator password (12+ characters)<input name="password" type="password" required minlength="12" autocomplete="new-password"></label>
      <button type="submit">Create <?= $isVercel ? 'admin account' : 'SHEWA database and admin' ?></button>
    </form>
  <?php endif; ?>
</main>
</body>
</html>
