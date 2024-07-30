<?php
require(__DIR__ . "/../../../partials/nav.php");
require_once(__DIR__ . "/../../../lib/render_functions.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH/home.php"));
}


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
            die(header("Location: $BASE_PATH/admin/list_items.php"));
        }
    } catch (PDOException $e) {
        error_log("Error fetching item: " . var_export($e, true));
        flash("An error occurred while fetching the item", "danger");
    }
}

function validate_field($field, $value) {
    $errors = [];
    switch ($field) {
        case 'product_price':
        case 'product_original_price':
        case 'sales_volume':
            if (!is_numeric($value)) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " must be a valid number.";
            }
            break;
        case 'product_url':
        case 'product_photo':
            if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " must be a valid URL.";
            }
            break;
        case 'product_star_rating':
            if (!is_numeric($value) || $value < 0 || $value > 5) {
                $errors[] = "Star Rating must be a number between 0 and 5.";
            }
            break;
        case 'asin':
        case 'product_title':
        case 'about_product':
            if (empty($value)) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
            }
            break;
    }
    return $errors;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $required_fields = ['asin', 'product_title', 'product_price', 'about_product', 'product_star_rating'];
    $data = [];
    $errors = [];

    foreach ($_POST as $field => $value) {
        $value = trim($value);
        if ($field !== 'action') {
            $data[$field] = $value;
            if (in_array($field, $required_fields) || !empty($value)) {
                $field_errors = validate_field($field, $value);
                if (!empty($field_errors)) {
                    $errors = array_merge($errors, $field_errors);
                }
            }
        }
    }

    
    $default_values = [
        'product_original_price' => 0,
        'product_num_ratings' => 0,
        'product_url' => 'N/A',
        'product_photo' => 'N/A',
        'product_num_offers' => 0,
        'product_availability' => 'N/A',
        'sales_volume' => 0,
        'product_description' => 'N/A',
        'product_information' => 'N/A',
        'rating_distribution' => 'N/A',
        'product_photos' => 'N/A',
        'product_details' => 'N/A',
        'customers_say' => 'N/A',
        'category_path' => 'N/A',
        'product_variations' => 'N/A'
    ];

    foreach ($default_values as $field => $default_value) {
        if (!isset($data[$field]) || $data[$field] === '') {
            $data[$field] = $default_value;
        }
    }

    
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM `IT202-S24-ProductDetails` WHERE asin = :asin AND id != :id");
    $stmt->execute([":asin" => $data['asin'], ":id" => $id]);
    $existing_item = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existing_item) {
        $errors[] = "An item with the same ASIN already exists.";
    }

    if (empty($errors)) {
        $params = [
            ":id" => $id,
            ":asin" => $data['asin'],
            ":product_title" => $data['product_title'],
            ":product_price" => $data['product_price'],
            ":product_original_price" => $data['product_original_price'],
            ":currency" => $data['currency'],
            ":country" => $data['country'],
            ":product_star_rating" => $data['product_star_rating'],
            ":product_num_ratings" => $data['product_num_ratings'],
            ":product_url" => $data['product_url'],
            ":product_photo" => $data['product_photo'],
            ":product_num_offers" => $data['product_num_offers'],
            ":product_availability" => $data['product_availability'],
            ":is_best_seller" => isset($data['is_best_seller']) ? 1 : 0,
            ":is_amazon_choice" => isset($data['is_amazon_choice']) ? 1 : 0,
            ":is_prime" => isset($data['is_prime']) ? 1 : 0,
            ":climate_pledge_friendly" => isset($data['climate_pledge_friendly']) ? 1 : 0,
            ":sales_volume" => $data['sales_volume'],
            ":about_product" => $data['about_product'],
            ":product_description" => $data['product_description'],
            ":product_information" => $data['product_information'],
            ":rating_distribution" => $data['rating_distribution'],
            ":product_photos" => $data['product_photos'],
            ":product_details" => $data['product_details'],
            ":customers_say" => $data['customers_say'],
            ":review_aspects" => $data['review_aspects'],
            ":category_path" => $data['category_path'],
            ":product_variations" => $data['product_variations'],
            ":modified" => date('Y-m-d H:i:s')
        ];

        $query = "UPDATE `IT202-S24-ProductDetails` SET
                    `asin` = :asin, `product_title` = :product_title, `product_price` = :product_price, `product_original_price` = :product_original_price, `currency` = :currency, `country` = :country, 
                    `product_star_rating` = :product_star_rating, `product_num_ratings` = :product_num_ratings, `product_url` = :product_url, `product_photo` = :product_photo, `product_num_offers` = :product_num_offers, 
                    `product_availability` = :product_availability, `is_best_seller` = :is_best_seller, `is_amazon_choice` = :is_amazon_choice, `is_prime` = :is_prime, `climate_pledge_friendly` = :climate_pledge_friendly, 
                    `sales_volume` = :sales_volume, `about_product` = :about_product, `product_description` = :product_description, `product_information` = :product_information, `rating_distribution` = :rating_distribution, 
                    `product_photos` = :product_photos, `product_details` = :product_details, `customers_say` = :customers_say, `review_aspects` = :review_aspects, `category_path` = :category_path, `product_variations` = :product_variations, 
                    `modified` = :modified
                  WHERE id = :id";

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

$form = [
    ["type" => "text", "name" => "asin", "placeholder" => "Item ASIN", "label" => "Item ASIN", "value" => $item['asin'] ?? "", "rules" => ["required" => "required"]],
    ["type" => "text", "name" => "product_title", "placeholder" => "Product Title", "label" => "Product Title", "value" => $item['product_title'] ?? "", "rules" => ["required" => "required"]],
    ["type" => "number", "name" => "product_price", "placeholder" => "Product Price", "label" => "Product Price", "value" => $item['product_price'] ?? "", "rules" => ["required" => "required", "step" => "0.01"]],
    ["type" => "number", "name" => "product_original_price", "placeholder" => "Product Original Price", "label" => "Product Original Price", "value" => $item['product_original_price'] ?? "", "rules" => ["step" => "0.01"]],
    ["type" => "text", "name" => "currency", "placeholder" => "Currency", "label" => "Currency", "value" => $item['currency'] ?? "", "rules" => ["required" => "required"]],
    ["type" => "text", "name" => "country", "placeholder" => "Country", "label" => "Country", "value" => $item['country'] ?? "", "rules" => ["required" => "required"]],
    ["type" => "number", "name" => "product_star_rating", "placeholder" => "Product Star Rating", "label" => "Product Star Rating", "value" => $item['product_star_rating'] ?? "", "rules" => ["required" => "required", "step" => "0.1", "min" => "0", "max" => "5"]],
    ["type" => "number", "name" => "product_num_ratings", "placeholder" => "Number of Ratings", "label" => "Number of Ratings", "value" => $item['product_num_ratings'] ?? "", "rules" => ["min" => "0"]],
    ["type" => "url", "name" => "product_url", "placeholder" => "Product URL", "label" => "Product URL", "value" => $item['product_url'] ?? ""],
    ["type" => "url", "name" => "product_photo", "placeholder" => "Product Photo", "label" => "Product Photo", "value" => $item['product_photo'] ?? ""],
    ["type" => "number", "name" => "product_num_offers", "placeholder" => "Number of Offers", "label" => "Number of Offers", "value" => $item['product_num_offers'] ?? "", "rules" => ["min" => "0"]],
    ["type" => "text", "name" => "product_availability", "placeholder" => "Availability", "label" => "Availability", "value" => $item['product_availability'] ?? ""],
    ["type" => "checkbox", "name" => "is_best_seller", "label" => "Best Seller", "checked" => isset($item['is_best_seller']) ? $item['is_best_seller'] : false],
    ["type" => "checkbox", "name" => "is_amazon_choice", "label" => "Amazon Choice", "checked" => isset($item['is_amazon_choice']) ? $item['is_amazon_choice'] : false],
    ["type" => "checkbox", "name" => "is_prime", "label" => "Prime", "checked" => isset($item['is_prime']) ? $item['is_prime'] : false],
    ["type" => "checkbox", "name" => "climate_pledge_friendly", "label" => "Climate Pledge Friendly", "checked" => isset($item['climate_pledge_friendly']) ? $item['climate_pledge_friendly'] : false],
    ["type" => "number", "name" => "sales_volume", "placeholder" => "Sales Volume", "label" => "Sales Volume", "value" => $item['sales_volume'] ?? ""],
    ["type" => "textarea", "name" => "about_product", "placeholder" => "About Product", "label" => "About Product", "value" => $item['about_product'] ?? "", "rules" => ["required" => "required"]],
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