<?php
require(__DIR__ . "/../../../partials/nav.php");
require_once(__DIR__ . "/../../../lib/render_functions.php");
require_once(__DIR__ . "/../../../lib/item_api.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}

function validate_field($field, $value) {
    $errors = [];
    switch ($field) {
        case 'product_price':
        case 'product_original_price':
        case 'product_price_max':
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
            if ($value < 0 || $value > 5) {
                $errors[] = "Star Rating must be between 0 and 5.";
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

function fetch_item_data_from_api($asin) {
    $api_url = "https://real-time-amazon-data.p.rapidapi.com/product-details?asin=" . urlencode($asin);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // You may need to set this to true in production
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-RapidAPI-Key: b36c183216msh998ab4a9181b9bap1d0532jsnac4e3054d2fa',
        'X-RapidAPI-Host: real-time-amazon-data.p.rapidapi.com'
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log('Curl error: ' . curl_error($ch));
        return null;
    }

    curl_close($ch);

    $data = json_decode($response, true);

    if (isset($data['data'])) {
        return [
            'asin' => $data['data']['asin'] ?? '',
            'product_title' => $data['data']['product_title'] ?? '',
            'product_price' => str_replace(['$', ','], '', $data['data']['product_price'] ?? '0'),
            'product_original_price' => str_replace(['$', ','], '', $data['data']['product_original_price'] ?? '0'),
            'currency' => $data['data']['currency'] ?? '',
            'country' => $data['data']['country'] ?? '',
            'product_star_rating' => $data['data']['product_star_rating'] ?? 0,
            'product_num_ratings' => $data['data']['product_num_ratings'] ?? 0,
            'product_url' => $data['data']['product_url'] ?? '',
            'product_photo' => $data['data']['product_photo'] ?? '',
            'product_num_offers' => $data['data']['product_num_offers'] ?? 0,
            'product_availability' => $data['data']['product_availability'] ?? '',
            'is_best_seller' => $data['data']['is_best_seller'] ? 1 : 0,
            'is_amazon_choice' => $data['data']['is_amazon_choice'] ? 1 : 0,
            'is_prime' => $data['data']['is_prime'] ? 1 : 0,
            'climate_pledge_friendly' => $data['data']['climate_pledge_friendly'] ? 1 : 0,
            'sales_volume' => str_replace(['$', ','], '', $data['data']['sales_volume'] ?? '0'),
            'about_product' => json_encode($data['data']['about_product'] ?? []),
            'product_description' => $data['data']['product_description'] ?? '',
            'product_information' => json_encode($data['data']['product_information'] ?? []),
            'rating_distribution' => json_encode($data['data']['rating_distribution'] ?? []),
            'product_photos' => json_encode($data['data']['product_photos'] ?? []),
            'product_details' => json_encode($data['data']['product_details'] ?? []),
            'customers_say' => json_encode($data['data']['customers_say'] ?? []),
            'category_path' => json_encode($data['data']['category_path'] ?? []),
            'product_variations' => json_encode($data['data']['product_variations'] ?? [])
        ];
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST["action"])) {
        $asin = trim($_POST["asin"]);
        if ($asin) {
            if ($_POST["action"] === "fetch") {
                $db = getDB();
                $stmt = $db->prepare("SELECT * FROM `IT202-S24-ProductDetails` WHERE asin = :asin");
                $stmt->execute([":asin" => $asin]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    flash("A record with the same ASIN already exists.", "warning");
                } else {
                    $item_data = fetch_item_data_from_api($asin);
                    if ($item_data) {
                        // Insert fetched data into the database
                        $columns = [];
                        $placeholders = [];
                        $params = [];
                        foreach ($item_data as $key => $value) {
                            $columns[] = "`$key`";
                            $placeholders[] = ":$key";
                            $params[":$key"] = $value;
                        }

                        $columns[] = "`created`";
                        $placeholders[] = ":created";
                        $params[":created"] = date('Y-m-d H:i:s');

                        $columns[] = "`modified`";
                        $placeholders[] = ":modified";
                        $params[":modified"] = date('Y-m-d H:i:s');

                        $query = "INSERT INTO `IT202-S24-ProductDetails` (" . join(",", $columns) . ") VALUES (" . join(",", $placeholders) . ")";
                        try {
                            $stmt = $db->prepare($query);
                            $stmt->execute($params);
                            flash("Item created successfully", "success");
                        } catch (PDOException $e) {
                            if ($e->errorInfo[1] == 1062) {
                                flash("A record with the same ASIN already exists.", "warning");
                            } else {
                                error_log("Error: " . var_export($e, true));
                                flash("An error occurred", "danger");
                            }
                        }
                    } else {
                        flash("Failed to fetch item data", "danger");
                    }
                }
            } elseif ($_POST["action"] === "create") {
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

                // Provide default values for fields that cannot be null
                $default_values = [
                    'product_original_price' => 0,
                    'product_price_max' => 0,
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
                    'product_variations' => 'N/A',
                    'is_best_seller' => 0,
                    'is_amazon_choice' => 0,
                    'is_prime' => 0,
                    'climate_pledge_friendly' => 0
                ];

                foreach ($default_values as $field => $default_value) {
                    if (!isset($data[$field]) || $data[$field] === '') {
                        $data[$field] = $default_value;
                    }
                }

                if (empty($errors)) {
                    $db = getDB();
                    $columns = [];
                    $placeholders = [];
                    $params = [];

                    foreach ($data as $key => $value) {
                        if (is_array($value)) {
                            $value = json_encode($value);
                        }
                        if (in_array($key, ["product_price", "product_original_price", "product_price_max"])) {
                            $value = str_replace(['$', ','], '', $value);
                            $value = (float)$value;
                        }
                        if (in_array($key, ["is_best_seller", "is_amazon_choice", "is_prime", "climate_pledge_friendly"])) {
                            $value = $value ? 1 : 0;
                        }

                        $columns[] = "`$key`";
                        $placeholders[] = ":$key";
                        $params[":$key"] = $value;
                    }

                    $columns[] = "`created`";
                    $placeholders[] = ":created";
                    $params[":created"] = date('Y-m-d H:i:s');

                    $columns[] = "`modified`";
                    $placeholders[] = ":modified";
                    $params[":modified"] = date('Y-m-d H:i:s');

                    $query = "INSERT INTO `IT202-S24-ProductDetails` (" . join(",", $columns) . ") VALUES (" . join(",", $placeholders) . ")";
                    error_log("Query: " . $query);
                    error_log("Params: " . var_export($params, true));

                    try {
                        $stmt = $db->prepare($query);
                        $stmt->execute($params);
                        flash("Item created successfully", "success");
                    } catch (PDOException $e) {
                        if ($e->errorInfo[1] == 1062) {
                            flash("A record with the same ASIN already exists.", "warning");
                        } else {
                            error_log("Error: " . var_export($e, true));
                            flash("An error occurred", "danger");
                        }
                    }
                } else {
                    foreach ($errors as $error) {
                        flash($error, "warning");
                    }
                }
            }
        } else {
            flash("ASIN is required", "warning");
        }
    }
}
?>

<div class="container-fluid">
    <h3>Create or Fetch Item</h3>
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link bg-success" href="#" onclick="switchTab('fetch')">Fetch</a>
        </li>
        <li class="nav-item">
            <a class="nav-link bg-success" href="#" onclick="switchTab('create')">Create</a>
        </li>
    </ul>
    <div id="fetch" class="tab-target">
        <form method="post" action="">
            <?php render_input(["type" => "search", "name" => "asin", "placeholder" => "Item ASIN", "rules" => ["required" => "required"]]); ?>
            <?php render_input(["type" => "hidden", "name" => "action", "value" => "fetch"]); ?>
            <?php render_button(["text" => "Fetch Item", "type" => "submit"]); ?>
        </form>
    </div>
    
    <div id="create" style="display: none;" class="tab-target">
        <form method="post" action="">
            <?php render_input(["type" => "text", "name" => "asin", "placeholder" => "Item ASIN", "label" => "Item ASIN", "rules" => ["required" => "required"], "value" => $_POST['asin'] ?? ""]); ?>
            <?php render_input(["type" => "text", "name" => "product_title", "placeholder" => "Product Title", "label" => "Product Title", "rules" => ["required" => "required"], "value" => $_POST['product_title'] ?? ""]); ?>
            <?php render_input(["type" => "number", "name" => "product_price", "placeholder" => "Product Price", "label" => "Product Price", "rules" => ["required" => "required", "step" => "0.01"], "value" => $_POST['product_price'] ?? ""]); ?>
            <?php render_input(["type" => "number", "name" => "product_original_price", "placeholder" => "Original Price", "label" => "Original Price", "step" => "0.01", "value" => $_POST['product_original_price'] ?? ""]); ?>
            <?php render_input(["type" => "number", "name" => "product_price_max", "placeholder" => "Max Price", "label" => "Max Price", "step" => "0.01", "value" => $_POST['product_price_max'] ?? ""]); ?>
            <?php render_input(["type" => "text", "name" => "currency", "placeholder" => "Currency", "label" => "Currency", "value" => $_POST['currency'] ?? ""]); ?>
            <?php render_input(["type" => "text", "name" => "country", "placeholder" => "Country", "label" => "Country", "value" => $_POST['country'] ?? ""]); ?>
            <?php render_input(["type" => "number", "name" => "product_star_rating", "placeholder" => "Star Rating", "label" => "Star Rating", "rules" => ["required" => "required", "step" => "0.1", "min" => "0", "max" => "5"], "value" => $_POST['product_star_rating'] ?? ""]); ?>
            <?php render_input(["type" => "number", "name" => "product_num_ratings", "placeholder" => "Number of Ratings", "label" => "Number of Ratings", "value" => $_POST['product_num_ratings'] ?? ""]); ?>
            <?php render_input(["type" => "url", "name" => "product_url", "placeholder" => "Product URL", "label" => "Product URL", "value" => $_POST['product_url'] ?? ""]); ?>
            <?php render_input(["type" => "url", "name" => "product_photo", "placeholder" => "Product Photo", "label" => "Product Photo", "value" => $_POST['product_photo'] ?? ""]); ?>
            <?php render_input(["type" => "number", "name" => "product_num_offers", "placeholder" => "Number of Offers", "label" => "Number of Offers", "value" => $_POST['product_num_offers'] ?? ""]); ?>
            <?php render_input(["type" => "text", "name" => "product_availability", "placeholder" => "Availability", "label" => "Availability", "value" => $_POST['product_availability'] ?? ""]); ?>
            <?php render_input(["type" => "checkbox", "name" => "is_best_seller", "label" => "Best Seller", "checked" => isset($_POST['is_best_seller']) ? $_POST['is_best_seller'] : false]); ?>
            <?php render_input(["type" => "checkbox", "name" => "is_amazon_choice", "label" => "Amazon Choice", "checked" => isset($_POST['is_amazon_choice']) ? $_POST['is_amazon_choice'] : false]); ?>
            <?php render_input(["type" => "checkbox", "name" => "is_prime", "label" => "Prime", "checked" => isset($_POST['is_prime']) ? $_POST['is_prime'] : false]); ?>
            <?php render_input(["type" => "checkbox", "name" => "climate_pledge_friendly", "label" => "Climate Pledge Friendly", "checked" => isset($_POST['climate_pledge_friendly']) ? $_POST['climate_pledge_friendly'] : false]); ?>
            <?php render_input(["type" => "number", "name" => "sales_volume", "placeholder" => "Sales Volume", "label" => "Sales Volume", "value" => $_POST['sales_volume'] ?? ""]); ?>
            <?php render_input(["type" => "textarea", "name" => "about_product", "placeholder" => "About Product", "label" => "About Product", "rules" => ["required" => "required"], "value" => $_POST['about_product'] ?? ""]); ?>
            <?php render_input(["type" => "textarea", "name" => "product_description", "placeholder" => "Product Description", "label" => "Product Description", "value" => $_POST['product_description'] ?? ""]); ?>
            <?php render_input(["type" => "textarea", "name" => "product_information", "placeholder" => "Product Information", "label" => "Product Information", "value" => $_POST['product_information'] ?? ""]); ?>
            <?php render_input(["type" => "textarea", "name" => "rating_distribution", "placeholder" => "Rating Distribution", "label" => "Rating Distribution", "value" => $_POST['rating_distribution'] ?? ""]); ?>
            <?php render_input(["type" => "textarea", "name" => "product_photos", "placeholder" => "Product Photos", "label" => "Product Photos", "value" => $_POST['product_photos'] ?? ""]); ?>
            <?php render_input(["type" => "textarea", "name" => "product_details", "placeholder" => "Product Details", "label" => "Product Details", "value" => $_POST['product_details'] ?? ""]); ?>
            <?php render_input(["type" => "textarea", "name" => "customers_say", "placeholder" => "Customers Say", "label" => "Customers Say", "value" => $_POST['customers_say'] ?? ""]); ?>
            <?php render_input(["type" => "textarea", "name" => "category_path", "placeholder" => "Category Path", "label" => "Category Path", "value" => $_POST['category_path'] ?? ""]); ?>
            <?php render_input(["type" => "textarea", "name" => "product_variations", "placeholder" => "Product Variations", "label" => "Product Variations", "value" => $_POST['product_variations'] ?? ""]); ?>
            <?php render_input(["type" => "hidden", "name" => "action", "value" => "create"]); ?>
            <?php render_button(["text" => "Create Item", "type" => "submit"]); ?>
        </form>
    </div>
</div>
<!--sha38 7/29/2024-->
<script>
function switchTab(target) {
    let targets = document.querySelectorAll(".tab-target");
    targets.forEach(t => t.style.display = "none");
    document.getElementById(target).style.display = "block";
}
</script>

<?php
require_once(__DIR__ . "/../../../partials/flash.php");
?>
