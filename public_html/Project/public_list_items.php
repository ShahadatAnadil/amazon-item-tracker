<?php
require(__DIR__ . "/../../partials/nav.php");
require_once(__DIR__ . "/../../lib/render_functions.php");
require_once(__DIR__ . "/../../lib/item_api.php");

// Ensure the user is logged in to access this page
if (!isset($_SESSION['user'])) {
    flash("You must be logged in to view this page", "warning");
    die(header("Location: $BASE_PATH/login.php"));
}

// Default values
$default_limit = 10;
$default_min_price = 0;
$default_max_price = 1000000000;
$default_min_rating = 0;
$default_max_rating = 5;

// Get user input with validation
$filter = isset($_GET['filter']) ? htmlspecialchars($_GET['filter']) : '';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : $default_min_price;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : $default_max_price;
$min_rating = isset($_GET['min_rating']) ? (float)$_GET['min_rating'] : $default_min_rating;
$max_rating = isset($_GET['max_rating']) ? (float)$_GET['max_rating'] : $default_max_rating;
$sort = isset($_GET['sort']) ? htmlspecialchars($_GET['sort']) : 'created'; // Default sorting by creation date
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : $default_limit;

// Ensure limit is within the acceptable range
$limit = ($limit >= 1 && $limit <= 100) ? $limit : $default_limit;

// Prepare SQL query with filter, sorting, and limiting
$sort_column = 'created'; // Default sort column

switch ($sort) {
    case 'created':
        $sort_column = 'created';
        break;
    case 'title_asc':
        $sort_column = 'product_title ASC';
        break;
    case 'title_desc':
        $sort_column = 'product_title DESC';
        break;
    case 'price_asc':
        $sort_column = 'product_price ASC';
        break;
    case 'price_desc':
        $sort_column = 'product_price DESC';
        break;
    case 'rating_asc':
        $sort_column = 'product_star_rating ASC';
        break;
    case 'rating_desc':
        $sort_column = 'product_star_rating DESC';
        break;
}

$query = "SELECT id, asin, product_title, product_price, currency, country, product_star_rating, product_num_ratings, product_url, product_photo, product_num_offers, product_availability, is_best_seller, is_amazon_choice, is_prime, climate_pledge_friendly, sales_volume, about_product, product_description, product_information, rating_distribution, product_photos, product_details, customers_say, category_path, product_variations, created, modified 
          FROM `IT202-S24-ProductDetails` 
          WHERE product_title LIKE :filter 
            AND product_price BETWEEN :min_price AND :max_price
            AND product_star_rating BETWEEN :min_rating AND :max_rating
          ORDER BY $sort_column
          LIMIT :limit";

$db = getDB();
$stmt = $db->prepare($query);
$stmt->bindValue(':filter', '%' . $filter . '%', PDO::PARAM_STR);
$stmt->bindValue(':min_price', $min_price, PDO::PARAM_STR);
$stmt->bindValue(':max_price', $max_price, PDO::PARAM_STR);
$stmt->bindValue(':min_rating', $min_rating, PDO::PARAM_STR);
$stmt->bindValue(':max_rating', $max_rating, PDO::PARAM_STR);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

$results = [];
try {
    $stmt->execute();
    $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($r) {
        $results = $r;
    }
} catch (PDOException $e) {
    error_log("Error fetching items: " . var_export($e, true));
    flash("Unhandled error occurred", "danger");
}

$table = [
    "data" => $results,
    "title" => "Latest Items",
    "ignored_columns" => ["id"],
    "view_url" => get_url("public_view_item.php"),
];
?>

<div class="container-fluid">
    <h3>List Items</h3>

    <!-- Filter, Sort, and Limit Form -->
    <form method="GET" action="public_list_items.php" class="filter-form">
        <div class="filter-group">
            <label for="filter">Filter by Title:</label>
            <input type="text" id="filter" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
        </div>

        <div class="filter-group">
            <label for="min_price">Min Price:</label>
            <input type="number" id="min_price" name="min_price" step="0.01" value="<?php echo htmlspecialchars($min_price); ?>">
        </div>

        <div class="filter-group">
            <label for="max_price">Max Price:</label>
            <input type="number" id="max_price" name="max_price" step="0.01" value="<?php echo htmlspecialchars($max_price); ?>">
        </div>

        <div class="filter-group">
            <label for="min_rating">Min Rating:</label>
            <input type="number" id="min_rating" name="min_rating" step="0.1" min="0" max="5" value="<?php echo htmlspecialchars($min_rating); ?>">
        </div>

        <div class="filter-group">
            <label for="max_rating">Max Rating:</label>
            <input type="number" id="max_rating" name="max_rating" step="0.1" min="0" max="5" value="<?php echo htmlspecialchars($max_rating); ?>">
        </div>

        <div class="filter-group">
            <label for="sort">Sort by:</label>
            <select id="sort" name="sort">
                <option value="created" <?php echo ($sort === 'created' ? 'selected' : ''); ?>>Date Created</option>
                <option value="title_asc" <?php echo ($sort === 'title_asc' ? 'selected' : ''); ?>>Title (A to Z)</option>
                <option value="title_desc" <?php echo ($sort === 'title_desc' ? 'selected' : ''); ?>>Title (Z to A)</option>
                <option value="price_asc" <?php echo ($sort === 'price_asc' ? 'selected' : ''); ?>>Price (Low to High)</option>
                <option value="price_desc" <?php echo ($sort === 'price_desc' ? 'selected' : ''); ?>>Price (High to Low)</option>
                <option value="rating_asc" <?php echo ($sort === 'rating_asc' ? 'selected' : ''); ?>>Rating (Low to High)</option>
                <option value="rating_desc" <?php echo ($sort === 'rating_desc' ? 'selected' : ''); ?>>Rating (High to Low)</option>
            </select>
        </div>

        <div class="filter-group">
            <label for="limit">Limit:</label>
            <input type="number" id="limit" name="limit" min="1" max="100" value="<?php echo htmlspecialchars($limit); ?>">
        </div>

        <button type="submit">Apply</button>
        <button type="button" class="btn btn-clear" onclick="clearFilters()">Clear Filters</button>
    </form>

    <?php if ($results): ?>
        <?php foreach ($results as $row): ?>
            <div class="item-summary">
                <h4><?php echo htmlspecialchars($row['product_title']); ?></h4>
                <p>Price: <?php echo htmlspecialchars($row['product_price']); ?> <?php echo htmlspecialchars($row['currency']); ?></p>
                <p>Rating: <?php echo htmlspecialchars($row['product_star_rating']); ?> (<?php echo htmlspecialchars($row['product_num_ratings']); ?> ratings)</p>
                <p><a href="<?php echo htmlspecialchars($row['product_url']); ?>" target="_blank">View Product</a></p>
                <a href="<?php echo get_url('public_view_item.php?id=' . urlencode($row['id'])); ?>">View Details</a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No results available</p>
    <?php endif; ?>
</div>

<script>
function clearFilters() {
    window.location.href = "<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>";
}
</script>

<?php
require_once(__DIR__ . "/../../partials/flash.php");
?>
<link rel="stylesheet" href="<?php echo get_url('styles.css'); ?>">