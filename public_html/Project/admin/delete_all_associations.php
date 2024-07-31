<?php
require(__DIR__ . "/../../../partials/nav.php");
//sha38 7/30/2024
if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH/home.php"));
}
//COMMENT FOR ALL USER ASSOCIATIONS PULL REQUEST
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : -1;
if ($user_id < 1) {
    flash("Invalid user ID", "danger");
    die(header("Location: $BASE_PATH/admin/all_associations.php"));
}

$db = getDB();

try {
    $stmt = $db->prepare("DELETE FROM `user_favorites` WHERE user_id = :user_id");
    $stmt->execute([":user_id" => $user_id]);

    flash("All associations for user ID $user_id have been deleted", "success");
} catch (PDOException $e) {
    error_log("Error deleting associations: " . var_export($e, true));
    flash("Error deleting associations", "danger");
}

header("Location: " . get_url('/Project/admin/all_associations.php'));
die();
?>