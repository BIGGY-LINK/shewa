<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

$error = '';
$flashMessage = take_flash();
$database = null;

try {
    $database = db();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'save_product') {
            $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $name = trim((string) ($_POST['name'] ?? ''));
            $category = trim((string) ($_POST['category'] ?? ''));
            $price = filter_var($_POST['price'] ?? '', FILTER_VALIDATE_FLOAT);
            $stock = filter_var($_POST['stock'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            $tone = (string) ($_POST['tone'] ?? '');
            $tones = ['tone-rose', 'tone-olive', 'tone-black', 'tone-nude', 'tone-blue'];

            if ($id === false && isset($_POST['id']) && $_POST['id'] !== '') {
                throw new InvalidArgumentException('The selected catalog item is invalid.');
            }
            if ($name === '' || strlen($name) > 120 || $category === '' || strlen($category) > 60) {
                throw new InvalidArgumentException('Enter a product name (up to 120 characters) and category (up to 60 characters).');
            }
            if ($price === false || $price <= 0 || $price > 99999999 || $stock === false || !in_array($tone, $tones, true)) {
                throw new InvalidArgumentException('Enter a valid price and stock quantity and choose a display style.');
            }

            if ($id === false) {
                $save = $database->prepare(
                    'INSERT INTO products (name, category, price, stock, tone, is_active) VALUES (?, ?, ?, ?, ?, 1)'
                );
                $save->execute([$name, $category, number_format((float) $price, 2, '.', ''), $stock, $tone]);
                flash('Catalog item added and published to the storefront.');
            } else {
                $save = $database->prepare(
                    'UPDATE products SET name = ?, category = ?, price = ?, stock = ?, tone = ? WHERE id = ? AND is_active = 1'
                );
                $save->execute([$name, $category, number_format((float) $price, 2, '.', ''), $stock, $tone, $id]);
                if ($save->rowCount() === 0) {
                    $exists = $database->prepare('SELECT id FROM products WHERE id = ? AND is_active = 1');
                    $exists->execute([$id]);
                    if (!$exists->fetch()) {
                        throw new InvalidArgumentException('This catalog item no longer exists.');
                    }
                }
                flash('Catalog item changes saved.');
            }
            redirect_to('admin/index.php');
        }

        if ($action === 'remove_product') {
            $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false) {
                throw new InvalidArgumentException('The selected catalog item is invalid.');
            }
            $remove = $database->prepare('UPDATE products SET is_active = 0 WHERE id = ? AND is_active = 1');
            $remove->execute([$id]);
            if ($remove->rowCount() !== 1) {
                throw new InvalidArgumentException('This catalog item is already removed or does not exist.');
            }
            flash('Catalog item removed from the storefront.');
            redirect_to('admin/index.php');
        }

        throw new InvalidArgumentException('Choose a valid dashboard action.');
    }

    $products = $database->query(
        'SELECT id, name, category, price, stock, tone FROM products WHERE is_active = 1 ORDER BY updated_at DESC, id DESC'
    )->fetchAll();
    $orders = $database->query(
        "SELECT o.id, o.order_number, o.total_amount, o.currency, o.status, o.created_at,
                u.name AS customer_name, u.email AS customer_email,
                GROUP_CONCAT(CONCAT(oi.quantity, ' × ', oi.product_name) ORDER BY oi.id SEPARATOR ', ') AS items
         FROM orders o
         INNER JOIN users u ON u.id = o.user_id
         LEFT JOIN order_items oi ON oi.order_id = o.id
         GROUP BY o.id
         ORDER BY o.created_at DESC
         LIMIT 100"
    )->fetchAll();
} catch (InvalidArgumentException $exception) {
    $error = $exception->getMessage();
    $products = $database ? $database->query(
        'SELECT id, name, category, price, stock, tone FROM products WHERE is_active = 1 ORDER BY updated_at DESC, id DESC'
    )->fetchAll() : [];
    $orders = [];
} catch (Throwable $exception) {
    error_log('Admin dashboard error: ' . $exception->getMessage());
    http_response_code(503);
    $error = 'The dashboard could not load its catalog. Check the SHEWA database connection.';
    $products = [];
    $orders = [];
}

