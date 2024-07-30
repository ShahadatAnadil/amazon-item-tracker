<?php
require(__DIR__ . "/../../../partials/nav.php");
require_once(__DIR__ . "/../../../lib/render_functions.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url('home.php')));
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
            die(header("Location: " . get_url('admin/list_items.php')));
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
        case 'product_star_rating':
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
        case 'asin':
        case 'product_title':
        case 'about_product':
        case 'currency':
            if (empty($value)) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
            }
            break;
        case 'product_star_rating':
            if ($value < 0 || $value > 5) {
                $errors[] = "Star Rating must be a number between 0 and 5.";
            }
            break;
    }
    return $errors;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $required_fields = ['asin', 'product_title', 'product_price', 'about_product', 'product_star_rating', 'currency'];
    $data = [];
    $errors = [];

    foreach ($_POST as $field => $value) {
        $value = trim($value);
        if (in_array($field, $required_fields) || !empty($value)) {
            $data[$field] = $value;
            $field_errors = validate_field($field, $value);
            if (!empty($field_errors)) {
                $errors = array_merge($errors, $field_errors);
            }
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
            ":product_original_price" => $data['product_original_price'] ?? null,
            ":currency" => $data['currency'],
            ":country" => $data['country'] ?? null,
            ":product_star_rating" => $data['product_star_rating'],
            ":product_num_ratings" => $data['product_num_ratings'] ?? null,
            ":product_url" => $data['product_url'] ?? null,
            ":product_photo" => $data['product_photo'] ?? null,
            ":product_num_offers" => $data['product_num_offers'] ?? null,
            ":product_availability" => $data['product_availability'] ?? null,
            ":is_best_seller" => isset($data['is_best_seller']) ? 1 : 0,
            ":is_amazon_choice" => isset($data['is_amazon_choice']) ? 1 : 0,
            ":is_prime" => isset($data['is_prime']) ? 1 : 0,
            ":climate_pledge_friendly" => isset($data['climate_pledge_friendly']) ? 1 : 0,
            ":sales_volume" => $data['sales_volume'] ?? null,
            ":about_product" => $data['about_product'],
            ":product_description" => $data['product_description'] ?? null,
            ":product_information" => $data['product_information'] ?? null,
            ":rating_distribution" => $data['rating_distribution'] ?? null,
            ":product_photos" => $data['product_photos'] ?? null,
            ":product_details" => $data['product_details'] ?? null,
            ":customers_say" => $data['customers_say'] ?? null,
            ":review_aspects" => $data['review_aspects'] ?? null,
            ":category_path" => $data['category_path'] ?? null,
            ":product_variations" => $data['product_variations'] ?? null,
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
?>

<div class="container-fluid">
    <h3>Edit Item</h3>
    <form method="post" action="">
        <div class="form-group">
            <label for="asin">Item ASIN</label>
            <input type="text" class="form-control" id="asin" name="asin" value="<?php echo htmlspecialchars($item['asin']); ?>" required>
        </div>
        <div class="form-group">
            <label for="product_title">Product Title</label>
            <input type="text" class="form-control" id="product_title" name="product_title" value="<?php echo htmlspecialchars($item['product_title']); ?>" required>
        </div>
        <div class="form-group">
            <label for="product_price">Product Price</label>
            <input type="number" step="0.01" class="form-control" id="product_price" name="product_price" value="<?php echo htmlspecialchars($item['product_price']); ?>" required>
        </div>
        <div class="form-group">
            <label for="product_original_price">Product Original Price</label>
            <input type="number" step="0.01" class="form-control" id="product_original_price" name="product_original_price" value="<?php echo htmlspecialchars($item['product_original_price']); ?>">
        </div>
        <div class="form-group">
            <label for="currency">Currency</label>
            <input type="text" class="form-control" id="currency" name="currency" value="<?php echo htmlspecialchars($item['currency']); ?>" required>
        </div>
        <div class="form-group">
            <label for="country">Country</label>
            <input type="text" class="form-control" id="country" name="country" value="<?php echo htmlspecialchars($item['country']); ?>">
        </div>
        <div class="form-group">
            <label for="product_star_rating">Product Star Rating</label>
            <input type="number" step="0.1" min="0" max="5" class="form-control" id="product_star_rating" name="product_star_rating" value="<?php echo htmlspecialchars($item['product_star_rating']); ?>" required>
        </div>
        <div class="form-group">
            <label for="product_num_ratings">Number of Ratings</label>
            <input type="number" class="form-control" id="product_num_ratings" name="product_num_ratings" value="<?php echo htmlspecialchars($item['product_num_ratings']); ?>">
        </div>
        <div class="form-group">
            <label for="product_url">Product URL</label>
            <input type="url" class="form-control" id="product_url" name="product_url" value="<?php echo htmlspecialchars($item['product_url']); ?>">
        </div>
        <div class="form-group">
            <label for="product_photo">Product Photo</label>
            <input type="url" class="form-control" id="product_photo" name="product_photo" value="<?php echo htmlspecialchars($item['product_photo']); ?>">
        </div>
        <div class="form-group">
            <label for="product_num_offers">Number of Offers</label>
            <input type="number" class="form-control" id="product_num_offers" name="product_num_offers" value="<?php echo htmlspecialchars($item['product_num_offers']); ?>">
        </div>
        <div class="form-group">
            <label for="product_availability">Availability</label>
            <input type="text" class="form-control" id="product_availability" name="product_availability" value="<?php echo htmlspecialchars($item['product_availability']); ?>">
        </div>
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="is_best_seller" name="is_best_seller" <?php echo isset($item['is_best_seller']) && $item['is_best_seller'] ? 'checked' : ''; ?>>
            <label class="form-check-label" for="is_best_seller">Best Seller</label>
        </div>
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="is_amazon_choice" name="is_amazon_choice" <?php echo isset($item['is_amazon_choice']) && $item['is_amazon_choice'] ? 'checked' : ''; ?>>
            <label class="form-check-label" for="is_amazon_choice">Amazon Choice</label>
        </div>
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="is_prime" name="is_prime" <?php echo isset($item['is_prime']) && $item['is_prime'] ? 'checked' : ''; ?>>
            <label class="form-check-label" for="is_prime">Prime</label>
        </div>
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="climate_pledge_friendly" name="climate_pledge_friendly" <?php echo isset($item['climate_pledge_friendly']) && $item['climate_pledge_friendly'] ? 'checked' : ''; ?>>
            <label class="form-check-label" for="climate_pledge_friendly">Climate Pledge Friendly</label>
        </div>
        <div class="form-group">
            <label for="sales_volume">Sales Volume</label>
            <input type="number" class="form-control" id="sales_volume" name="sales_volume" value="<?php echo htmlspecialchars($item['sales_volume']); ?>">
        </div>
        <div class="form-group">
            <label for="about_product">About Product</label>
            <textarea class="form-control" id="about_product" name="about_product" required><?php echo htmlspecialchars($item['about_product']); ?></textarea>
        </div>
        <div class="form-group">
            <label for="product_description">Product Description</label>
            <textarea class="form-control" id="product_description" name="product_description"><?php echo htmlspecialchars($item['product_description']); ?></textarea>
        </div>
        <div class="form-group">
            <label for="product_information">Product Information</label>
            <textarea class="form-control" id="product_information" name="product_information"><?php echo htmlspecialchars($item['product_information']); ?></textarea>
        </div>
        <div class="form-group">
            <label for="rating_distribution">Rating Distribution</label>
            <textarea class="form-control" id="rating_distribution" name="rating_distribution"><?php echo htmlspecialchars($item['rating_distribution']); ?></textarea>
        </div>
        <div class="form-group">
            <label for="product_photos">Product Photos</label>
            <textarea class="form-control" id="product_photos" name="product_photos"><?php echo htmlspecialchars($item['product_photos']); ?></textarea>
        </div>
        <div class="form-group">
            <label for="product_details">Product Details</label>
            <textarea class="form-control" id="product_details" name="product_details"><?php echo htmlspecialchars($item['product_details']); ?></textarea>
        </div>
        <div class="form-group">
            <label for="customers_say">Customers Say</label>
            <textarea class="form-control" id="customers_say" name="customers_say"><?php echo htmlspecialchars($item['customers_say']); ?></textarea>
        </div>
        <div class="form-group">
            <label for="review_aspects">Review Aspects</label>
            <textarea class="form-control" id="review_aspects" name="review_aspects"><?php echo htmlspecialchars($item['review_aspects']); ?></textarea>
        </div>
        <div class="form-group">
            <label for="category_path">Category Path</label>
            <textarea class="form-control" id="category_path" name="category_path"><?php echo htmlspecialchars($item['category_path']); ?></textarea>
        </div>
        <div class="form-group">
            <label for="product_variations">Product Variations</label>
            <textarea class="form-control" id="product_variations" name="product_variations"><?php echo htmlspecialchars($item['product_variations']); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Update Item</button>
    </form>
</div>

<?php
require_once(__DIR__ . "/../../../partials/flash.php");
?>

<style>
.flash-message {
    font-size: 1.2em;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 15px;
}
.flash-message.warning {
    background-color: #ffc107;
    color: #856404;
}
.flash-message.success {
    background-color: #28a745;
    color: #fff;
}
.flash-message.danger {
    background-color: #dc3545;
    color: #fff;
}
.flash-container {
    position: fixed;
    top: 0;
    width: 100%;
    z-index: 1000;
    text-align: center;
}
</style>