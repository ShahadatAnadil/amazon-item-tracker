<?php
session_start();
require(__DIR__ . "/../../../lib/functions.php");
require(__DIR__ . "/../../../partials/nav.php");
require_once(__DIR__ . "/../../../lib/render_functions.php");

error_log("Current script path: " . __FILE__);

// Ensure the user has the Admin role
if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("home.php")));
}

// Fetch the ID from the URL query parameters
$id = se($_GET, "id", -1, false);
if ($id < 1) {
    flash("Invalid ID passed to delete", "danger");
    die(header("Location: " . get_url("admin/list_items.php")));
}

// Fetch the item from the database
$db = getDB();
$query = "SELECT * FROM `IT202-S24-ProductDetails` WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->execute([":id" => $id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    flash("Item not found", "warning");
    die(header("Location: " . get_url("admin/list_items.php")));
}

try {
    $db->beginTransaction();
    
    // Delete related records from other tables
    $deleteFavoritesQuery = "DELETE FROM user_favorites WHERE item_id = :id";
    $stmt = $db->prepare($deleteFavoritesQuery);
    $stmt->execute([":id" => $id]);

    $deleteOrderItemsQuery = "DELETE FROM OrderItems WHERE item_id = :id";
    $stmt = $db->prepare($deleteOrderItemsQuery);
    $stmt->execute([":id" => $id]);

    // Perform the deletion from the main table
    $query = "DELETE FROM `IT202-S24-ProductDetails` WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([":id" => $id]);

    if ($stmt->rowCount() > 0) {
        flash("Deleted record with id $id", "success");
    } else {
        flash("Failed to delete record with id $id", "danger");
    }
    
    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    error_log("Error deleting item with id $id: " . var_export($e, true));
    flash("Error deleting record", "danger");
}

// Redirect back to the previous page with filters/sorting applied
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : get_url('admin/list_items.php');
$query_params = parse_url($referer, PHP_URL_QUERY);

if ($query_params) {
    $redirect_url = get_url('admin/list_items.php') . '?' . $query_params;
} else {
    $redirect_url = get_url('admin/list_items.php');
}

error_log("Redirecting to: " . $redirect_url);
die(header("Location: " . $redirect_url));
?>