<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$error = '';
$action = (string) ($_POST['action'] ?? $_GET['action'] ?? 'login');
$currentUser = current_user();

if ($currentUser !== null && $currentUser['role'] === 'admin') {
    redirect_to('admin/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if ($currentUser !== null) {
        redirect_to('index.php#shop');
    }
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    try {
        if ($action === 'register') {
            $name = trim((string) ($_POST['name'] ?? ''));
            if ($name === '' || strlen($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
                throw new InvalidArgumentException('Enter your name and a valid email address.');
            }
            if (strlen($password) < 12) {
                throw new InvalidArgumentException('Choose a password with at least 12 characters.');
            }

            $insert = db()->prepare(
                "INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, 'customer')"
            );
            $insert->execute([
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);

            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int) db()->lastInsertId(),
                'name' => $name,
                'email' => $email,
                'role' => 'customer',
            ];
            unset($_SESSION['csrf_token']);
            redirect_to('index.php#shop');
        }

        if ($action !== 'login' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Enter a valid email address and password.');
        }
        $lookup = db()->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = :email LIMIT 1');
        $lookup->execute(['email' => $email]);
        $user = $lookup->fetch();
        if (!$user || $user['role'] !== 'customer' || !password_verify($password, $user['password_hash'])) {
            throw new InvalidArgumentException('The email or password is incorrect. Admin accounts must sign in through Admin.');
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => 'customer',
        ];
        unset($_SESSION['csrf_token']);
        redirect_to('index.php#shop');
    } catch (PDOException $exception) {
        error_log('Customer account database error: ' . $exception->getMessage());
        $error = $exception->getCode() === '23000'
            ? 'An account already uses that email address.'
            : 'The account service is temporarily unavailable. Check that MySQL is running.';
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
    }
}

