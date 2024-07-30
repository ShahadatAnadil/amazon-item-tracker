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
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'product_title';
$filter_price_min = isset($_GET['filter_price_min']) ? floatval($_GET['filter_price_min']) : 0;
$filter_price_max = isset($_GET['filter_price_max']) ? floatval($_GET['filter_price_max']) : PHP_INT_MAX;
$filter_rating_min = isset($_GET['filter_rating_min']) ? floatval($_GET['filter_rating_min']) : 0;
$filter_rating_max = isset($_GET['filter_rating_max']) ? floatval($_GET['filter_rating_max']) : 5;
$search_title = isset($_GET['search_title']) ? trim($_GET['search_title']) : '';


$query = "SELECT uf.id, p.id as product_id, p.product_title, p.product_price, p.currency, p.product_star_rating, p.product_photo
          FROM user_favorites uf
          JOIN `IT202-S24-ProductDetails` p ON uf.item_id = p.id
          WHERE uf.user_id = :user_id
          AND p.`product_price` BETWEEN :filter_price_min AND :filter_price_max
          AND p.`product_star_rating` BETWEEN :filter_rating_min AND :filter_rating_max";

if (!empty($search_title)) {
    $query .= " AND p.`product_title` LIKE :search_title";
}

switch ($sort_by) {
    case 'price_asc':
        $query .= " ORDER BY p.`product_price` ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY p.`product_price` DESC";
        break;
    case 'rating_asc':
        $query .= " ORDER BY p.`product_star_rating` ASC";
        break;
    case 'rating_desc':
        $query .= " ORDER BY p.`product_star_rating` DESC";
        break;
    case 'title':
    default:
        $query .= " ORDER BY p.`product_title` ASC";
        break;
}

$total_query = "SELECT COUNT(*) as total
                FROM user_favorites uf
                JOIN `IT202-S24-ProductDetails` p ON uf.item_id = p.id
                WHERE uf.user_id = :user_id
                AND p.`product_price` BETWEEN :filter_price_min AND :filter_price_max
                AND p.`product_star_rating` BETWEEN :filter_rating_min AND :filter_rating_max";

if (!empty($search_title)) {
    $total_query .= " AND p.`product_title` LIKE :search_title";
}

$query .= " LIMIT :limit OFFSET :offset";

$total_stmt = $db->prepare($total_query);
$total_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$total_stmt->bindValue(':filter_price_min', $filter_price_min, PDO::PARAM_STR);
$total_stmt->bindValue(':filter_price_max', $filter_price_max, PDO::PARAM_STR);
$total_stmt->bindValue(':filter_rating_min', $filter_rating_min, PDO::PARAM_STR);
$total_stmt->bindValue(':filter_rating_max', $filter_rating_max, PDO::PARAM_STR);
if (!empty($search_title)) {
    $total_stmt->bindValue(':search_title', '%' . $search_title . '%', PDO::PARAM_STR);
}

$total_stmt->execute();
$total_items = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $db->prepare($query);
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt->bindValue(':filter_price_min', $filter_price_min, PDO::PARAM_STR);
$stmt->bindValue(':filter_price_max', $filter_price_max, PDO::PARAM_STR);
$stmt->bindValue(':filter_rating_min', $filter_rating_min, PDO::PARAM_STR);
$stmt->bindValue(':filter_rating_max', $filter_rating_max, PDO::PARAM_STR);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
if (!empty($search_title)) {
    $stmt->bindValue(':search_title', '%' . $search_title . '%', PDO::PARAM_STR);
}

$favorites = [];
try {
    $stmt->execute();
    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $filtered_items_count = count($favorites);
} catch (PDOException $e) {
    error_log("Error fetching favorites: " . var_export($e, true));
    flash("Unhandled error occurred", "danger");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_favorite_id'])) {
    $remove_favorite_id = intval($_POST['remove_favorite_id']);
    $stmt = $db->prepare("DELETE FROM user_favorites WHERE id = :id AND user_id = :user_id");
    try {
        $stmt->execute([':id' => $remove_favorite_id, ':user_id' => $user_id]);
        flash("Item removed from favorites", "success");
        header("Location: favorites.php");
        exit;
    } catch (PDOException $e) {
        error_log("Error removing item from favorites: " . var_export($e, true));
        flash("Unhandled error occurred", "danger");
    }
}
// COMMENT FOR HANDLING API DATA ASSOCIATION PULL REQUEST

