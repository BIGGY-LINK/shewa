<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function checkout_error(int $status, string $message): never
{
    http_response_code($status);
    echo json_encode(['error' => $message], JSON_THROW_ON_ERROR);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    checkout_error(405, 'Method not allowed.');
}

if (current_user() === null || current_user()['role'] !== 'customer') {
    checkout_error(401, 'Sign in with a customer account before submitting an order.');
}

try {
    verify_csrf();
} catch (Throwable $exception) {
    checkout_error(403, 'Your session expired. Reload the page and try again.');
}

$payload = json_decode(file_get_contents('php://input') ?: '', true);
$cart = is_array($payload) ? ($payload['cart'] ?? null) : null;
if (!is_array($cart) || $cart === [] || count($cart) > 50) {
    checkout_error(422, 'Your bag is empty or contains too many different products.');
}

try {
    $database = db();
    $database->beginTransaction();
    $items = [];
    $total = 0.0;
    $productQuery = $database->prepare('SELECT id, name, price, stock FROM products WHERE id = ? AND is_active = 1 FOR UPDATE');

    foreach ($cart as $rawId => $rawQuantity) {
        $productId = filter_var($rawId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $quantity = filter_var($rawQuantity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 20]]);
        if ($productId === false || $quantity === false) {
            throw new DomainException('The bag contains an invalid product quantity.');
        }
        $productQuery->execute([$productId]);
        $product = $productQuery->fetch();
        if (!$product || $quantity > (int) $product['stock']) {
            throw new DomainException('An item in your bag is no longer available in that quantity.');
        }
        $price = (float) $product['price'];
        $total += $price * $quantity;
        $items[] = [
            'id' => (int) $product['id'],
            'name' => $product['name'],
            'price' => $price,
            'quantity' => $quantity,
        ];
    }

    if ($total <= 0 || $total > 99999999) {
        throw new DomainException('The order total is invalid.');
    }

    $orderNumber = 'EL-' . strtoupper(bin2hex(random_bytes(5)));
    $orderInsert = $database->prepare(
        "INSERT INTO orders (order_number, user_id, total_amount, currency, status)
         VALUES (?, ?, ?, 'USD', 'awaiting_payment')"
    );
    $orderInsert->execute([$orderNumber, current_user()['id'], number_format($total, 2, '.', '')]);
    $orderId = (int) $database->lastInsertId();

    $itemInsert = $database->prepare(
        'INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price) VALUES (?, ?, ?, ?, ?)'
    );
    foreach ($items as $item) {
        $itemInsert->execute([$orderId, $item['id'], $item['name'], $item['quantity'], $item['price']]);
    }

    $database->commit();
    echo json_encode([
        'orderNumber' => $orderNumber,
        'message' => 'Your order was saved as awaiting payment. No payment has been taken.',
    ], JSON_THROW_ON_ERROR);
} catch (DomainException $exception) {
    if (isset($database) && $database instanceof PDO && $database->inTransaction()) {
        $database->rollBack();
    }
    checkout_error(422, $exception->getMessage());
} catch (Throwable $exception) {
    if (isset($database) && $database instanceof PDO && $database->inTransaction()) {
        $database->rollBack();
    }
    error_log('Checkout error: ' . $exception->getMessage());
    checkout_error(500, 'The order could not be saved. Please try again later.');
}
