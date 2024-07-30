<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$BASE_PATH = '/Project';

// Include all necessary files
require_once(__DIR__ . "/db.php");
require_once(__DIR__ . "/flash_messages.php");
require_once(__DIR__ . "/sanitizers.php");
require_once(__DIR__ . "/user_helpers.php");
require_once(__DIR__ . "/duplicate_user_details.php");
require_once(__DIR__ . "/reset_session.php");
require_once(__DIR__ . "/get_url.php");
require_once(__DIR__ . "/render_functions.php");
require_once(__DIR__ . "/load_api_keys.php");
require_once(__DIR__ . "/api_helper.php");
require_once(__DIR__ . "/item_api.php");
require_once(__DIR__ . "/safer_echo.php");

// IDK IF TO DELETE OR NOT
if (!function_exists('get_url')) {
    function get_url($dest) {
        global $BASE_PATH;
        if (str_starts_with($dest, "/")) {
            // handle absolute path
            return "$BASE_PATH$dest";
        }
        // handle relative path
        return "$BASE_PATH/$dest";
    }
}

// Helper function to check if a user is logged in
if (!function_exists('is_logged_in')) {
    function is_logged_in($redirect = false, $destination = "login.php")
    {
        $isLoggedIn = isset($_SESSION["user"]);
        if ($redirect && !$isLoggedIn) {
            flash("You must be logged in to view this page", "warning");
            die(header("Location: " . get_url($destination)));
        }
        return $isLoggedIn;
    }
}

// Helper function to get user ID
if (!function_exists('get_user_id')) {
    function get_user_id()
    {
        if (is_logged_in()) {
            return se($_SESSION["user"], "id", false, false);
        }
        return false;
    }
}

// Helper function to get username
if (!function_exists('get_username')) {
    function get_username()
    {
        if (is_logged_in()) {
            return se($_SESSION["user"], "username", "", false);
        }
        return "";
    }
}

// Helper function to get user email
if (!function_exists('get_user_email')) {
    function get_user_email()
    {
        if (is_logged_in()) {
            return se($_SESSION["user"], "email", "", false);
        }
        return "";
    }
}

function process_purchase($user_id, $items) {
    $db = getDB();
    $order_id = null;

    try {
        $db->beginTransaction();

        // Insert order into the Orders table
        $stmt = $db->prepare("INSERT INTO Orders (user_id, total_price, created) VALUES (:user_id, 0, NOW())");
        $stmt->execute([":user_id" => $user_id]);
        $order_id = $db->lastInsertId();

        // Insert items into OrderItems table and calculate total price
        $total_price = 0;
        foreach ($items as $item) {
            $stmt = $db->prepare("INSERT INTO OrderItems (order_id, item_id, quantity, unit_price) VALUES (:order_id, :item_id, :quantity, :unit_price)");
            $stmt->execute([
                ":order_id" => $order_id,
                ":item_id" => $item['id'],
                ":quantity" => $item['quantity'],
                ":unit_price" => $item['price']
            ]);
            $total_price += $item['quantity'] * $item['price'];
        }

        // Update the total price in the Orders table
        $stmt = $db->prepare("UPDATE Orders SET total_price = :total_price WHERE id = :order_id");
        $stmt->execute([
            ":total_price" => $total_price,
            ":order_id" => $order_id
        ]);

        $db->commit();
        return $order_id;
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error processing purchase: " . var_export($e, true));
        return false;
    }
}
?>