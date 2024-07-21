<?php
session_start();
require(__DIR__ . "/../../../lib/functions.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("home.php")));
}

$id = se($_GET, "id", -1, false);
if ($id < 1) {
    flash("Invalid ID passed to delete", "danger");
    die(header("Location: " . get_url("admin/list_items.php")));
}

$db = getDB();
$query = "SELECT * FROM `IT202-S24-ProductDetails` WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->execute([":id" => $id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    flash("Item not found", "warning");
    die(header("Location: " . get_url("admin/list_items.php")));
}

// Ensure the entity can be deleted by the user (e.g., by Admin)
if (!has_role("Admin")) {
    flash("You don't have permission to delete this item", "warning");
    die(header("Location: " . get_url("admin/list_items.php")));
}

try {
    $query = "DELETE FROM `IT202-S24-ProductDetails` WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([":id" => $id]);

    if ($stmt->rowCount() > 0) {
        flash("Deleted record with id $id", "success");
    } else {
        flash("Failed to delete record with id $id", "danger");
    }
} catch (Exception $e) {
    error_log("Error deleting item with id $id: " . var_export($e, true));
    flash("Error deleting record", "danger");
}

// Redirect back to the list_items.php with filters/sorting applied
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : get_url('admin/list_items.php');

// Extract query parameters from the referer URL
$query_params = parse_url($referer, PHP_URL_QUERY);

if ($query_params) {
    // Append filters and sort parameters to the list_items.php URL
    $redirect_url = get_url('admin/list_items.php') . '?' . $query_params;
} else {
    // Default URL if no query parameters are found
    $redirect_url = get_url('admin/list_items.php');
}

die(header("Location: " . $redirect_url));
?>