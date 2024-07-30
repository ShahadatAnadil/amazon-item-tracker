<?php
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH/home.php"));
}

$db = getDB();

try {
    $stmt = $db->prepare("DELETE FROM `user_favorites`");
    $stmt->execute();

    flash("All associations have been deleted", "success");
} catch (PDOException $e) {
    error_log("Error deleting all associations: " . var_export($e, true));
    flash("Error deleting all associations", "danger");
}

die(header("Location: " . get_url('admin/all_associations.php')));
?>