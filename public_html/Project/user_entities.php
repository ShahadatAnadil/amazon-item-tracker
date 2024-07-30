<?php
require(__DIR__ . "/../../partials/nav.php");

if (!isset($_SESSION['user'])) {
    flash("You must be logged in to view this page", "warning");
    die(header("Location: $BASE_PATH/login.php"));
}

$user_id = $_SESSION['user']['id'];

// Set default values for filters and sorting
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'product_title';
$search_title = isset($_GET['search_title']) ? trim($_GET['search_title']) : '';

// Ensure limit is within allowed range
if ($limit < 1 || $limit > 100) {
    $limit = 10;
}

// Build the query with optional filters and sorting
$query = "SELECT p.*, 
                 IF(f.user_id IS NULL, 0, 1) as is_favorited
          FROM `IT202-S24-ProductDetails` p
          LEFT JOIN `user_favorites` f ON p.id = f.item_id AND f.user_id = :user_id
          WHERE f.user_id = :user_id";

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
    case 'title':
    default:
        $query .= " ORDER BY p.`product_title` ASC";
        break;
}

$query .= " LIMIT :limit OFFSET :offset";

$db = getDB();
$stmt = $db->prepare($query);
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

if (!empty($search_title)) {
    $stmt->bindValue(':search_title', '%' . $search_title . '%', PDO::PARAM_STR);
}

$results = [];
try {
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get the total number of items
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM `IT202-S24-ProductDetails` p
                          LEFT JOIN `user_favorites` f ON p.id = f.item_id AND f.user_id = :user_id
                          WHERE f.user_id = :user_id");
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $total_items = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $total_pages = ceil($total_items / $limit);
    $current_page = floor($offset / $limit) + 1;

} catch (PDOException $e) {
    error_log("Error fetching items: " . var_export($e, true));
    flash("Unhandled error occurred", "danger");
}
?>

<div class="container">
    <h1>My Associated Items</h1>
    <p>Total Items: <?php echo $total_items; ?> | Showing <?php echo count($results); ?> items</p>

    <!-- Filters and Sort Options -->
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
                            <p class="card-text">Price: <?php echo htmlspecialchars($item['product_price']); ?> <?php echo htmlspecialchars($item['currency']); ?></p>
                            <p class="card-text">Rating: <?php echo htmlspecialchars($item['product_star_rating']); ?> (<?php echo htmlspecialchars($item['product_num_ratings']); ?> ratings)</p>
                            <div class="d-flex justify-content-between">
                                <a href="<?php echo get_url('Project/item.php?id=' . urlencode($item['id'])); ?>" class="btn btn-primary">View</a>
                                <button class="btn btn-link <?php echo $item['is_favorited'] ? 'favorited' : ''; ?>" onclick="toggleFavorite(<?php echo $item['id']; ?>)">
                                    <i class="fas fa-heart"></i>
                                </button>
                                <button class="btn btn-danger" onclick="deleteAssociation(<?php echo $item['id']; ?>)">Delete</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination (if needed) -->
    <div class="d-flex justify-content-between align-items-center mt-3">
        <a href="?<?php echo http_build_query(array_merge($_GET, ['offset' => max(0, $offset - $limit)])); ?>" class="btn btn-outline-primary <?php echo $current_page == 1 ? 'disabled' : ''; ?>">Previous</a>
        <span>Page <?php echo $current_page; ?> of <?php echo $total_pages; ?></span>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['offset' => $offset + $limit])); ?>" class="btn btn-outline-primary <?php echo $current_page == $total_pages ? 'disabled' : ''; ?>">Next</a>
    </div>

    <div class="mt-4">
        <button class="btn btn-danger" onclick="removeAllAssociations()">Remove All Associations</button>
    </div>
</div>

<script>
function clearFilters() {
    window.location.href = "<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>";
}

function toggleFavorite(itemId) {
    fetch(`toggle_favorite.php?item_id=${itemId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Find the button and update the class
                let btn = document.querySelector(`button[onclick="toggleFavorite(${itemId})"]`);
                if (data.action === "added") {
                    btn.classList.add("favorited");
                } else if (data.action === "removed") {
                    btn.classList.remove("favorited");
                }
            } else {
                alert(data.message);
            }
        });
}

function deleteAssociation(itemId) {
    if (confirm("Are you sure you want to delete this association?")) {
        window.location.href = `delete_association.php?id=${itemId}`;
    }
}

function removeAllAssociations() {
    if (confirm("Are you sure you want to remove all associations?")) {
        window.location.href = `delete_all_associations.php?user_id=<?php echo $user_id; ?>`;
    }
}
</script>

<?php
require(__DIR__ . "/../../partials/flash.php");
?>