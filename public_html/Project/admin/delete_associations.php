<?php
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url('home.php')));
}

$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

error_log("Received item_id: " . $item_id);
error_log("Received user_id: " . $user_id);

if ($item_id < 1 || $user_id < 1) {
    flash("Invalid item or user ID", "danger");
    die(header("Location: " . get_url('admin/all_associations.php')));
}

$db = getDB();

try {
    $stmt = $db->prepare("DELETE FROM `user_favorites` WHERE item_id = :item_id AND user_id = :user_id");
    $stmt->execute([":item_id" => $item_id, ":user_id" => $user_id]);
    flash("Deleted association", "success");
} catch (PDOException $e) {
    error_log("Error deleting association: " . var_export($e, true));
    flash("Error deleting association", "danger");
}

die(header("Location: " . get_url('user_entities.php')));
?>