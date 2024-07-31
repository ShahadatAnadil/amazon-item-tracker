<?php
require(__DIR__ . "/../../../partials/nav.php");
require_once(__DIR__ . "/../../../lib/render_functions.php");
require_once(__DIR__ . "/../../../lib/item_api.php");


if (!isset($_SESSION['user'])) {
    flash("You must be logged in to view this page", "warning");
    die(header("Location: " . get_url('login.php')));
}


if (!has_role("Admin")) {
    flash("You don't have permission to view this item", "warning");
    die(header("Location: " . get_url('public_list_items.php')));
}


$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$query = "SELECT * FROM `IT202-S24-ProductDetails` WHERE id = :id";
$db = getDB();
$stmt = $db->prepare($query);
$stmt->bindValue(':id', $item_id, PDO::PARAM_INT);

$result = [];
try {
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching item details: " . var_export($e, true));
    flash("Unhandled error occurred", "danger");
}

if (!$result) {
    flash("Item not found", "warning");
    die(header("Location: " . get_url('public_list_items.php')));
}


function format_json($json) {
    $data = json_decode($json, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return $data;
    } else {
        return $json; 
    }
}


$about_product = format_json($result['about_product']);
$description = $result['product_description'];
$information = format_json($result['product_information']);
$rating_distribution = format_json($result['rating_distribution']);
$product_photos = format_json($result['product_photos']);
$product_details = format_json($result['product_details']);
$customer_reviews = $result['customers_say'];
$category_path = format_json($result['category_path']);
$product_variations = format_json($result['product_variations']);
?>

<div class="container">
    <div class="item-header">
        <h1><?php echo htmlspecialchars($result['product_title']); ?></h1>
        <p class="item-price">Price: <span><?php echo htmlspecialchars($result['product_price']); ?> <?php echo htmlspecialchars($result['currency']); ?></span></p>
        <p class="item-rating">Rating: <span><?php echo htmlspecialchars($result['product_star_rating']); ?> (<?php echo htmlspecialchars($result['product_num_ratings']); ?> ratings)</span></p>
        <div class="item-actions">
            <a href="<?php echo get_url('admin/edit_item.php?id=' . urlencode($item_id)); ?>" class="btn btn-edit">Edit</a>
            <a href="<?php echo get_url('admin/delete_item.php?id=' . urlencode($item_id)); ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this item?');">Delete</a>
        </div>
    </div>

    <section class="item-section">
        <h2>About Product</h2>
        <?php if (is_array($about_product)): ?>
            <ul>
                <?php foreach ($about_product as $point): ?>
                    <li><?php echo htmlspecialchars($point); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p><?php echo htmlspecialchars($about_product); ?></p>
        <?php endif; ?>
    </section>

    <section class="item-section">
        <h2>Description</h2>
        <p><?php echo htmlspecialchars($description); ?></p>
    </section>

    <section class="item-section">
        <h2>Information</h2>
        <?php if (is_array($information)): ?>
            <ul>
                <?php foreach ($information as $key => $value): ?>
                    <li><strong><?php echo htmlspecialchars($key); ?>:</strong> <?php echo htmlspecialchars($value); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p><?php echo htmlspecialchars($information); ?></p>
        <?php endif; ?>
    </section>

    <section class="item-section">
        <h2>Rating Distribution</h2>
        <?php if (is_array($rating_distribution)): ?>
            <ul>
                <?php foreach ($rating_distribution as $rating => $count): ?>
                    <li><?php echo htmlspecialchars($rating); ?> stars: <?php echo htmlspecialchars($count); ?> votes</li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p><?php echo htmlspecialchars($rating_distribution); ?></p>
        <?php endif; ?>
    </section>

    <section class="item-section">
        <h2>Product Photos</h2>
        <?php if (is_array($product_photos)): ?>
            <div class="photo-gallery">
                <?php foreach ($product_photos as $photo): ?>
                    <img src="<?php echo htmlspecialchars($photo); ?>" alt="Product Photo" class="img-thumbnail">
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p><?php echo htmlspecialchars($product_photos); ?></p>
        <?php endif; ?>
    </section>

    <section class="item-section">
        <h2>Product Details</h2>
        <?php if (is_array($product_details)): ?>
            <ul>
                <?php foreach ($product_details as $key => $value): ?>
                    <li><strong><?php echo htmlspecialchars($key); ?>:</strong> <?php echo htmlspecialchars($value); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p><?php echo htmlspecialchars($product_details); ?></p>
        <?php endif; ?>
    </section>

    <section class="item-section">
        <h2>Customer Reviews</h2>
        <p><?php echo htmlspecialchars($customer_reviews); ?></p>
    </section>

    <section class="item-section">
        <h2>Category Path</h2>
        <?php if (is_array($category_path)): ?>
            <ul>
                <?php foreach ($category_path as $category): ?>
                    <li><a href="<?php echo htmlspecialchars($category['link']); ?>"><?php echo htmlspecialchars($category['name']); ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p><?php echo htmlspecialchars($category_path); ?></p>
        <?php endif; ?>
    </section>

    <section class="item-section">
        <h2>Product Variations</h2>
        <?php if (is_array($product_variations)): ?>
            <ul>
                <?php foreach ($product_variations as $key => $values): ?>
                    <li><strong><?php echo htmlspecialchars($key); ?>:</strong>
                        <ul>
                            <?php foreach ($values as $variation): ?>
                                <li>
                                    <?php echo htmlspecialchars($variation['value']); ?>
                                    <?php if (isset($variation['photo'])): ?>
                                        <img src="<?php echo htmlspecialchars($variation['photo']); ?>" alt="Variation Photo" class="img-thumbnail">
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p><?php echo htmlspecialchars($product_variations); ?></p>
        <?php endif; ?>
    </section>
</div>

<?php
require_once(__DIR__ . "/../../../partials/flash.php");
?>
<link rel="stylesheet" href="<?php echo get_url('styles.css'); ?>">








