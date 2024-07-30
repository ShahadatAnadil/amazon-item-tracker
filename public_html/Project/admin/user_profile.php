<?php
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH/home.php"));
}

$user_id = isset($_GET['id']) ? intval($_GET['id']) : -1;
if ($user_id < 1) {
    flash("Invalid user ID", "danger");
    die(header("Location: $BASE_PATH/home.php"));
}

$db = getDB();

// Fetch user details
$query = "SELECT * FROM Users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->execute([":id" => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    flash("User not found", "danger");
    die(header("Location: $BASE_PATH/home.php"));
}

// Fetch user's favorited items
$favorites_query = "SELECT p.* FROM `IT202-S24-ProductDetails` p
                    JOIN `user_favorites` f ON p.id = f.item_id
                    WHERE f.user_id = :user_id";
$favorites_stmt = $db->prepare($favorites_query);
$favorites_stmt->execute([":user_id" => $user_id]);
$favorites = $favorites_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch user's order history
$order_query = "SELECT o.id AS order_id, o.total_price, o.created, oi.item_id, oi.quantity, oi.unit_price, p.product_title
                FROM Orders o
                JOIN OrderItems oi ON o.id = oi.order_id
                JOIN `IT202-S24-ProductDetails` p ON oi.item_id = p.id
                WHERE o.user_id = :user_id
                ORDER BY o.created DESC";
$order_stmt = $db->prepare($order_query);
$order_stmt->execute([":user_id" => $user_id]);
$purchases = $order_stmt->fetchAll(PDO::FETCH_ASSOC);

$orders = [];
foreach ($purchases as $purchase) {
    $order_id = $purchase['order_id'];
    if (!isset($orders[$order_id])) {
        $orders[$order_id] = [
            'order_id' => $order_id,
            'total_price' => $purchase['total_price'],
            'created' => $purchase['created'],
            'items' => []
        ];
    }
    $orders[$order_id]['items'][] = [
        'item_id' => $purchase['item_id'],
        'product_title' => $purchase['product_title'],
        'quantity' => $purchase['quantity'],
        'unit_price' => $purchase['unit_price']
    ];
}
?>

<div class="container">
    <h1>User Profile</h1>
    <h2>Details</h2>
    <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>

    <h2>Favorited Items:</h2>
    <?php if (empty($favorites)): ?>
        <p>No favorited items found.</p>
    <?php else: ?>
        <div class="row">
            <?php foreach ($favorites as $item): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <img src="<?php echo htmlspecialchars($item['product_photo']); ?>" class="card-img-top" alt="Product Image">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($item['product_title']); ?></h5>
                            <p class="card-text">Price: <?php echo htmlspecialchars($item['product_price']); ?> <?php echo htmlspecialchars($item['currency']); ?></p>
                            <p class="card-text">Rating: <?php echo htmlspecialchars($item['product_star_rating']); ?></p>
                            <a href="<?php echo get_url('Project/item.php?id=' . urlencode($item['id'])); ?>" class="btn btn-primary">View</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h2>Order History:</h2>
    <?php if (empty($orders)): ?>
        <p>No order history found.</p>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <div class="order">
                <h3>Order ID: <?php echo htmlspecialchars($order['order_id']); ?></h3>
                <p><strong>Total Price:</strong> $<?php echo htmlspecialchars(number_format($order['total_price'], 2)); ?></p>
                <p><strong>Date:</strong> <?php echo htmlspecialchars($order['created']); ?></p>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order['items'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_title']); ?></td>
                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td><?php echo htmlspecialchars($item['unit_price']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Add the button to delete all associations -->
    <a href="<?php echo get_url('admin/delete_all_associations.php?user_id=' . urlencode($user_id)); ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete all associations for this user?');">Delete All Associations</a>
</div>

<?php
require(__DIR__ . "/../../../partials/flash.php");
?>