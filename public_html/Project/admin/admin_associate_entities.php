<?php
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH/login.php"));
}
//COMMENT FOR ASSOCIATING ANY ITEM WITH ANY USER ADMIN ONLY
$entity_query = isset($_GET['entity_query']) ? trim($_GET['entity_query']) : '';
$user_query = isset($_GET['user_query']) ? trim($_GET['user_query']) : '';

$entities = [];
$users = [];

if (!empty($entity_query)) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM `IT202-S24-ProductDetails` WHERE `product_title` LIKE :entity_query LIMIT 25");
    $stmt->execute([':entity_query' => '%' . $entity_query . '%']);
    $entities = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (!empty($user_query)) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM `Users` WHERE `username` LIKE :user_query LIMIT 25");
    $stmt->execute([':user_query' => '%' . $user_query . '%']);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_entities = isset($_POST['selected_entities']) ? $_POST['selected_entities'] : [];
    $selected_users = isset($_POST['selected_users']) ? $_POST['selected_users'] : [];
    $db = getDB();

    foreach ($selected_entities as $entity_id) {
        foreach ($selected_users as $user_id) {
            $stmt = $db->prepare("INSERT INTO `user_favorites` (`user_id`, `item_id`) VALUES (:user_id, :item_id)
                                  ON DUPLICATE KEY UPDATE `user_id` = :user_id, `item_id` = :item_id");
            $stmt->execute([':user_id' => $user_id, ':item_id' => $entity_id]);
        }
    }

    flash("Associations updated successfully", "success");
    header("Location: admin_associate_entities.php");
    exit;
}
?>

<div class="container">
    <h1>Admin Associate Entities with Users</h1>

    
    <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="mb-4">
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="entity_query">Entity Search:</label>
                <input type="text" name="entity_query" id="entity_query" value="<?php echo htmlspecialchars($entity_query); ?>" class="form-control">
            </div>
            <div class="col-md-6">
                <label for="user_query">User Search:</label>
                <input type="text" name="user_query" id="user_query" value="<?php echo htmlspecialchars($user_query); ?>" class="form-control">
            </div>
        </div>
        <input type="submit" value="Search" class="btn btn-primary">
    </form>

    <?php if (!empty($entities) && !empty($users)): ?>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="row">
                <div class="col-md-6">
                    <h3>Entities</h3>
                    <ul class="list-group">
                        <?php foreach ($entities as $entity): ?>
                            <li class="list-group-item">
                                <input type="checkbox" name="selected_entities[]" value="<?php echo htmlspecialchars($entity['id']); ?>">
                                <?php echo htmlspecialchars($entity['product_title']); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h3>Users</h3>
                    <ul class="list-group">
                        <?php foreach ($users as $user): ?>
                            <li class="list-group-item">
                                <input type="checkbox" name="selected_users[]" value="<?php echo htmlspecialchars($user['id']); ?>">
                                <?php echo htmlspecialchars($user['username']); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-success">Apply Associations</button>
            </div>
        </form>
    <?php elseif (empty($entities) && !empty($entity_query)): ?>
        <p>No entities found for the given search query.</p>
    <?php elseif (empty($users) && !empty($user_query)): ?>
        <p>No users found for the given search query.</p>
    <?php endif; ?>
</div>

<?php
require(__DIR__ . "/../../../partials/flash.php");
?>