$token = csrf_token();
$flashMessage = take_flash();
$orders = [];
if ($currentUser !== null && $currentUser['role'] === 'customer') {
    try {
        $orderQuery = db()->prepare(
            "SELECT o.order_number, o.total_amount, o.currency, o.status, o.created_at,
                    GROUP_CONCAT(CONCAT(oi.quantity, ' × ', oi.product_name) ORDER BY oi.id SEPARATOR ', ') AS items
             FROM orders o
             LEFT JOIN order_items oi ON oi.order_id = o.id
             WHERE o.user_id = ?
             GROUP BY o.id
             ORDER BY o.created_at DESC"
        );
        $orderQuery->execute([$currentUser['id']]);
        $orders = $orderQuery->fetchAll();
    } catch (Throwable $exception) {
        error_log('Customer order history error: ' . $exception->getMessage());
        $error = 'Your order history is temporarily unavailable.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $currentUser ? 'My account' : 'Customer account' ?> — Ewa_luxe Closet</title>
  <style>
    :root{color-scheme:light;--paper:#fbf8f3;--ink:#191714;--muted:#76695f;--gold:#a45b48;--line:#ded1c5}
    *{box-sizing:border-box}body{margin:0;background:#eee5dc;color:var(--ink);font:15px Arial,sans-serif}
    header{padding:22px max(7vw,20px);background:var(--paper);border-bottom:1px solid var(--line)}
    header a{color:var(--ink);font:20px Georgia,serif;text-decoration:none}
    main{max-width:510px;margin:9vh auto;padding:36px;background:var(--paper);box-shadow:0 18px 50px #33251b12}
    .eyebrow{color:var(--gold);font-size:10px;letter-spacing:.18em;text-transform:uppercase}
    h1{font:400 40px Georgia,serif}p{color:var(--muted);line-height:1.75}
    .tabs{display:flex;gap:8px;margin:24px 0}
    .tabs a,.button{padding:11px 15px;border:1px solid var(--line);background:transparent;color:var(--ink);font-size:12px;text-decoration:none;cursor:pointer}
    .tabs a.active,.button{border-color:var(--ink);background:var(--ink);color:white}
    label{display:block;margin:16px 0;color:var(--muted);font-size:11px}
    input{box-sizing:border-box;width:100%;margin-top:7px;padding:12px;border:1px solid var(--line);background:white;color:var(--ink);font-size:15px}
    .button{width:100%;margin-top:10px} .error{padding:12px;background:#f7e9e5;color:#8d3930}
    .success{padding:12px;background:#ece5d9;color:#574632}
    .note{font-size:11px}
  </style>
</head>
<body>
<header><a href="<?= e(app_url('index.php')) ?>">Ewa_luxe Closet</a></header>
<main>
  <div class="eyebrow">Your Ewa_luxe account</div>
  <h1><?= $currentUser ? 'Welcome back, ' . e($currentUser['name']) . '.' : 'Welcome in.' ?></h1>
  <p><?= $currentUser ? 'Review your orders or return to the collection.' : 'Sign in or create an account to submit an order and view your order history.' ?></p>
  <?php if ($error !== ''): ?><div class="error" role="alert"><?= e($error) ?></div><?php endif; ?>
  <?php if ($flashMessage): ?><div class="success"><?= e($flashMessage['message']) ?></div><?php endif; ?>
  <?php if ($currentUser): ?>
    <p><?= e($currentUser['email']) ?></p>
    <a class="button" href="<?= e(app_url('index.php#shop')) ?>">Continue shopping</a>
    <h2 style="margin-top:32px;font:400 27px Georgia,serif">Order history</h2>
    <?php if ($orders): ?>
      <div style="overflow:auto">
        <table style="width:100%;border-collapse:collapse;text-align:left;font-size:12px">
          <thead><tr><th style="padding:10px;border-bottom:1px solid #ded1c5">Order</th><th style="padding:10px;border-bottom:1px solid #ded1c5">Items</th><th style="padding:10px;border-bottom:1px solid #ded1c5">Total</th><th style="padding:10px;border-bottom:1px solid #ded1c5">Status</th></tr></thead>
          <tbody>
          <?php foreach ($orders as $order): ?>
            <tr>
              <td style="padding:10px;border-bottom:1px solid #ded1c5"><?= e($order['order_number']) ?><br><span class="note"><?= e($order['created_at']) ?></span></td>
              <td style="padding:10px;border-bottom:1px solid #ded1c5"><?= e($order['items'] ?? '') ?></td>
              <td style="padding:10px;border-bottom:1px solid #ded1c5"><?= e($order['currency']) ?> <?= e(number_format((float) $order['total_amount'], 2)) ?></td>
              <td style="padding:10px;border-bottom:1px solid #ded1c5"><?= e(str_replace('_', ' ', $order['status'])) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="note">You have not submitted an order yet.</p>
    <?php endif; ?>
    <form method="post" action="<?= e(app_url('logout.php')) ?>">
      <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
      <button class="button" type="submit">Sign out</button>
    </form>
  <?php else: ?>
    <nav class="tabs" aria-label="Account options">
      <a class="<?= $action === 'login' ? 'active' : '' ?>" href="?action=login">Sign in</a>
      <a class="<?= $action === 'register' ? 'active' : '' ?>" href="?action=register">Create account</a>
    </nav>
    <form method="post" action="account.php">
      <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
      <input type="hidden" name="action" value="<?= $action === 'register' ? 'register' : 'login' ?>">
      <?php if ($action === 'register'): ?>
        <label>Your name<input name="name" required maxlength="100" autocomplete="name"></label>
      <?php endif; ?>
      <label>Email address<input name="email" type="email" required maxlength="190" autocomplete="email"></label>
      <label>Password<input name="password" type="password" required <?= $action === 'register' ? 'minlength="12" autocomplete="new-password"' : 'autocomplete="current-password"' ?>></label>
      <?php if ($action === 'register'): ?><p class="note">Use at least 12 characters. Passwords are securely hashed before saving.</p><?php endif; ?>
      <button class="button" type="submit"><?= $action === 'register' ? 'Create account' : 'Sign in' ?></button>
    </form>
  <?php endif; ?>
</main>
</body>
</html>
