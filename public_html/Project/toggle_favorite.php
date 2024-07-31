<?php
session_start();
require_once(__DIR__ . "/../../lib/functions.php");
require_once(__DIR__ . "/../../lib/db.php");
$response = ["success" => false, "message" => "An error occurred"];
//sha38 7/30/2024
if (isset($_GET['item_id'])) {
    $user_id = get_user_id();
    $item_id = intval($_GET['item_id']);

    if (!$user_id) {
        $response["message"] = "User is not logged in.";
        echo json_encode($response);
        exit;
    }
    $db = getDB();
    $itemCheckQuery = "SELECT id FROM `IT202-S24-ProductDetails` WHERE id = :item_id";
    $itemCheckStmt = $db->prepare($itemCheckQuery);
    $itemCheckStmt->bindValue(':item_id', $item_id, PDO::PARAM_INT);
    $itemCheckStmt->execute();
    $itemExists = $itemCheckStmt->fetch(PDO::FETCH_ASSOC);
    if ($itemExists) {
        $userCheckQuery = "SELECT id FROM `Users` WHERE id = :user_id";
        $userCheckStmt = $db->prepare($userCheckQuery);
        $userCheckStmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $userCheckStmt->execute();
        $userExists = $userCheckStmt->fetch(PDO::FETCH_ASSOC);

        if ($userExists) {
            $query = "SELECT id FROM user_favorites WHERE user_id = :user_id AND item_id = :item_id";
            $stmt = $db->prepare($query);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':item_id', $item_id, PDO::PARAM_INT);
            $stmt->execute();
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $query = "DELETE FROM user_favorites WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindValue(':id', $existing['id'], PDO::PARAM_INT);
                $stmt->execute();
                $response["success"] = true;
                $response["message"] = "Item removed from favorites";
                $response["action"] = "removed";
            } else {
                $query = "INSERT INTO user_favorites (user_id, item_id) VALUES (:user_id, :item_id)";
                $stmt = $db->prepare($query);
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindValue(':item_id', $item_id, PDO::PARAM_INT);
                $stmt->execute();
                $response["success"] = true;
                $response["message"] = "Item added to favorites";
                $response["action"] = "added";
            }
        } else {
            $response["message"] = "User does not exist."; //sha38 7/30/2024
        }
    } else {
        $response["message"] = "Item does not exist.";
    }
} else {
    $response["message"] = "Invalid item ID.";
}

echo json_encode($response);
?>