$total_pages = ceil($total_items / $limit);
$current_page = floor($offset / $limit) + 1;
?>

<div class="container">
    <h1>My Favorites</h1>

    <!-- Filters -->
    <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="filter-form mb-4">
        <div class="row mb-3">
            <div class="col-md-3">
                <label for="search_title">Search by Title:</label>
                <input type="text" name="search_title" id="search_title" value="<?php echo htmlspecialchars($search_title); ?>" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="filter_price_min">Price Min:</label>
                <input type="number" name="filter_price_min" id="filter_price_min" value="<?php echo htmlspecialchars($filter_price_min); ?>" step="0.01" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="filter_price_max">Price Max:</label>
                <input type="number" name="filter_price_max" id="filter_price_max" value="<?php echo htmlspecialchars($filter_price_max); ?>" step="0.01" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="filter_rating_min">Rating Min:</label>
                <input type="number" name="filter_rating_min" id="filter_rating_min" value="<?php echo htmlspecialchars($filter_rating_min); ?>" step="0.1" min="0" max="5" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="filter_rating_max">Rating Max:</label>
                <input type="number" name="filter_rating_max" id="filter_rating_max" value="<?php echo htmlspecialchars($filter_rating_max); ?>" step="0.1" min="0" max="5" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="sort_by">Sort By:</label>
                <select name="sort_by" id="sort_by" class="form-select">
                    <option value="title" <?php echo ($sort_by === 'title') ? 'selected' : ''; ?>>Title (A-Z)</option>
                    <option value="price_asc" <?php echo ($sort_by === 'price_asc') ? 'selected' : ''; ?>>Price (Lowest to Highest)</option>
                    <option value="price_desc" <?php echo ($sort_by === 'price_desc') ? 'selected' : ''; ?>>Price (Highest to Lowest)</option>
                    <option value="rating_asc" <?php echo ($sort_by === 'rating_asc') ? 'selected' : ''; ?>>Rating (Lowest to Highest)</option>
                    <option value="rating_desc" <?php echo ($sort_by === 'rating_desc') ? 'selected' : ''; ?>>Rating (Highest to Lowest)</option>
                </select>
            </div>
        </div>
        <div class="d-flex justify-content-between">
            <input type="submit" value="Apply Filters" class="btn btn-primary">
            <input type="button" value="Clear Filters" class="btn btn-secondary" onclick="clearFilters()">
        </div>
    </form>

    <p>Showing <?php echo $filtered_items_count; ?> out of <?php echo $total_items; ?> items:</p>

    <?php if (empty($favorites)): ?>
        <p>You have no favorite items.</p>
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
                            <div class="d-flex justify-content-between">
                                <a href="<?php echo get_url('Project/item.php?id=' . urlencode($item['product_id'])); ?>" class="btn btn-primary">View</a>
                                <form method="POST" action="favorites.php">
                                    <button type="submit" name="remove_favorite_id" value="<?php echo htmlspecialchars($item['id']); ?>" class="btn btn-danger">Remove</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between">
            <button class="btn btn-outline-primary" <?php if ($current_page <= 1) echo 'disabled'; ?> onclick="window.location.href='?<?php echo http_build_query(array_merge($_GET, ['offset' => max(0, $offset - $limit)])); ?>'">Previous</button>
            <span>Page <?php echo $current_page; ?> of <?php echo $total_pages; ?></span>
            <button class="btn btn-outline-primary" <?php if ($current_page >= $total_pages) echo 'disabled'; ?> onclick="window.location.href='?<?php echo http_build_query(array_merge($_GET, ['offset' => $offset + $limit])); ?>'">Next</button>
        </div>
    <?php endif; ?>
</div>

<script>
function clearFilters() {
    window.location.href = "<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>";
}
</script>

<?php
require(__DIR__ . "/../../partials/flash.php");
?>