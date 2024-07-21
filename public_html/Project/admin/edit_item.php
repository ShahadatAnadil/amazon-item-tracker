<?php
require(__DIR__ . "/../../../partials/nav.php");
require_once(__DIR__ . "/../../../lib/render_functions.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}

if (isset($_POST["asin"])) {
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

    $db = getDB();
    $query = "INSERT INTO `IT202-S24-ProductDetails` 
                (`asin`, `product_title`, `product_price`, `product_original_price`, `currency`, `country`, 
                `product_star_rating`, `product_num_ratings`, `product_url`, `product_photo`, `product_num_offers`, 
                `product_availability`, `is_best_seller`, `is_amazon_choice`, `is_prime`, `climate_pledge_friendly`, 
                `sales_volume`, `about_product`, `product_description`, `product_information`, `rating_distribution`, 
                `product_photos`, `product_details`, `customers_say`, `review_aspects`, `category_path`, `product_variations`)
              VALUES 
                (:asin, :product_title, :product_price, :product_original_price, :currency, :country, 
                :product_star_rating, :product_num_ratings, :product_url, :product_photo, :product_num_offers, 
                :product_availability, :is_best_seller, :is_amazon_choice, :is_prime, :climate_pledge_friendly, 
                :sales_volume, :about_product, :product_description, :product_information, :rating_distribution, 
                :product_photos, :product_details, :customers_say, :review_aspects, :category_path, :product_variations)";

    $params = [
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
        flash("Created a new item with id: " . $db->lastInsertId(), "success");
    } catch (PDOException $e) {
        error_log("Error inserting item: " . var_export($e, true));
        flash("An error occurred while creating the item", "danger");
    }
}

$form = [
    ["type" => "text", "name" => "asin", "placeholder" => "Item ASIN", "label" => "Item ASIN", "rules" => ["required" => "required"]],
    ["type" => "text", "name" => "product_title", "placeholder" => "Product Title", "label" => "Product Title", "rules" => ["required" => "required"]],
    ["type" => "number", "name" => "product_price", "placeholder" => "Product Price", "label" => "Product Price", "rules" => ["required" => "required", "min" => "0"]],
    ["type" => "number", "name" => "product_original_price", "placeholder" => "Original Price", "label" => "Original Price", "rules" => ["min" => "0"]],
    ["type" => "text", "name" => "currency", "placeholder" => "Currency", "label" => "Currency", "rules" => ["required" => "required"]],
    ["type" => "text", "name" => "country", "placeholder" => "Country", "label" => "Country", "rules" => ["required" => "required"]],
    ["type" => "number", "name" => "product_star_rating", "placeholder" => "Product Star Rating", "label" => "Product Star Rating", "rules" => ["required" => "required", "min" => "0", "max" => "5", "step" => "0.1"]],
    ["type" => "number", "name" => "product_num_ratings", "placeholder" => "Number of Ratings", "label" => "Number of Ratings", "rules" => ["required" => "required", "min" => "0"]],
    ["type" => "url", "name" => "product_url", "placeholder" => "Product URL", "label" => "Product URL", "rules" => ["required" => "required"]],
    ["type" => "url", "name" => "product_photo", "placeholder" => "Product Photo", "label" => "Product Photo", "rules" => ["required" => "required"]],
    ["type" => "number", "name" => "product_num_offers", "placeholder" => "Number of Offers", "label" => "Number of Offers", "rules" => ["min" => "0"]],
    ["type" => "text", "name" => "product_availability", "placeholder" => "Availability", "label" => "Availability"],
    ["type" => "checkbox", "name" => "is_best_seller", "label" => "Best Seller"],
    ["type" => "checkbox", "name" => "is_amazon_choice", "label" => "Amazon Choice"],
    ["type" => "checkbox", "name" => "is_prime", "label" => "Prime"],
    ["type" => "checkbox", "name" => "climate_pledge_friendly", "label" => "Climate Pledge Friendly"],
    ["type" => "text", "name" => "sales_volume", "placeholder" => "Sales Volume", "label" => "Sales Volume"],
    ["type" => "textarea", "name" => "about_product", "placeholder" => "About Product", "label" => "About Product"],
    ["type" => "textarea", "name" => "product_description", "placeholder" => "Product Description", "label" => "Product Description"],
    ["type" => "textarea", "name" => "product_information", "placeholder" => "Product Information", "label" => "Product Information"],
    ["type" => "textarea", "name" => "rating_distribution", "placeholder" => "Rating Distribution", "label" => "Rating Distribution"],
    ["type" => "textarea", "name" => "product_photos", "placeholder" => "Product Photos", "label" => "Product Photos"],
    ["type" => "textarea", "name" => "product_details", "placeholder" => "Product Details", "label" => "Product Details"],
    ["type" => "textarea", "name" => "customers_say", "placeholder" => "Customers Say", "label" => "Customers Say"],
    ["type" => "textarea", "name" => "review_aspects", "placeholder" => "Review Aspects", "label" => "Review Aspects"],
    ["type" => "textarea", "name" => "category_path", "placeholder" => "Category Path", "label" => "Category Path"],
    ["type" => "textarea", "name" => "product_variations", "placeholder" => "Product Variations", "label" => "Product Variations"]
];
?>

<div class="container-fluid">
    <h3>Create Item</h3>
    <form method="post" action="">
        <?php foreach ($form as $input) {
            render_input($input);
        } ?>
        <?php render_button(["text" => "Create Item", "type" => "submit"]); ?>
    </form>
</div>

<?php
require_once(__DIR__ . "/../../../partials/flash.php");
?>