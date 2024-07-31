<?php
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH/login.php"));
}
//COMMENT FOR ALL USER ASSOCIATIONS PULL REQUEST
$search_username = isset($_GET['search_username']) ? trim($_GET['search_username']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'product_title';

if ($limit < 1 || $limit > 100) {
    $limit = 10;
}

$db = getDB();


$query = "SELECT p.*, 
                 u.id AS user_id, 
                 u.username, 
                 COUNT(f.user_id) as total_users
          FROM `IT202-S24-ProductDetails` p
          LEFT JOIN `user_favorites` f ON p.id = f.item_id
          LEFT JOIN `Users` u ON f.user_id = u.id
          WHERE 1=1";

if (!empty($search_username)) {
    $query .= " AND u.username LIKE :search_username";
}
//sha38 7/30/2024
$query .= " GROUP BY p.id, u.username";

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

$stmt = $db->prepare($query);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

if (!empty($search_username)) {
    $stmt->bindValue(':search_username', '%' . $search_username . '%', PDO::PARAM_STR);
}

$results = [];
$total_items = 0;
$total_pages = 0;
$current_page = 1;

try {
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // sha38 7/30/2024
    $count_query = "SELECT COUNT(DISTINCT p.id) as total FROM `IT202-S24-ProductDetails` p
                    LEFT JOIN `user_favorites` f ON p.id = f.item_id
                    LEFT JOIN `Users` u ON f.user_id = u.id
                    WHERE 1=1";

    if (!empty($search_username)) {
        $count_query .= " AND u.username LIKE :search_username";
    }

    $count_stmt = $db->prepare($count_query);

    if (!empty($search_username)) {
        $count_stmt->bindValue(':search_username', '%' . $search_username . '%', PDO::PARAM_STR);
    }

    $count_stmt->execute();
    $total_items = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $total_pages = ceil($total_items / $limit);
    $current_page = floor($offset / $limit) + 1;
} catch (PDOException $e) {
    error_log("Error fetching associations: " . var_export($e, true));
    flash("Unhandled error occurred", "danger");
}
?>

<div class="container">
    <h1>All User Associations</h1>
    <p>Showing <?php echo count($results); ?> out of <?php echo htmlspecialchars($total_items ?? ''); ?> items:</p>

<!--sha38 7/30/2024-->  
    <form method="GET" action="<?php echo get_url('admin/all_associations.php'); ?>" class="filter-form mb-4">
        <div class="row mb-3">
            <div class="col-md-3">
                <label for="search_username">Search by Username:</label>
                <input type="text" name="search_username" id="search_username" value="<?php echo htmlspecialchars($search_username ?? ''); ?>" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="limit">Records Per Page:</label>
                <input type="number" name="limit" id="limit" value="<?php echo htmlspecialchars($limit); ?>" min="1" max="100" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="sort_by">Sort By:</label>
                <select name="sort_by" id="sort_by" class="form-select">
                    <option value="title" <?php echo ($sort_by === 'title') ? 'selected' : ''; ?>>Title (A-Z)</option>
                    <option value="price_asc" <?php echo ($sort_by === 'price_asc') ? 'selected' : ''; ?>>Price (Lowest to Highest)</option>
                    <option value="price_desc" <?php echo ($sort_by === 'price_desc') ? 'selected' : ''; ?>>Price (Highest to Lowest)</option>
                </select>
            </div>
        </div>
        <div class="d-flex justify-content-between">
            <input type="submit" value="Apply Filters" class="btn btn-primary">
            <input type="button" value="Clear Filters" class="btn btn-secondary" onclick="clearFilters()">
        </div>
    </form>

    <div class="row">
        <?php if (empty($results)) : ?>
            <p>No results available.</p>
        <?php else : ?>
            <?php foreach ($results as $item) : ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($item['product_title'] ?? ''); ?></h5>
                            <p class="card-text">Price: <?php echo htmlspecialchars($item['product_price'] ?? ''); ?> <?php echo htmlspecialchars($item['currency'] ?? ''); ?></p>
                            <p class="card-text">Rating: <?php echo htmlspecialchars($item['product_star_rating'] ?? ''); ?></p> <!--sha38 7/30/2024-->
                            <p class="card-text">Associated with: <a href="<?php echo get_url('admin/user_profile.php?id=' . urlencode($item['user_id'] ?? '')); ?>"><?php echo htmlspecialchars($item['username'] ?? ''); ?></a></p>
                            <p class="card-text">Total Users Associated: <?php echo htmlspecialchars($item['total_users'] ?? ''); ?></p>
                            <div class="d-flex justify-content-between">
                                <a href="<?php echo get_url('item.php?id=' . urlencode($item['id'] ?? '')); ?>" class="btn btn-primary">View</a> <!--sha38 7/30/2024-->
                                <a href="<?php echo get_url('admin/delete_associations.php?item_id=' . urlencode($item['id'] ?? '') . '&user_id=' . urlencode($item['user_id'] ?? '')); ?>" class="btn btn-danger">Delete Association</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!--sha39 7/30/2024-->
    <div class="d-flex justify-content-between align-items-center">
        <a href="<?php echo get_url('admin/all_associations.php?' . http_build_query(array_merge($_GET, ['offset' => max(0, $offset - $limit)]))); ?>" class="btn btn-outline-primary <?php echo $current_page == 1 ? 'disabled' : ''; ?>">Previous</a>
        <span>Page <?php echo htmlspecialchars($current_page ?? ''); ?> of <?php echo htmlspecialchars($total_pages ?? ''); ?></span>
        <a href="<?php echo get_url('admin/all_associations.php?' . http_build_query(array_merge($_GET, ['offset' => $offset + $limit]))); ?>" class="btn btn-outline-primary <?php echo $current_page == $total_pages ? 'disabled' : ''; ?>">Next</a>
    </div>

   
    <div class="mt-4">
        <a href="<?php echo get_url('admin/delete_all_user_associations.php'); ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete all associations for all users?');">Delete All Associations</a>
    </div>
</div>

<script>
    function clearFilters() {
        window.location.href = "<?php echo get_url('admin/all_associations.php'); ?>";
    }
</script>

<?php
require(__DIR__ . "/../../../partials/flash.php");
?>