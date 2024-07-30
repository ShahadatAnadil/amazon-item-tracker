<?php
require_once(__DIR__ . "/../../lib/functions.php");
require_once(__DIR__ . "/../../lib/db.php");

$response = ["success" => false, "message" => "An error occurred"];

if (isset($_GET['item_id'])) {
    $user_id = get_user_id();
    $item_id = intval($_GET['item_id']);
    
    $db = getDB();
    // COMMENT FOR HANDLING API DATA ASSOCIATION PULL REQUEST
    
    $itemCheckQuery = "SELECT id FROM `IT202-S24-ProductDetails` WHERE id = :item_id";
    $itemCheckStmt = $db->prepare($itemCheckQuery);
    $itemCheckStmt->bindValue(':item_id', $item_id, PDO::PARAM_INT);
    $itemCheckStmt->execute();
    $itemExists = $itemCheckStmt->fetch(PDO::FETCH_ASSOC);

    
    $userCheckQuery = "SELECT id FROM `Users` WHERE id = :user_id";
    $userCheckStmt = $db->prepare($userCheckQuery);
    $userCheckStmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $userCheckStmt->execute();
    $userExists = $userCheckStmt->fetch(PDO::FETCH_ASSOC);

    if ($itemExists && $userExists) {
        
        $query = "INSERT INTO user_cart (user_id, item_id, quantity) VALUES (:user_id, :item_id, 1)";
        $stmt = $db->prepare($query);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':item_id', $item_id, PDO::PARAM_INT);
        $stmt->execute();
        $response["success"] = true;
        $response["message"] = "Item added to cart";
    } else {
        $response["message"] = "User or item does not exist.";
    }
} else {
    $response["message"] = "Invalid item ID.";
}

echo json_encode($response);
?>