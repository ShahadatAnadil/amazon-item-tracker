<?php
require(__DIR__ . "/../../partials/nav.php");
require_once(__DIR__ . "/../../lib/render_functions.php");

if (!isset($_SESSION['user'])) {
    flash("You must be logged in to view this page", "warning");
    die(header("Location: $BASE_PATH/login.php"));
}

$user_id = $_SESSION['user']['id'];
$query = "SELECT p.* FROM user_favorites f 
          JOIN `IT202-S24-ProductDetails` p ON f.item_id = p.id 
          WHERE f.user_id = :user_id";
$db = getDB();
$stmt = $db->prepare($query);
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

$items = [];
try {
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching favorites: " . var_export($e, true));
    flash("Unhandled error occurred", "danger");
}

?>

<div class="container">
    <h1>Your Favorites</h1>
    <div class="row">
        <?php if (empty($items)): ?>
            <p>No favorite items available.</p>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <img src="<?php echo htmlspecialchars($item['product_photo']); ?>" class="card-img-top" alt="Product Image">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($item['product_title']); ?></h5>
                            <p class="card-text">Price: <?php echo htmlspecialchars($item['product_price']); ?> <?php echo htmlspecialchars($item['currency']); ?></p>
                            <p class="card-text">Rating: <?php echo htmlspecialchars($item['product_star_rating']); ?> (<?php echo htmlspecialchars($item['product_num_ratings']); ?> ratings)</p>
                            <a href="<?php echo get_url('item.php?id=' . urlencode($item['id'])); ?>" class="btn btn-primary">View</a>
                            <i class="fas fa-heart" onclick="toggleFavorite(<?php echo $item['id']; ?>)"></i>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>