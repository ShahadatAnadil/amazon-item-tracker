<?php
require(__DIR__ . "/../../../partials/nav.php");
require_once(__DIR__ . "/../../../lib/render_functions.php");
require_once(__DIR__ . "/../../../lib/item_api.php");

if (!isset($_SESSION['user'])) {
    flash("You must be logged in to view this page", "warning");
    die(header("Location: $BASE_PATH/login.php"));
}

if (!has_role("Admin")) {
    flash("You don't have permission to edit or delete this item", "warning");
    die(header("Location: $BASE_PATH/home.php"));
}

// Set default values for filters and sorting
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'product_title';
$filter_price_min = isset($_GET['filter_price_min']) ? floatval($_GET['filter_price_min']) : 0;
$filter_price_max = isset($_GET['filter_price_max']) ? floatval($_GET['filter_price_max']) : PHP_INT_MAX;
$filter_rating_min = isset($_GET['filter_rating_min']) ? floatval($_GET['filter_rating_min']) : 0;
$filter_rating_max = isset($_GET['filter_rating_max']) ? floatval($_GET['filter_rating_max']) : 5;

// Validate limit
if ($limit < 1 || $limit > 100) {
    $limit = 10;
}

// Construct the SQL query
$query = "SELECT * FROM `IT202-S24-ProductDetails` 
          WHERE `product_price` BETWEEN :filter_price_min AND :filter_price_max
          AND `product_star_rating` BETWEEN :filter_rating_min AND :filter_rating_max";

switch ($sort_by) {
    case 'price_asc':
        $query .= " ORDER BY `product_price` ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY `product_price` DESC";
        break;
    case 'rating_asc':
        $query .= " ORDER BY `product_star_rating` ASC";
        break;
    case 'rating_desc':
        $query .= " ORDER BY `product_star_rating` DESC";
        break;
    case 'title':
    default:
        $query .= " ORDER BY `product_title` ASC";
        break;
}

$query .= " LIMIT :limit OFFSET :offset";

$db = getDB();
$stmt = $db->prepare($query);
$stmt->bindValue(':filter_price_min', $filter_price_min, PDO::PARAM_STR);
$stmt->bindValue(':filter_price_max', $filter_price_max, PDO::PARAM_STR);
$stmt->bindValue(':filter_rating_min', $filter_rating_min, PDO::PARAM_STR);
$stmt->bindValue(':filter_rating_max', $filter_rating_max, PDO::PARAM_STR);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$results = [];
try {
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching items: " . var_export($e, true));
    flash("Unhandled error occurred", "danger");
}
?>

<div class="container">
    <h1>Product List</h1>

    <!-- Filters and Sort Options -->
    <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="filter-form">
        <div class="filter-group">
            <label for="filter_price_min">Price Min:</label>
            <input type="number" name="filter_price_min" id="filter_price_min" value="<?php echo htmlspecialchars($filter_price_min); ?>" step="0.01">
        </div>
        <div class="filter-group">
            <label for="filter_price_max">Price Max:</label>
            <input type="number" name="filter_price_max" id="filter_price_max" value="<?php echo htmlspecialchars($filter_price_max); ?>" step="0.01">
        </div>
        <div class="filter-group">
            <label for="filter_rating_min">Rating Min:</label>
            <input type="number" name="filter_rating_min" id="filter_rating_min" value="<?php echo htmlspecialchars($filter_rating_min); ?>" step="0.1" min="0" max="5">
        </div>
        <div class="filter-group">
            <label for="filter_rating_max">Rating Max:</label>
            <input type="number" name="filter_rating_max" id="filter_rating_max" value="<?php echo htmlspecialchars($filter_rating_max); ?>" step="0.1" min="0" max="5">
        </div>
        <div class="filter-group">
            <label for="sort_by">Sort By:</label>
            <select name="sort_by" id="sort_by">
                <option value="title" <?php echo ($sort_by === 'title') ? 'selected' : ''; ?>>Title (A-Z)</option>
                <option value="price_asc" <?php echo ($sort_by === 'price_asc') ? 'selected' : ''; ?>>Price (Lowest to Highest)</option>
                <option value="price_desc" <?php echo ($sort_by === 'price_desc') ? 'selected' : ''; ?>>Price (Highest to Lowest)</option>
                <option value="rating_asc" <?php echo ($sort_by === 'rating_asc') ? 'selected' : ''; ?>>Rating (Lowest to Highest)</option>
                <option value="rating_desc" <?php echo ($sort_by === 'rating_desc') ? 'selected' : ''; ?>>Rating (Highest to Lowest)</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="limit">Records Per Page:</label>
            <input type="number" name="limit" id="limit" value="<?php echo htmlspecialchars($limit); ?>" min="1" max="100">
        </div>
        <input type="submit" value="Apply Filters" class="btn">
        <input type="button" value="Clear Filters" class="btn btn-clear" onclick="clearFilters()">
    </form>

    <?php if (empty($results)): ?>
        <p>No results available.</p>
    <?php else: ?>
        <?php foreach ($results as $item): ?>
            <div class="item-summary">
                <h2><?php echo htmlspecialchars($item['product_title']); ?></h2>
                <p>Price: <?php echo htmlspecialchars($item['product_price']); ?> <?php echo htmlspecialchars($item['currency']); ?></p>
                <p>Rating: <?php echo htmlspecialchars($item['product_star_rating']); ?> (<?php echo htmlspecialchars($item['product_num_ratings']); ?> ratings)</p>
                <a href="<?php echo get_url('admin/view_item.php?id=' . urlencode($item['id'])); ?>" class="btn">View</a>
                <a href="<?php echo get_url('admin/edit_item.php?id=' . urlencode($item['id'])); ?>" class="btn btn-edit">Edit</a>
                <a href="<?php echo get_url('admin/delete_item.php?id=' . urlencode($item['id'])); ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this item?');">Delete</a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Pagination (if needed) -->
    <div class="pagination">
        <a href="?<?php echo http_build_query(array_merge($_GET, ['offset' => max(0, $offset - $limit)])); ?>">Previous</a>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['offset' => $offset + $limit])); ?>">Next</a>
    </div>
</div>

<script>
function clearFilters() {
    window.location.href = "<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>";
}
</script>

<link rel="stylesheet" href="<?php echo get_url('styles.css'); ?>">
<?php
require_once(__DIR__ . "/../../../partials/flash.php");
?>