$user = current_user();
$token = csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#f5f0e8">
  <title>Catalog dashboard — Ewa_luxe Closet</title>
  <style>
    :root{color-scheme:light;--paper:#fbf8f3;--ink:#191714;--muted:#76695f;--gold:#a45b48;--line:#ded1c5;--panel:#fffdfa}
    *{box-sizing:border-box}body{margin:0;background:#eee5dc;color:var(--ink);font:14px Arial,sans-serif}
    header{position:sticky;top:0;z-index:2;display:flex;align-items:center;justify-content:space-between;padding:18px 6vw;background:var(--paper);border-bottom:1px solid var(--line)}
    header a{color:var(--ink);font:20px Georgia,serif;text-decoration:none}
    .header-meta{display:flex;align-items:center;gap:18px;color:var(--muted);font-size:11px}
    .header-meta form{margin:0}.header-meta button{padding:9px 12px;border:1px solid var(--line);background:transparent;color:var(--ink);cursor:pointer}
    main{max-width:1220px;margin:50px auto;padding:0 24px}
    .eyebrow{color:var(--gold);font-size:10px;font-weight:bold;letter-spacing:.18em;text-transform:uppercase}
    h1{margin:12px 0;font:400 clamp(38px,5vw,58px) Georgia,serif;letter-spacing:-.04em}
    h2{margin:0 0 16px;font:400 27px Georgia,serif}
    .intro{color:var(--muted);line-height:1.75}.notice{margin:24px 0;padding:15px 18px;border-left:4px solid var(--gold);background:#f8f4ec;color:#655641;line-height:1.7}
    .alert{margin:18px 0;padding:12px;background:#f7e9e5;color:#8d3930}.success{margin:18px 0;padding:12px;background:#ece5d9;color:#574632}
    .tabs{display:flex;gap:8px;margin:25px 0 14px}.tab,.button{padding:11px 15px;border:1px solid var(--line);background:transparent;color:var(--ink);font-size:11px;cursor:pointer}
    .tab[aria-selected="true"],.button.primary{border-color:var(--ink);background:var(--ink);color:#fff}
    .panel{padding:24px;border:1px solid var(--line);background:var(--panel);box-shadow:0 12px 35px #30241b0b}
    .panel[hidden]{display:none}.panel-heading{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:18px}
    .panel-heading p{margin:0;color:var(--muted);font-size:12px;line-height:1.7}
    .button.danger{border-color:#b87167;color:#8d3930}.button:hover{border-color:var(--gold)}
    .table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;text-align:left;font-size:12px}
    th,td{padding:13px 10px;border-bottom:1px solid var(--line);vertical-align:middle}
    th{color:var(--gold);font-size:9px;letter-spacing:.13em;text-transform:uppercase}
    .row-actions{display:flex;gap:7px}.row-actions form{margin:0}.row-actions .button{padding:8px 10px;font-size:10px}
    .product-editor{max-width:720px;margin-top:24px;padding-top:22px;border-top:1px solid var(--line)}
    .product-editor[hidden]{display:none}.fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}
    label{display:grid;gap:7px;color:var(--muted);font-size:10px;font-weight:bold;letter-spacing:.08em;text-transform:uppercase}
    input,select{width:100%;min-height:40px;padding:10px;border:1px solid var(--line);background:white;color:var(--ink);font:14px Arial,sans-serif}
    .form-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:18px}
    .status{color:var(--muted);font-size:11px}
    @media(max-width:650px){header{padding:15px 5vw}.header-meta{gap:9px}main{margin:35px auto;padding:0 5vw}.panel{padding:16px}.panel-heading{align-items:flex-start;flex-direction:column}.fields{grid-template-columns:1fr}.form-actions{flex-wrap:wrap}}
  </style>
</head>
<body>
<header>
  <a href="<?= e(app_url('index.php')) ?>">Ewa_luxe Closet</a>
  <div class="header-meta">
    <span>Signed in: <?= e($user['name'] ?? 'Admin') ?></span>
    <a href="<?= e(app_url('index.php')) ?>">View store</a>
    <form method="post" action="<?= e(app_url('logout.php')) ?>">
      <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
      <button type="submit">Sign out</button>
    </form>
  </div>
</header>
<main>
  <div class="eyebrow">Store management · <?= count($products) ?> active catalog items</div>
  <h1>Admin dashboard.</h1>
  <p class="intro">Manage the live storefront catalog and review customer orders. Updates are saved to the SHEWA database.</p>
  <div class="notice"><strong>Payment status:</strong> Order submissions are saved as awaiting payment. No charges are taken until PayPal or another payment provider is configured.</div>
  <?php if ($error !== ''): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>
  <?php if ($flashMessage): ?><div class="success" role="status"><?= e($flashMessage['message']) ?></div><?php endif; ?>
  <div class="tabs" role="tablist" aria-label="Dashboard sections">
    <button class="tab" id="catalog-tab" type="button" role="tab" aria-selected="true" aria-controls="catalog-panel">Catalog</button>
    <button class="tab" id="orders-tab" type="button" role="tab" aria-selected="false" aria-controls="orders-panel">Orders (<?= count($orders) ?>)</button>
  </div>
  <section class="panel" id="catalog-panel" role="tabpanel" aria-labelledby="catalog-tab">
    <div class="panel-heading">
      <div><h2>Catalog items</h2><p>Add products, edit prices and stock, or remove items from the public storefront.</p></div>
      <button class="button" id="new-product" type="button">Add catalog item</button>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Product</th><th>Category</th><th>Price (USD)</th><th>Stock</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($products as $product): ?>
          <tr>
            <td><?= e($product['name']) ?></td><td><?= e($product['category']) ?></td>
            <td>$<?= e(number_format((float) $product['price'], 2)) ?></td><td><?= e($product['stock']) ?></td>
            <td><div class="row-actions">
              <button class="button edit-product" type="button"
                data-id="<?= e($product['id']) ?>"
                data-name="<?= e($product['name']) ?>"
                data-category="<?= e($product['category']) ?>"
                data-price="<?= e($product['price']) ?>"
                data-stock="<?= e($product['stock']) ?>"
                data-tone="<?= e($product['tone']) ?>">Edit</button>
              <form method="post" onsubmit="return confirm('Remove this item from the public storefront?')">
                <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                <input type="hidden" name="action" value="remove_product">
                <input type="hidden" name="id" value="<?= e($product['id']) ?>">
                <button class="button danger" type="submit">Remove</button>
              </form>
            </div></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$products): ?><tr><td colspan="5">No active catalog items. Add your first product above.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <form class="product-editor" id="product-editor" method="post" hidden>
      <h2 id="editor-title">Add catalog item</h2>
      <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
      <input type="hidden" name="action" value="save_product">
      <input type="hidden" name="id" id="product-id">
      <div class="fields">
        <label>Product name<input name="name" id="product-name" required maxlength="120"></label>
        <label>Category<input name="category" id="product-category" required maxlength="60"></label>
        <label>Price (USD)<input name="price" id="product-price" type="number" required min="0.01" max="99999999" step="0.01"></label>
        <label>Stock quantity<input name="stock" id="product-stock" type="number" required min="0" step="1"></label>
        <label>Display style<select name="tone" id="product-tone"><option value="tone-rose">Rose</option><option value="tone-olive">Olive</option><option value="tone-black">Black</option><option value="tone-nude">Nude</option><option value="tone-blue">Blue</option></select></label>
      </div>
      <div class="form-actions"><button class="button" id="cancel-editor" type="button">Cancel</button><button class="button primary" type="submit">Save catalog item</button></div>
    </form>
  </section>
  <section class="panel" id="orders-panel" role="tabpanel" aria-labelledby="orders-tab" hidden>
    <div class="panel-heading"><div><h2>Customer orders</h2><p>Orders stay marked awaiting payment until a payment provider is connected.</p></div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Order</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Placed</th></tr></thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
          <tr>
            <td><?= e($order['order_number']) ?></td>
            <td><?= e($order['customer_name']) ?><br><span class="status"><?= e($order['customer_email']) ?></span></td>
            <td><?= e($order['items'] ?? '') ?></td>
            <td><?= e($order['currency']) ?> <?= e(number_format((float) $order['total_amount'], 2)) ?></td>
            <td><?= e(str_replace('_', ' ', $order['status'])) ?></td>
            <td><?= e($order['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$orders): ?><tr><td colspan="6">No customer orders have been submitted.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>
<script>
  const editor = document.querySelector("#product-editor");
  function showEditor(product = null) {
    editor.reset();
    document.querySelector("#product-id").value = product?.id || "";
    document.querySelector("#editor-title").textContent = product ? "Edit catalog item" : "Add catalog item";
    if (product) {
      document.querySelector("#product-name").value = product.name;
      document.querySelector("#product-category").value = product.category;
      document.querySelector("#product-price").value = product.price;
      document.querySelector("#product-stock").value = product.stock;
      document.querySelector("#product-tone").value = product.tone;
    }
    editor.hidden = false;
    editor.scrollIntoView({ behavior: "smooth", block: "nearest" });
    document.querySelector("#product-name").focus();
  }
  document.querySelector("#new-product").addEventListener("click", () => showEditor());
  document.querySelector("#cancel-editor").addEventListener("click", () => { editor.hidden = true; });
  document.querySelectorAll(".edit-product").forEach(button => button.addEventListener("click", () => {
    showEditor({
      id: button.dataset.id,
      name: button.dataset.name,
      category: button.dataset.category,
      price: button.dataset.price,
      stock: button.dataset.stock,
      tone: button.dataset.tone
    });
  }));
  document.querySelectorAll(".tab").forEach(tab => tab.addEventListener("click", () => {
    const catalogSelected = tab.id === "catalog-tab";
    document.querySelector("#catalog-panel").hidden = !catalogSelected;
    document.querySelector("#orders-panel").hidden = catalogSelected;
    document.querySelector("#catalog-tab").setAttribute("aria-selected", String(catalogSelected));
    document.querySelector("#orders-tab").setAttribute("aria-selected", String(!catalogSelected));
  }));
</script>
</body>
</html>
