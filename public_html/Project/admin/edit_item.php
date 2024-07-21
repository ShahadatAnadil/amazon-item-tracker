<?php
require(__DIR__ . "/../../../partials/nav.php");
require_once(__DIR__ . "/../../../lib/render_functions.php");
require_once(__DIR__ . "/../../../lib/item_api.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}

$id = se($_GET, "id", -1, false);
$item = [];
if ($id > -1) {
    // Fetch
    $db = getDB();
    $query = "SELECT * FROM `IT202-S24-ProductDetails` WHERE id = :id";
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([":id" => $id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($r) {
            $item = $r;
        }
    } catch (PDOException $e) {
        error_log("Error fetching record: " . var_export($e, true));
        flash("Error fetching record", "danger");
    }
} else {
    flash("Invalid id passed", "danger");
    die(header("Location: " . get_url("admin/list_items.php")));
}

if (isset($_POST["asin"])) {
    foreach ($_POST as $k => $v) {
        if (in_array($k, [
            "asin", "product_title", "product_price", "product_original_price", "product_price_max", "currency", 
            "country", "product_star_rating", "product_num_ratings", "product_url", "product_photo", 
            "product_num_offers", "product_availability", "is_best_seller", "is_amazon_choice", "is_prime", 
            "climate_pledge_friendly", "sales_volume", "about_product", "product_description", "product_information", 
            "rating_distribution", "product_photos", "product_details", "customers_say", "category_path", "product_variations"
        ])) {
            if (in_array($k, ["is_best_seller", "is_amazon_choice", "is_prime", "climate_pledge_friendly"])) {
                $item[$k] = isset($v) ? 1 : 0;
            } else {
                $item[$k] = $v;
            }
        }
    }

    $db = getDB();
    $query = "UPDATE `IT202-S24-ProductDetails` SET ";
    $params = [];
    foreach ($item as $k => $v) {
        if ($k != "id") {
            $query .= "`$k` = :$k, ";
            $params[":$k"] = $v;
        }
    }
    $query = rtrim($query, ", ");
    $query .= " WHERE id = :id";
    $params[":id"] = $id;

    error_log("Query: " . $query);
    error_log("Params: " . var_export($params, true));

    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        flash("Updated record with ID " . $id, "success");
    } catch (PDOException $e) {
        error_log("Something broke with the query: " . var_export($e, true));
        flash("An error occurred", "danger");
    }
}

$form = [];
if ($item) {
    $form = [
        ["type" => "text", "name" => "asin", "placeholder" => "Item ASIN", "label" => "Item ASIN", "value" => $item["asin"], "rules" => ["required" => "required"]],
        ["name" => "product_title", "placeholder" => "Product Title", "label" => "Product Title", "value" => $item["product_title"]],
        ["name" => "product_price", "placeholder" => "Product Price", "label" => "Product Price", "value" => $item["product_price"]],
        ["name" => "product_original_price", "placeholder" => "Original Price", "label" => "Original Price", "value" => $item["product_original_price"]],
        ["name" => "product_price_max", "placeholder" => "Max Price", "label" => "Max Price", "value" => $item["product_price_max"]],
        ["name" => "currency", "placeholder" => "Currency", "label" => "Currency", "value" => $item["currency"]],
        ["name" => "country", "placeholder" => "Country", "label" => "Country", "value" => $item["country"]],
        ["name" => "product_star_rating", "placeholder" => "Product Star Rating", "label" => "Product Star Rating", "value" => $item["product_star_rating"]],
        ["name" => "product_num_ratings", "placeholder" => "Number of Ratings", "label" => "Number of Ratings", "value" => $item["product_num_ratings"]],
        ["name" => "product_url", "placeholder" => "Product URL", "label" => "Product URL", "value" => $item["product_url"]],
        ["name" => "product_photo", "placeholder" => "Product Photo", "label" => "Product Photo", "value" => $item["product_photo"]],
        ["name" => "product_num_offers", "placeholder" => "Number of Offers", "label" => "Number of Offers", "value" => $item["product_num_offers"]],
        ["name" => "product_availability", "placeholder" => "Availability", "label" => "Availability", "value" => $item["product_availability"]],
        ["name" => "is_best_seller", "label" => "Best Seller", "type" => "checkbox", "checked" => $item["is_best_seller"]],
        ["name" => "is_amazon_choice", "label" => "Amazon Choice", "type" => "checkbox", "checked" => $item["is_amazon_choice"]],
        ["name" => "is_prime", "label" => "Prime", "type" => "checkbox", "checked" => $item["is_prime"]],
        ["name" => "climate_pledge_friendly", "label" => "Climate Pledge Friendly", "type" => "checkbox", "checked" => $item["climate_pledge_friendly"]],
        ["name" => "sales_volume", "placeholder" => "Sales Volume", "label" => "Sales Volume", "value" => $item["sales_volume"]],
        ["name" => "about_product", "placeholder" => "About Product", "label" => "About Product", "value" => $item["about_product"]],
        ["name" => "product_description", "placeholder" => "Product Description", "label" => "Product Description", "value" => $item["product_description"]],
        ["name" => "product_information", "placeholder" => "Product Information", "label" => "Product Information", "value" => $item["product_information"]],
        ["name" => "rating_distribution", "placeholder" => "Rating Distribution", "label" => "Rating Distribution", "value" => $item["rating_distribution"]],
        ["name" => "product_photos", "placeholder" => "Product Photos", "label" => "Product Photos", "value" => $item["product_photos"]],
        ["name" => "product_details", "placeholder" => "Product Details", "label" => "Product Details", "value" => $item["product_details"]],
        ["name" => "customers_say", "placeholder" => "Customers Say", "label" => "Customers Say", "value" => $item["customers_say"]],
        ["name" => "category_path", "placeholder" => "Category Path", "label" => "Category Path", "value" => $item["category_path"]],
        ["name" => "product_variations", "placeholder" => "Product Variations", "label" => "Product Variations", "value" => $item["product_variations"]],
    ];
}
?>

<div class="container-fluid">
    <h3>Edit Item</h3>
    <form method="post" action="">
        <?php if (!empty($form)) : ?>
            <?php foreach ($form as $input) {
                render_input($input);
            } ?>
        <?php else : ?>
            <p>No data available for this item.</p>
        <?php endif; ?>
        <?php render_button(["text" => "Update Item", "type" => "Update"]); ?>
    </form>
</div>

<?php
require_once(__DIR__ . "/../../../partials/flash.php");
?>