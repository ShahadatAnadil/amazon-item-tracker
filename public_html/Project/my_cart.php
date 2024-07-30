<?php
require(__DIR__ . "/../../partials/nav.php");

if (!isset($_SESSION['user'])) {
    flash("You must be logged in to view this page", "warning");
    die(header("Location: $BASE_PATH/login.php"));
}

$user_id = $_SESSION['user']['id'];
$db = getDB();

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

// Get the total number of items in the cart
$stmt = $db->prepare("SELECT COUNT(*) as total FROM user_cart WHERE user_id = :user_id");
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
try {
    $stmt->execute();
    $total_items = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_items / $limit);
    $current_page = floor($offset / $limit) + 1;
} catch (PDOException $e) {
    error_log("Error fetching cart item count: " . var_export($e, true));
    flash("Unhandled error occurred", "danger");
    $total_items = 0;
    $total_pages = 1;
    $current_page = 1;
}
// COMMENT FOR HANDLING API DATA ASSOCIATION PULL REQUEST
$query = "SELECT uc.id, p.product_title, p.product_price, p.currency, uc.quantity, p.id AS product_id, p.product_photo
          FROM user_cart uc
          JOIN `IT202-S24-ProductDetails` p ON uc.item_id = p.id
          WHERE uc.user_id = :user_id
          LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$cart_items = [];
try {
    $stmt->execute();
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching cart items: " . var_export($e, true));
    flash("Unhandled error occurred", "danger");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove_item_id'])) {
        $remove_item_id = intval($_POST['remove_item_id']);
        $stmt = $db->prepare("DELETE FROM user_cart WHERE id = :id AND user_id = :user_id");
        try {
            $stmt->execute([':id' => $remove_item_id, ':user_id' => $user_id]);
            flash("Item removed from cart", "success");
            header("Location: my_cart.php");
            exit;
        } catch (PDOException $e) {
            error_log("Error removing item from cart: " . var_export($e, true));
            flash("Unhandled error occurred", "danger");
        }
    } elseif (isset($_POST['checkout'])) {
        $items = [];
        foreach ($cart_items as $item) {
            $items[] = [
                'id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['product_price']
            ];
        }
        $order_id = process_purchase($user_id, $items);
        if ($order_id) {
            $stmt = $db->prepare("DELETE FROM user_cart WHERE user_id = :user_id");
            try {
                $stmt->execute([':user_id' => $user_id]);
                flash("Checkout completed. Order ID: $order_id", "success");
                header("Location: my_purchases.php");
                exit;
            } catch (PDOException $e) {
                error_log("Error clearing cart: " . var_export($e, true));
                flash("Unhandled error occurred", "danger");
            }
        } else {
            flash("Purchase failed. Please try again.", "danger");
        }
    }
}
?>

<div class="container">
    <h1>My Cart</h1>
    <?php if (empty($cart_items)): ?>
        <p>Your cart is empty.</p>
    <?php else: ?>
        <p>You have <?php echo $total_items; ?> items in your cart.</p>
        <form method="POST" action="my_cart.php">
            <table class="table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td><img src="<?php echo htmlspecialchars($item['product_photo']); ?>" alt="Product Image" style="width: 50px; height: 50px;"></td>
                            <td><?php echo htmlspecialchars($item['product_title']); ?></td>
                            <td><?php echo htmlspecialchars($item['product_price']); ?> <?php echo htmlspecialchars($item['currency']); ?></td>
                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                            <td>
                                <button type="submit" name="remove_item_id" value="<?php echo htmlspecialchars($item['id']); ?>" class="btn btn-danger">Remove</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" name="checkout" class="btn btn-success">Checkout</button>
        </form>
        <!-- Pagination (if needed) -->
        <div class="d-flex justify-content-between align-items-center mt-3">
            <a href="?<?php echo http_build_query(array_merge($_GET, ['offset' => max(0, $offset - $limit)])); ?>" class="btn btn-outline-primary <?php echo $current_page == 1 ? 'disabled' : ''; ?>">Previous</a>
            <span>Page <?php echo $current_page; ?> of <?php echo $total_pages; ?></span>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['offset' => $offset + $limit])); ?>" class="btn btn-outline-primary <?php echo $current_page == $total_pages ? 'disabled' : ''; ?>">Next</a>
        </div>
    <?php endif; ?>
</div>

<?php
require(__DIR__ . "/../../partials/flash.php");
?>