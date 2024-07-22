<?php
require(__DIR__ . "/../../../partials/nav.php");
require_once(__DIR__ . "/../../../lib/render_functions.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}

// Fetch existing item data if id is provided sha38 7/22/2024
$item = [];
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM `IT202-S24-ProductDetails` WHERE id = :id");
    try {
        $stmt->execute([":id" => $id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$item) {
            flash("Item not found", "warning");
            die(header("Location: $BASE_PATH" . "/admin/list_items.php"));
        }
    } catch (PDOException $e) {
        error_log("Error fetching item: " . var_export($e, true));
        flash("An error occurred while fetching the item", "danger");
    }
}

if (isset($_POST["asin"])) {
    // Get POST data
    $asin = se($_POST, "asin", "", false);
    $product_title = se($_POST, "product_title", "", false);
    $product_price = se($_POST, "product_price", "", false);
    $product_original_price = se($_POST, "product_original_price", "", false);
    $currency = se($_POST, "currency", "", false);
    $country = se($_POST, "country", "", false);
    $product_star_rating = se($_POST, "product_star_rating", "", false);
    $product_num_ratings = se($_POST, "product_num_ratings", "", false);
    $product_url = se($_POST, "product_url", "", false);
    $product_photo = se($_POST, "product_photo", "", false);
    $product_num_offers = se($_POST, "product_num_offers", "", false);
    $product_availability = se($_POST, "product_availability", "", false);
    $is_best_seller = isset($_POST["is_best_seller"]) ? 1 : 0;
    $is_amazon_choice = isset($_POST["is_amazon_choice"]) ? 1 : 0;
    $is_prime = isset($_POST["is_prime"]) ? 1 : 0;
    $climate_pledge_friendly = isset($_POST["climate_pledge_friendly"]) ? 1 : 0;
    $sales_volume = se($_POST, "sales_volume", "", false);
    $about_product = se($_POST, "about_product", "", false);
    $product_description = se($_POST, "product_description", "", false);
    $product_information = se($_POST, "product_information", "", false);
    $rating_distribution = se($_POST, "rating_distribution", "", false);
    $product_photos = se($_POST, "product_photos", "", false);
    $product_details = se($_POST, "product_details", "", false);
    $customers_say = se($_POST, "customers_say", "", false);
    $review_aspects = se($_POST, "review_aspects", "", false);
    $category_path = se($_POST, "category_path", "", false);
    $product_variations = se($_POST, "product_variations", "", false);

    // Server-side validation sha38 7/22/2024
    $errors = [];

    if (empty($asin)) {
        $errors[] = "ASIN is required";
    }
    if (empty($product_title)) {
        $errors[] = "Product title is required";
    }
    if ($product_price === "" || $product_price < 0) {
        $errors[] = "Product price must be a positive number";
    }
    if ($product_original_price !== "" && $product_original_price < 0) {
        $errors[] = "Original price must be a positive number";
    }
    if (empty($currency)) {
        $errors[] = "Currency is required";
    }
    if (empty($country)) {
        $errors[] = "Country is required";
    }
    if ($product_star_rating === "" || $product_star_rating < 0 || $product_star_rating > 5) {
        $errors[] = "Product star rating must be between 0 and 5";
    }
    if ($product_num_ratings === "" || $product_num_ratings < 0) {
        $errors[] = "Number of ratings must be a positive number";

    }
    if (empty($product_url)) {
        $errors[] = "Product URL is required";
    }
    if (empty($product_photo)) {
        $errors[] = "Product photo URL is required";
    }
    if ($product_num_offers !== "" && $product_num_offers < 0) {
        $errors[] = "Number of offers must be a positive number";
        
    }

    if (count($errors) === 0) {
        $db = getDB();
        $query = "UPDATE `IT202-S24-ProductDetails` SET
                    `asin` = :asin, `product_title` = :product_title, `product_price` = :product_price, `product_original_price` = :product_original_price, `currency` = :currency, `country` = :country, 
                    `product_star_rating` = :product_star_rating, `product_num_ratings` = :product_num_ratings, `product_url` = :product_url, `product_photo` = :product_photo, `product_num_offers` = :product_num_offers, 
                    `product_availability` = :product_availability, `is_best_seller` = :is_best_seller, `is_amazon_choice` = :is_amazon_choice, `is_prime` = :is_prime, `climate_pledge_friendly` = :climate_pledge_friendly, 
                    `sales_volume` = :sales_volume, `about_product` = :about_product, `product_description` = :product_description, `product_information` = :product_information, `rating_distribution` = :rating_distribution, 
                    `product_photos` = :product_photos, `product_details` = :product_details, `customers_say` = :customers_say, `review_aspects` = :review_aspects, `category_path` = :category_path, `product_variations` = :product_variations
                  WHERE id = :id";

        $params = [
            ":id" => $id,
            ":asin" => $asin,
            ":product_title" => $product_title,
            ":product_price" => $product_price,
            ":product_original_price" => $product_original_price,
            ":currency" => $currency,
            ":country" => $country,
            ":product_star_rating" => $product_star_rating,
            ":product_num_ratings" => $product_num_ratings,
            ":product_url" => $product_url,
            ":product_photo" => $product_photo,
            ":product_num_offers" => $product_num_offers,
            ":product_availability" => $product_availability,
            ":is_best_seller" => $is_best_seller,
            ":is_amazon_choice" => $is_amazon_choice,
            ":is_prime" => $is_prime,
            ":climate_pledge_friendly" => $climate_pledge_friendly,
            ":sales_volume" => $sales_volume,
            ":about_product" => $about_product,
            ":product_description" => $product_description,
            ":product_information" => $product_information,
            ":rating_distribution" => $rating_distribution,
            ":product_photos" => $product_photos,
            ":product_details" => $product_details,
            ":customers_say" => $customers_say,
            ":review_aspects" => $review_aspects,
            ":category_path" => $category_path,
            ":product_variations" => $product_variations
        ];

        try {
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            flash("Updated item with id: $id", "success");
            header("Location: " . get_url("admin/list_items.php"));
            exit();
        } catch (PDOException $e) {
            error_log("Error updating item: " . var_export($e, true));
            flash("An error occurred while updating the item", "danger");
        }
    } else {
        foreach ($errors as $error) {
            flash($error, "warning");
        }
    }
}
//sha38 7/22/2024
$form = [
    ["type" => "text", "name" => "asin", "placeholder" => "Item ASIN", "label" => "Item ASIN", "value" => $item['asin'] ?? "", "rules" => ["required" => "required"]],
    ["type" => "text", "name" => "product_title", "placeholder" => "Product Title", "label" => "Product Title", "value" => $item['product_title'] ?? "", "rules" => ["required" => "required"]],
    ["type" => "number", "name" => "product_price", "placeholder" => "Product Price", "label" => "Product Price", "value" => $item['product_price'] ?? "", "rules" => ["min" => "0"]],
    ["type" => "number", "name" => "product_original_price", "placeholder" => "Product Original Price", "label" => "Product Original Price", "value" => $item['product_original_price'] ?? ""],
    ["type" => "text", "name" => "currency", "placeholder" => "Currency", "label" => "Currency", "value" => $item['currency'] ?? "", "rules" => ["required" => "required"]],
    ["type" => "text", "name" => "country", "placeholder" => "Country", "label" => "Country", "value" => $item['country'] ?? "", "rules" => ["required" => "required"]],
    ["type" => "number", "name" => "product_star_rating", "placeholder" => "Product Star Rating", "label" => "Product Star Rating", "value" => $item['product_star_rating'] ?? "", "rules" => ["min" => "0", "max" => "5"]],
    ["type" => "number", "name" => "product_num_ratings", "placeholder" => "Number of Ratings", "label" => "Number of Ratings", "value" => $item['product_num_ratings'] ?? "", "rules" => ["min" => "0"]],
    ["type" => "url", "name" => "product_url", "placeholder" => "Product URL", "label" => "Product URL", "value" => $item['product_url'] ?? "", "rules" => ["required" => "required"]],
    ["type" => "url", "name" => "product_photo", "placeholder" => "Product Photo", "label" => "Product Photo", "value" => $item['product_photo'] ?? "", "rules" => ["required" => "required"]],
    ["type" => "number", "name" => "product_num_offers", "placeholder" => "Number of Offers", "label" => "Number of Offers", "value" => $item['product_num_offers'] ?? "", "rules" => ["min" => "0"]],
    ["type" => "text", "name" => "product_availability", "placeholder" => "Availability", "label" => "Availability", "value" => $item['product_availability'] ?? ""],
    ["type" => "checkbox", "name" => "is_best_seller", "label" => "Best Seller", "checked" => isset($item['is_best_seller']) ? $item['is_best_seller'] : false],
    ["type" => "checkbox", "name" => "is_amazon_choice", "label" => "Amazon Choice", "checked" => isset($item['is_amazon_choice']) ? $item['is_amazon_choice'] : false],
    ["type" => "checkbox", "name" => "is_prime", "label" => "Prime", "checked" => isset($item['is_prime']) ? $item['is_prime'] : false],
    ["type" => "checkbox", "name" => "climate_pledge_friendly", "label" => "Climate Pledge Friendly", "checked" => isset($item['climate_pledge_friendly']) ? $item['climate_pledge_friendly'] : false],
    ["type" => "text", "name" => "sales_volume", "placeholder" => "Sales Volume", "label" => "Sales Volume", "value" => $item['sales_volume'] ?? ""],
    ["type" => "textarea", "name" => "about_product", "placeholder" => "About Product", "label" => "About Product", "value" => $item['about_product'] ?? ""],
    ["type" => "textarea", "name" => "product_description", "placeholder" => "Product Description", "label" => "Product Description", "value" => $item['product_description'] ?? ""],
    ["type" => "textarea", "name" => "product_information", "placeholder" => "Product Information", "label" => "Product Information", "value" => $item['product_information'] ?? ""],
    ["type" => "textarea", "name" => "rating_distribution", "placeholder" => "Rating Distribution", "label" => "Rating Distribution", "value" => $item['rating_distribution'] ?? ""],
    ["type" => "textarea", "name" => "product_photos", "placeholder" => "Product Photos", "label" => "Product Photos", "value" => $item['product_photos'] ?? ""],
    ["type" => "textarea", "name" => "product_details", "placeholder" => "Product Details", "label" => "Product Details", "value" => $item['product_details'] ?? ""],
    ["type" => "textarea", "name" => "customers_say", "placeholder" => "Customers Say", "label" => "Customers Say", "value" => $item['customers_say'] ?? ""],
    ["type" => "textarea", "name" => "review_aspects", "placeholder" => "Review Aspects", "label" => "Review Aspects", "value" => $item['review_aspects'] ?? ""],
    ["type" => "textarea", "name" => "category_path", "placeholder" => "Category Path", "label" => "Category Path", "value" => $item['category_path'] ?? ""],
    ["type" => "textarea", "name" => "product_variations", "placeholder" => "Product Variations", "label" => "Product Variations", "value" => $item['product_variations'] ?? ""]
];
?>

<div class="container-fluid">
    <h3>Edit Item</h3>
    <form method="post" action="">
        <?php foreach ($form as $input) {
            render_input($input);
        } ?>
        <?php render_button(["text" => "Update Item", "type" => "submit"]); ?>
    </form>
</div>

<?php
require_once(__DIR__ . "/../../../partials/flash.php");
?>