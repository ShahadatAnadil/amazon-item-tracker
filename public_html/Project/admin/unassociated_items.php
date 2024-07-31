<?php
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH/login.php"));
}
//COMMENT FOR UNASSOCIATED PULL REQUEST

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'product_title';
$search_title = isset($_GET['search_title']) ? trim($_GET['search_title']) : '';


if ($limit < 1 || $limit > 100) {
    $limit = 10;
}


$query = "SELECT p.*
          FROM `IT202-S24-ProductDetails` p
          LEFT JOIN `user_favorites` f ON p.id = f.item_id
          WHERE f.user_id IS NULL";

if (!empty($search_title)) {
    $query .= " AND p.`product_title` LIKE :search_title";
}
//sha38 7/30/2024
switch ($sort_by) {
    case 'price_asc':
        $query .= " ORDER BY p.`product_price` ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY p.`product_price` DESC";
        break;
    case 'title':
    default:
        $query .= " ORDER BY p.`product_title` ASC";
        break;
}

$query .= " LIMIT :limit OFFSET :offset";

$db = getDB();
$stmt = $db->prepare($query);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

if (!empty($search_title)) {
    $stmt->bindValue(':search_title', '%' . $search_title . '%', PDO::PARAM_STR);
}
//sha38 7/30/2024
$results = [];
try {
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM `IT202-S24-ProductDetails` p
                          LEFT JOIN `user_favorites` f ON p.id = f.item_id
                          WHERE f.user_id IS NULL");
    $stmt->execute();
    $total_items = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $total_pages = ceil($total_items / $limit);
    $current_page = floor($offset / $limit) + 1;

} catch (PDOException $e) {
    error_log("Error fetching items: " . var_export($e, true));
    flash("Unhandled error occurred", "danger");
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<div class="container">
    <h1>Unassociated Items</h1>
    <p>Total Items: <?php echo $total_items; ?> | Showing <?php echo count($results); ?> items</p>

    <!-- sha38 7/30/2024-->
    <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="filter-form mb-4">
        <div class="row mb-3">
            <div class="col-md-3">
                <label for="search_title">Search by Title:</label>
                <input type="text" name="search_title" id="search_title" value="<?php echo htmlspecialchars($search_title); ?>" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="sort_by">Sort By:</label>
                <select name="sort_by" id="sort_by" class="form-select">
                    <option value="title" <?php echo ($sort_by === 'title') ? 'selected' : ''; ?>>Title (A-Z)</option>
                    <option value="price_asc" <?php echo ($sort_by === 'price_asc') ? 'selected' : ''; ?>>Price (Lowest to Highest)</option>
                    <option value="price_desc" <?php echo ($sort_by === 'price_desc') ? 'selected' : ''; ?>>Price (Highest to Lowest)</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="limit">Records Per Page:</label>
                <input type="number" name="limit" id="limit" value="<?php echo htmlspecialchars($limit); ?>" min="1" max="100" class="form-control">
            </div>
        </div>
        <div class="d-flex justify-content-between">
            <input type="submit" value="Apply Filters" class="btn btn-primary">
            <input type="button" value="Clear Filters" class="btn btn-secondary" onclick="clearFilters()">
        </div>
    </form>

    <div class="row">
        <?php if (empty($results)): ?>
            <p>No results available.</p>
        <?php else: ?>
            <?php foreach ($results as $item): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <img src="<?php echo htmlspecialchars($item['product_photo']); ?>" class="card-img-top" alt="Product Image">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($item['product_title']); ?></h5>
                            <p class="card-text">Price: <?php echo htmlspecialchars($item['product_price']); ?> <?php echo htmlspecialchars($item['currency']); ?></p> <!--sha38 7/30/2024-->
                            <p class="card-text">Rating: <?php echo htmlspecialchars($item['product_star_rating']); ?> (<?php echo htmlspecialchars($item['product_num_ratings']); ?> ratings)</p>
                            <div class="d-flex justify-content-between">
                                <a href="<?php echo get_url('Project/item.php?id=' . urlencode($item['id'])); ?>" class="btn btn-primary">View</a> 
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
            <!--sha38 7/30/2024-->
    
    <div class="d-flex justify-content-between align-items-center">
        <a href="?<?php echo http_build_query(array_merge($_GET, ['offset' => max(0, $offset - $limit)])); ?>" class="btn btn-outline-primary <?php echo $current_page == 1 ? 'disabled' : ''; ?>">Previous</a>
        <span>Page <?php echo $current_page; ?> of <?php echo $total_pages; ?></span>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['offset' => $offset + $limit])); ?>" class="btn btn-outline-primary <?php echo $current_page == $total_pages ? 'disabled' : ''; ?>">Next</a>
    </div>
</div>

<script>
function clearFilters() {
    window.location.href = "<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>";
}
</script>

<?php
require(__DIR__ . "/../../../partials/flash.php");
?>