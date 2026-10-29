<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = current_user();
if ($user !== null && $user['role'] === 'admin') {
    redirect_to('admin/index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    try {
        $query = db()->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
        $query->execute([$email]);
        $user = $query->fetch();
        if (!$user || $user['role'] !== 'admin' || !password_verify($password, $user['password_hash'])) {
            throw new InvalidArgumentException('The admin email or password is incorrect.');
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => 'admin',
        ];
        unset($_SESSION['csrf_token']);
        redirect_to('admin/index.php');
    } catch (PDOException $exception) {
        error_log('Admin login database error: ' . $exception->getMessage());
        $error = 'The admin sign-in service is unavailable. Check that MySQL is running and the store is set up.';
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
    }
}

$token = csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin sign in — Ewa_luxe Closet</title>
  <style>
    body{margin:0;background:#eee5dc;color:#191714;font:15px Arial,sans-serif}header{padding:22px 7vw;background:#fbf8f3}
    header a{color:#191714;font:20px Georgia,serif;text-decoration:none}main{max-width:460px;margin:10vh auto;padding:36px;background:#fbf8f3;box-shadow:0 18px 50px #33251b12}
    .eyebrow{color:#a45b48;font-size:10px;letter-spacing:.18em}h1{font:400 39px Georgia,serif}p{color:#76695f;line-height:1.7}
    label{display:block;margin:18px 0;color:#76695f;font-size:11px}input{box-sizing:border-box;width:100%;margin-top:7px;padding:12px;border:1px solid #ded1c5;font-size:15px}
    button,.button{display:inline-block;margin-top:12px;padding:12px 18px;border:0;background:#191714;color:white;text-decoration:none;cursor:pointer}
    .error{padding:12px;background:#f7e9e5;color:#8d3930}
  </style>
</head>
<body>
<header><a href="<?= e(app_url('index.php')) ?>">Ewa_luxe Closet</a></header>
<main>
  <div class="eyebrow">STAFF ACCESS · CATALOG DASHBOARD</div>
  <h1>Admin sign in.</h1>
  <p>Sign in with the administrator account created during local SHEWA setup.</p>
  <?php if ($error !== ''): ?><div class="error" role="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <label>Admin email<input type="email" name="email" required autocomplete="username"></label>
    <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
    <button type="submit">Sign in to dashboard</button>
  </form>
</main>
</body>
</html>
