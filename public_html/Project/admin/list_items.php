<?php
require(__DIR__ . "/../../../partials/nav.php");
require_once(__DIR__ . "/../../../lib/render_functions.php");
require_once(__DIR__ . "/../../../lib/item_api.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}

$query = "SELECT id, asin, product_title, product_price, currency, country, product_star_rating, product_num_ratings, product_url, product_photo, product_num_offers, product_availability, is_best_seller, is_amazon_choice, is_prime, climate_pledge_friendly, sales_volume, about_product, product_description, product_information, rating_distribution, product_photos, product_details, customers_say, category_path, product_variations, created, modified FROM `IT202-S24-ProductDetails` ORDER BY created DESC LIMIT 500";
$db = getDB();
$stmt = $db->prepare($query);
$results = [];
try {
    $stmt->execute();
    $r = $stmt->fetchAll();
    if ($r) {
        $results = $r;
    }
} catch (PDOException $e) {
    error_log("Error fetching items: " . var_export($e, true));
    flash("Unhandled error occurred", "danger");
}

$table = ["data" => $results, "title" => "Latest Items", "ignored_columns" => ["id"], "edit_url"=>get_url("admin/edit_item.php")];
?>

<div class="container-fluid">
    <h3>List Items</h3>
    <?php render_table($table); ?>
</div>

<?php
require_once(__DIR__ . "/../../../partials/flash.php");
?>