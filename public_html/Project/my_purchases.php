<?php
require(__DIR__ . "/../../partials/nav.php");

if (!isset($_SESSION['user'])) {
    flash("You must be logged in to view this page", "warning");
    die(header("Location: $BASE_PATH/login.php"));
}

$user_id = $_SESSION['user']['id'];
$db = getDB();

$order_id_filter = isset($_GET['order_id_filter']) ? intval($_GET['order_id_filter']) : null;

$query = "SELECT o.id AS order_id, o.total_price, o.created, oi.item_id, oi.quantity, oi.unit_price, p.product_title
          FROM Orders o
          JOIN OrderItems oi ON o.id = oi.order_id
          JOIN `IT202-S24-ProductDetails` p ON oi.item_id = p.id
          WHERE o.user_id = :user_id";

if ($order_id_filter) {
    $query .= " AND o.id = :order_id_filter";
}

$query .= " ORDER BY o.created DESC";

$stmt = $db->prepare($query);
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

if ($order_id_filter) {
    $stmt->bindValue(':order_id_filter', $order_id_filter, PDO::PARAM_INT);
}

$purchases = [];
try {
    $stmt->execute();
    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching purchases: " . var_export($e, true));
    flash("Unhandled error occurred", "danger");
}

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
    <h1>My Purchases</h1>
    <form method="GET" action="my_purchases.php" class="mb-4">
        <div class="row">
            <div class="col-md-3">
                <label for="order_id_filter">Search by Order ID:</label>
                <input type="number" name="order_id_filter" id="order_id_filter" value="<?php echo htmlspecialchars($order_id_filter); ?>" class="form-control" placeholder="Order ID">
            </div>
            <div class="col-md-3">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary form-control">Apply Filter</button>
            </div>
            <div class="col-md-3">
                <label>&nbsp;</label>
                <button type="button" class="btn btn-secondary form-control" onclick="clearFilter()">Clear Filter</button>
            </div>
        </div>
    </form>

    <?php if (empty($orders)): ?>
        <p>There is no purchase history with that ID.</p>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <div class="order">
                <h2>Order ID: <?php echo htmlspecialchars($order['order_id']); ?></h2>
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
                                <td><?php echo htmlspecialchars(number_format($item['unit_price'], 2)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function clearFilter() {
    window.location.href = "<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>";
}
</script>

<?php
require(__DIR__ . "/../../partials/flash.php");
?>