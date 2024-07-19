<?php
require(__DIR__ . "/../../partials/nav.php");

$result = [];
if (isset($_GET["asin"])) {
    $data = ["asin" => $_GET["asin"], "country" => "US"];
    $endpoint = "https://real-time-amazon-data.p.rapidapi.com/product-details";
    $isRapidAPI = true;
    $rapidAPIHost = "real-time-amazon-data.p.rapidapi.com";
    $result = get($endpoint, "Product_Details_API_KEY", $data, $isRapidAPI, $rapidAPIHost);

    error_log("Response: " . var_export($result, true));

    if (se($result, "status", 400, false) == 200 && isset($result["response"])) {
        $result = json_decode($result["response"], true);
    } else {
        $result = [];
    }
}

if (isset($result["data"])) {
    $quote = $result["data"];

    $columns = [];
    $placeholders = [];
    $params = [];

    // Check the columns that exist in the database
    $db = getDB();
    $existingColumns = $db->query("SHOW COLUMNS FROM `IT202-S24-ProductDetails`")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($quote as $key => $value) {
        if (!in_array($key, $existingColumns)) {
            continue; // Skip columns that don't exist in the table schema
        }

        $column = $key;
        if (is_array($value)) {
            $value = json_encode($value);
        }
        if (in_array($key, ["product_price", "product_original_price", "product_price_max"]) && !is_null($value)) {
            $value = str_replace('$', '', $value);
            $value = str_replace(',', '', $value);
            $value = (float)$value;
        }
        if (is_bool($value)) {
            $value = $value ? 1 : 0;
        }

        $columns[] = "`$column`";
        $placeholders[] = ":$column";
        $params[":$column"] = $value;
    }
    $query = "INSERT INTO `IT202-S24-ProductDetails` (" . join(",", $columns) . ") VALUES (" . join(",", $placeholders) . ")";

    var_export($query);

    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        flash("Inserted record", "success");
    } catch (PDOException $e) {
        error_log("Something broke with the query: " . var_export($e, true));
        flash("An error occured", "danger");
    }
}
?>

<div class="container-fluid">
    <h1>Product Details</h1>
    <p>Remember, we typically won't be frequently calling live data from our API, this is merely a quick sample. We'll want to cache data in our DB to save on API quota.</p>
    <form>
        <div>
            <label>ASIN</label>
            <input name="asin" />
            <input type="submit" value="Fetch Item" />
        </div>
    </form>
    <div class="row">
        <?php if (!empty($result["data"])) : ?>
            <h2>Data</h2>
            <table class="table">
                <tbody>
                    <?php if (is_array($quote) || is_object($quote)) : ?>
                        <?php foreach ($quote as $k => $v) : ?>
                            <tr>
                                <th><?php se($k); ?></th>
                                <td>
                                    <?php 
                                    if (is_array($v) || is_object($v)) {
                                        echo "<pre>" . var_export($v, true) . "</pre>";
                                    } else {
                                        se($v);
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php
require(__DIR__ . "/../../partials/flash.php");
?>