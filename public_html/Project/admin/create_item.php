<?php
require(__DIR__ . "/../../../partials/nav.php");
require_once(__DIR__ . "/../../../lib/render_functions.php");
require_once(__DIR__ . "/../../../lib/item_api.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}



if (isset($_POST["action"])) {
    $action = $_POST["action"];
    $asin = strtoupper(se($_POST, "asin", "", false));
    $data = [];

    if ($asin) {
        if ($action === "fetch") {
            $result = fetch_data($asin);
            error_log("Data from API: " . var_export($result, true));
            if ($result && isset($result['params'])) {
                $data = $result['params'];
                $data["is_api"] = 1;
            } else {
                flash("No data found for ASIN $asin", "warning");
            }

            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM `IT202-S24-ProductDetails` WHERE `asin` = :asin");
            $stmt->execute([":asin" => $asin]);
            $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingItem) {
                $columns = [];
                $params = [":asin" => $asin];

                foreach ($data as $key => $value) {
                    if (array_key_exists($key, $existingItem)) {
                        if (is_array($value)) {
                            $value = json_encode($value);
                        }
                        if (in_array($key, ["product_price", "product_original_price", "product_price_max"]) && !is_null($value)) {
                            $value = str_replace(['$', ','], '', $value);
                            $value = (float)$value;
                        }
                        if (in_array($key, ["is_best_seller", "is_amazon_choice", "is_prime", "climate_pledge_friendly"])) {
                            $value = isset($value) && $value ? 1 : 0;
                        }
                        $columns[] = "`$key` = :$key";
                        $params[":$key"] = $value;
                    }
                }

                // Add modified timestamp
                $columns[] = "`modified` = :modified";
                $params[":modified"] = date('Y-m-d H:i:s');

                if (!empty($columns)) {
                    $query = "UPDATE `IT202-S24-ProductDetails` SET " . join(", ", $columns) . " WHERE `asin` = :asin";
                    error_log("Query: " . $query);
                    error_log("Params: " . var_export($params, true));

                    try {
                        $stmt = $db->prepare($query);
                        $stmt->execute($params);
                        flash("Updated record with ASIN " . $asin, "success");
                    } catch (PDOException $e) {
                        error_log("Something broke with the query: " . var_export($e, true));
                        flash("An error occurred", "danger");
                    }
                } else {
                    flash("No valid data to update", "warning");
                }
            } else {
                // Insert new record
                $columns = [];
                $placeholders = [];
                $params = [];

                foreach ($data as $key => $value) {
                    if (!is_null($value)) {
                        if (is_array($value)) {
                            $value = json_encode($value);
                        }
                        if (in_array($key, ["product_price", "product_original_price", "product_price_max"]) && !is_null($value)) {
                            $value = str_replace(['$', ','], '', $value);
                            $value = (float)$value;
                        }
                        if (in_array($key, ["is_best_seller", "is_amazon_choice", "is_prime", "climate_pledge_friendly"])) {
                            $value = isset($value) && $value ? 1 : 0;
                        }
                        $columns[] = "`$key`";
                        $placeholders[] = ":$key";
                        $params[":$key"] = $value;
                    }
                }

                // Ensure all required fields have values
                $requiredFields = ['is_amazon_choice', 'is_best_seller', 'is_prime', 'climate_pledge_friendly'];
                foreach ($requiredFields as $field) {
                    if (!isset($params[":$field"])) {
                        $columns[] = "`$field`";
                        $placeholders[] = ":$field";
                        $params[":$field"] = 0; // Default value
                    }
                }

                // Add created and modified timestamps
                $columns[] = "`created`";
                $placeholders[] = ":created";
                $params[":created"] = date('Y-m-d H:i:s');
                
                $columns[] = "`modified`";
                $placeholders[] = ":modified";
                $params[":modified"] = date('Y-m-d H:i:s');

                if (!empty($columns)) {
                    $query = "INSERT INTO `IT202-S24-ProductDetails` (" . join(",", $columns) . ") VALUES (" . join(",", $placeholders) . ")";
                    error_log("Query: " . $query);
                    error_log("Params: " . var_export($params, true));

                    try {
                        $stmt = $db->prepare($query);
                        $stmt->execute($params);
                        flash("Inserted record " . $db->lastInsertId(), "success");
                    } catch (PDOException $e) {
                        if ($e->errorInfo[1] == 1062) { // Duplicate entry error code
                            flash("A record with the same ASIN already exists.", "warning");
                        } else {
                            error_log("Something broke with the query: " . var_export($e, true));
                            flash("An error occurred", "danger");
                        }
                    }
                } else {
                    flash("No valid data to insert", "warning");
                }
            }
        } else if ($action === "create") {
            foreach ($_POST as $k => $v) {
                if (in_array($k, [
                    "asin",
                    "product_title",
                    "product_price",
                    "product_original_price",
                    "product_price_max",
                    "currency",
                    "country",
                    "product_star_rating",
                    "product_num_ratings",
                    "product_url",
                    "product_photo",
                    "product_num_offers",
                    "product_availability",
                    "is_best_seller",
                    "is_amazon_choice",
                    "is_prime",
                    "climate_pledge_friendly",
                    "sales_volume",
                    "about_product",
                    "product_description",
                    "product_information",
                    "rating_distribution",
                    "product_photos",
                    "product_details",
                    "customers_say",
                    "category_path",
                    "product_variations"
                ])) {
                    // Convert checkbox values to 1 or 0
                    if (in_array($k, ["is_best_seller", "is_amazon_choice", "is_prime", "climate_pledge_friendly"])) {
                        $data[$k] = isset($v) && $v === 'on' ? 1 : 0;
                    } else {
                        $data[$k] = $v;
                    }
                }
            }

            error_log("Data array before processing: " . var_export($data, true));

            $db = getDB();
            $existingColumns = $db->query("SHOW COLUMNS FROM `IT202-S24-ProductDetails`")->fetchAll(PDO::FETCH_COLUMN);

            error_log("Existing columns: " . var_export($existingColumns, true));

            $columns = [];
            $placeholders = [];
            $params = [];

            foreach ($data as $key => $value) {
                if (!in_array($key, $existingColumns)) {
                    error_log("Skipping column: $key");
                    continue;
                }

                if (is_array($value)) {
                    $value = json_encode($value);
                }
                if (in_array($key, ["product_price", "product_original_price", "product_price_max"]) && !is_null($value)) {
                    $value = str_replace(['$', ','], '', $value);
                    $value = (float)$value;
                }
                if (in_array($key, ["is_best_seller", "is_amazon_choice", "is_prime", "climate_pledge_friendly"])) {
                    $value = isset($value) && $value ? 1 : 0;
                }

                $columns[] = "`$key`";
                $placeholders[] = ":$key";
                $params[":$key"] = $value;
            }

            // Ensure all required fields have values
            $requiredFields = ['is_amazon_choice', 'is_best_seller', 'is_prime', 'climate_pledge_friendly'];
            foreach ($requiredFields as $field) {
                if (!isset($params[":$field"])) {
                    $columns[] = "`$field`";
                    $placeholders[] = ":$field";
                    $params[":$field"] = 0; // Default value
                }
            }

            // Add created and modified timestamps
            $columns[] = "`created`";
            $placeholders[] = ":created";
            $params[":created"] = date('Y-m-d H:i:s');
            
            $columns[] = "`modified`";
            $placeholders[] = ":modified";
            $params[":modified"] = date('Y-m-d H:i:s');

            if (!empty($columns)) {
                $query = "INSERT INTO `IT202-S24-ProductDetails` (" . join(",", $columns) . ") VALUES (" . join(",", $placeholders) . ")";
                error_log("Query: " . $query);
                error_log("Params: " . var_export($params, true));

                try {
                    $stmt = $db->prepare($query);
                    $stmt->execute($params);
                    flash("Inserted record " . $db->lastInsertId(), "success");
                } catch (PDOException $e) {
                    if ($e->errorInfo[1] == 1062) { // Duplicate entry error code
                        flash("A record with the same ASIN already exists.", "warning");
                    } else {
                        error_log("Something broke with the query: " . var_export($e, true));
                        flash("An error occurred", "danger");
                    }
                }
            } else {
                flash("No valid data to insert", "warning");
            }
        }
    } else {
        flash("You must provide an ASIN", "warning");
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
            <?php render_input(["type" => "text", "name" => "asin", "placeholder" => "Item ASIN", "label" => "Item ASIN", "rules" => ["required" => "required"]]); ?>
            <?php render_input(["name" => "product_title", "placeholder" => "Product Title", "label" => "Product Title"]); ?>
            <?php render_input(["name" => "product_price", "placeholder" => "Product Price", "label" => "Product Price"]); ?>
            <?php render_input(["name" => "product_original_price", "placeholder" => "Original Price", "label" => "Original Price"]); ?>
            <?php render_input(["name" => "product_price_max", "placeholder" => "Max Price", "label" => "Max Price"]); ?>
            <?php render_input(["name" => "currency", "placeholder" => "Currency", "label" => "Currency"]); ?>
            <?php render_input(["name" => "country", "placeholder" => "Country", "label" => "Country"]); ?>
            <?php render_input(["name" => "product_star_rating", "placeholder" => "Star Rating", "label" => "Star Rating"]); ?>
            <?php render_input(["name" => "product_num_ratings", "placeholder" => "Number of Ratings", "label" => "Number of Ratings"]); ?>
            <?php render_input(["name" => "product_url", "placeholder" => "Product URL", "label" => "Product URL"]); ?>
            <?php render_input(["name" => "product_photo", "placeholder" => "Product Photo", "label" => "Product Photo"]); ?>
            <?php render_input(["name" => "product_num_offers", "placeholder" => "Number of Offers", "label" => "Number of Offers"]); ?>
            <?php render_input(["name" => "product_availability", "placeholder" => "Availability", "label" => "Availability"]); ?>
            <?php render_input(["name" => "is_best_seller", "placeholder" => "Best Seller", "label" => "Best Seller", "type" => "checkbox"]); ?>
            <?php render_input(["name" => "is_amazon_choice", "placeholder" => "Amazon Choice", "label" => "Amazon Choice", "type" => "checkbox"]); ?>
            <?php render_input(["name" => "is_prime", "placeholder" => "Prime", "label" => "Prime", "type" => "checkbox"]); ?>
            <?php render_input(["name" => "climate_pledge_friendly", "placeholder" => "Climate Pledge Friendly", "label" => "Climate Pledge Friendly", "type" => "checkbox"]); ?>
            <?php render_input(["name" => "sales_volume", "placeholder" => "Sales Volume", "label" => "Sales Volume"]); ?>
            <?php render_input(["name" => "about_product", "placeholder" => "About Product", "label" => "About Product"]); ?>
            <?php render_input(["name" => "product_description", "placeholder" => "Product Description", "label" => "Product Description"]); ?>
            <?php render_input(["name" => "product_information", "placeholder" => "Product Information", "label" => "Product Information"]); ?>
            <?php render_input(["name" => "rating_distribution", "placeholder" => "Rating Distribution", "label" => "Rating Distribution"]); ?>
            <?php render_input(["name" => "product_photos", "placeholder" => "Product Photos", "label" => "Product Photos"]); ?>
            <?php render_input(["name" => "product_details", "placeholder" => "Product Details", "label" => "Product Details"]); ?>
            <?php render_input(["name" => "customers_say", "placeholder" => "Customers Say", "label" => "Customers Say"]); ?>
            <?php render_input(["name" => "category_path", "placeholder" => "Category Path", "label" => "Category Path"]); ?>
            <?php render_input(["name" => "product_variations", "placeholder" => "Product Variations", "label" => "Product Variations"]); ?>
            <?php render_input(["type" => "hidden", "name" => "action", "value" => "create"]); ?>
            <?php render_button(["text" => "Create Item", "type" => "submit"]); ?>
        </form>
    </div>
</div>

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