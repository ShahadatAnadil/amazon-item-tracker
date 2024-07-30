<?php
require(__DIR__ . "/../../partials/nav.php");
require_once(__DIR__ . "/../../lib/render_functions.php");
require_once(__DIR__ . "/../../lib/item_api.php");

if (!isset($_SESSION['user'])) {
    flash("You must be logged in to view this page", "warning");
    die(header("Location: " . get_url('login.php')));
}

$user_id = $_SESSION['user']['id'];


$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'product_title';
$filter_price_min = isset($_GET['filter_price_min']) ? floatval($_GET['filter_price_min']) : 0;
$filter_price_max = isset($_GET['filter_price_max']) ? floatval($_GET['filter_price_max']) : PHP_INT_MAX;
$filter_rating_min = isset($_GET['filter_rating_min']) ? floatval($_GET['filter_rating_min']) : 0;
$filter_rating_max = isset($_GET['filter_rating_max']) ? floatval($_GET['filter_rating_max']) : 5;
$search_title = isset($_GET['search_title']) ? trim($_GET['search_title']) : '';


if ($limit < 1 || $limit > 100) {
    $limit = 10;
}


$query = "SELECT p.*, 
                 IF(f.user_id IS NULL, 0, 1) as is_favorited
          FROM `IT202-S24-ProductDetails` p
          LEFT JOIN `user_favorites` f ON p.id = f.item_id AND f.user_id = :user_id
          WHERE p.`product_price` BETWEEN :filter_price_min AND :filter_price_max
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

$query .= " LIMIT :limit OFFSET :offset";

$db = getDB();
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

$results = [];
try {
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM `IT202-S24-ProductDetails` p
                          WHERE p.`product_price` BETWEEN :filter_price_min AND :filter_price_max
                          AND p.`product_star_rating` BETWEEN :filter_rating_min AND :filter_rating_max");
    $stmt->bindValue(':filter_price_min', $filter_price_min, PDO::PARAM_STR);
    $stmt->bindValue(':filter_price_max', $filter_price_max, PDO::PARAM_STR);
    $stmt->bindValue(':filter_rating_min', $filter_rating_min, PDO::PARAM_STR);
    $stmt->bindValue(':filter_rating_max', $filter_rating_max, PDO::PARAM_STR);
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
    <h1>Product List</h1>

    
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
                                <a href="<?php echo get_url('item.php?id=' . urlencode($item['id'])); ?>" class="btn btn-primary">View</a>
                                <button class="btn btn-link <?php echo $item['is_favorited'] ? 'favorited' : ''; ?>" onclick="toggleFavorite(<?php echo $item['id']; ?>)">
                                    <i class="fas fa-heart"></i>
                                </button>
                                <button class="btn btn-success" onclick="addToCart(<?php echo $item['id']; ?>)">Add to Cart</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    
    <div class="d-flex justify-content-between align-items-center">
        <a href="?<?php echo http_build_query(array_merge($_GET, ['offset' => max(0, $offset - $limit)])); ?>" class="btn btn-outline-primary <?php echo $current_page == 1 ? 'disabled' : ''; ?>">Previous</a>
        <span>Page <?php echo $current_page; ?> of <?php echo $total_pages; ?></span>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['offset' => $offset + $limit])); ?>" class="btn btn-outline-primary <?php echo $current_page == $total_pages ? 'disabled' : ''; ?>">Next</a>
    </div>
</div>


<div id="notification" class="notification"></div>

<script>
function clearFilters() {
    window.location.href = "<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>";
}

function toggleFavorite(itemId) {
    fetch(`toggle_favorite.php?item_id=${itemId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let btn = document.querySelector(`button[onclick="toggleFavorite(${itemId})"]`);
                if (data.action === "added") {
                    btn.classList.add("favorited");
                } else if (data.action === "removed") {
                    btn.classList.remove("favorited");
                }
                showNotification(data.message, "success");
            } else {
                showNotification(data.message, "error");
            }
        });
}

function addToCart(itemId) {
    fetch(`add_to_cart.php?item_id=${itemId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification("Item added to cart!", "success");
            } else {
                showNotification("Failed to add item to cart.", "error");
            }
        });
}

function showNotification(message, type) {
    const notification = document.getElementById("notification");
    notification.innerText = message;
    notification.className = `notification ${type}`;
    notification.style.display = "block";

    setTimeout(() => {
        notification.style.display = "none";
    }, 10000);
}

</script>

<style>
.notification {
    display: none;
    position: fixed;
    top: 10px;
    right: 10px;
    padding: 15px;
    border-radius: 5px;
    z-index: 1000;
}

.notification.success {
    background-color: #4caf50;
    color: white;
}

.notification.error {
    background-color: #f44336;
    color: white;
}
</style>