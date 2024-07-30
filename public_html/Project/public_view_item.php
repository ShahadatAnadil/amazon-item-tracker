<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require(__DIR__ . "/../../lib/functions.php");
require(__DIR__ . "/../../partials/nav.php");
require_once(__DIR__ . "/../../lib/render_functions.php");
require_once(__DIR__ . "/../../lib/item_api.php");

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    flash("You must be logged in to view this page.", "warning");
    header("Location: " . get_url('login.php')); // Redirect to login page
    exit;
}


$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo 'Invalid item ID.';
    exit;
}


$db = getDB();

try {
    
    $stmt = $db->prepare("SELECT * FROM `IT202-S24-ProductDetails` WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        
        echo '<div class="item-details">';
        echo '<h1>' . htmlspecialchars($item['product_title'] ?? 'No Title') . '</h1>';
        echo '<p><strong>ASIN:</strong> ' . htmlspecialchars($item['asin'] ?? 'N/A') . '</p>';
        echo '<p><strong>Price:</strong> $' . htmlspecialchars(number_format($item['product_price'] ?? 0, 2)) . '</p>';
        echo '<p><strong>Original Price:</strong> $' . htmlspecialchars(number_format($item['product_original_price'] ?? 0, 2)) . '</p>';
        echo '<p><strong>Currency:</strong> ' . htmlspecialchars($item['currency'] ?? 'N/A') . '</p>';
        echo '<p><strong>Country:</strong> ' . htmlspecialchars($item['country'] ?? 'N/A') . '</p>';
        echo '<p><strong>Star Rating:</strong> ' . htmlspecialchars($item['product_star_rating'] ?? 'N/A') . '</p>';
        echo '<p><strong>Number of Ratings:</strong> ' . htmlspecialchars($item['product_num_ratings'] ?? 'N/A') . '</p>';
        echo '<p><strong>Product URL:</strong> <a href="' . htmlspecialchars($item['product_url'] ?? '#') . '" target="_blank">Link</a></p>';
        echo '<p><strong>Product Photo:</strong> <img src="' . htmlspecialchars($item['product_photo'] ?? '#') . '" alt="Product Photo" class="product-photo"></p>';
        echo '<p><strong>Number of Offers:</strong> ' . htmlspecialchars($item['product_num_offers'] ?? 'N/A') . '</p>';
        echo '<p><strong>Availability:</strong> ' . htmlspecialchars($item['product_availability'] ?? 'N/A') . '</p>';
        echo '<p><strong>Best Seller:</strong> ' . ($item['is_best_seller'] ? 'Yes' : 'No') . '</p>';
        echo '<p><strong>Amazon Choice:</strong> ' . ($item['is_amazon_choice'] ? 'Yes' : 'No') . '</p>';
        echo '<p><strong>Climate Pledge Friendly:</strong> ' . ($item['climate_pledge_friendly'] ? 'Yes' : 'No') . '</p>';
        echo '<p><strong>Sales Volume:</strong> ' . htmlspecialchars($item['sales_volume'] ?? 'N/A') . '</p>';

        
        if (!empty($item['about_product'])) {
            $aboutProduct = json_decode($item['about_product'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($aboutProduct)) {
                echo '<p><strong>About Product:</strong></p>';
                echo '<ul>';
                foreach ($aboutProduct as $about) {
                    echo '<li>' . htmlspecialchars($about) . '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p><strong>About Product:</strong> ' . htmlspecialchars($item['about_product']) . '</p>';
            }
        }

        echo '<p><strong>Description:</strong> ' . htmlspecialchars($item['product_description'] ?? 'N/A') . '</p>';

        
        if (!empty($item['product_information'])) {
            $productInformation = json_decode($item['product_information'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($productInformation)) {
                echo '<p><strong>Information:</strong></p>';
                echo '<div class="product-information">';
                foreach ($productInformation as $key => $value) {
                    echo '<p class="info-key">' . htmlspecialchars($key) . ': <span class="info-value">' . htmlspecialchars($value) . '</span></p>';
                }
                echo '</div>';
            } else {
                echo '<p><strong>Information:</strong> ' . htmlspecialchars($item['product_information']) . '</p>';
            }
        }

        
        if (!empty($item['rating_distribution'])) {
            $ratingDistribution = json_decode($item['rating_distribution'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($ratingDistribution)) {
                echo '<p><strong>Rating Distribution:</strong></p>';
                foreach ($ratingDistribution as $rating => $count) {
                    echo '<p>' . htmlspecialchars($rating) . ' star: ' . htmlspecialchars($count) . '</p>';
                }
            } else {
                echo '<p><strong>Rating Distribution:</strong> ' . htmlspecialchars($item['rating_distribution']) . '</p>';
            }
        }

        
        if (!empty($item['product_photos'])) {
            $photos = json_decode($item['product_photos'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo '<p><strong>Product Photos:</strong></p>';
                foreach ($photos as $photo) {
                    echo '<img src="' . htmlspecialchars($photo) . '" alt="Product Photo" class="product-photo">';
                }
            } else {
                echo '<p><strong>Product Photos:</strong> ' . htmlspecialchars($item['product_photos']) . '</p>';
            }
        }

        
        if (!empty($item['product_details'])) {
            $details = json_decode($item['product_details'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo '<p><strong>Product Details:</strong></p>';
                foreach ($details as $key => $value) {
                    echo '<p><strong>' . htmlspecialchars($key) . ':</strong> ' . htmlspecialchars($value) . '</p>';
                }
            } else {
                echo '<p><strong>Product Details:</strong> ' . htmlspecialchars($item['product_details']) . '</p>';
            }
        }

        
        if (!empty($item['customers_say'])) {
            $reviews = json_decode($item['customers_say'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo '<p><strong>Customer Reviews:</strong></p>';
                foreach ($reviews as $review) {
                    echo '<p>' . htmlspecialchars($review) . '</p>';
                }
            } else {
                echo '<p><strong>Customer Reviews:</strong> ' . htmlspecialchars($item['customers_say']) . '</p>';
            }
        }

        
        if (!empty($item['product_variations'])) {
            $variations = json_decode($item['product_variations'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo '<p><strong>Product Variations:</strong></p>';
                foreach ($variations as $key => $values) {
                    echo '<p><strong>' . htmlspecialchars($key) . ':</strong></p>';
                    echo '<ul>';
                    foreach ($values as $value) {
                        echo '<li>' . htmlspecialchars($value['value']) . ' (' . htmlspecialchars($value['asin']) . ')</li>';
                        if (isset($value['photo'])) {
                            echo '<img src="' . htmlspecialchars($value['photo']) . '" alt="' . htmlspecialchars($value['value']) . '" class="product-photo">';
                        }
                    }
                    echo '</ul>';
                }
            } else {
                echo '<p><strong>Product Variations:</strong> ' . htmlspecialchars($item['product_variations']) . '</p>';
            }
        }

        echo '<p><strong>Prime Eligible:</strong> ' . ($item['is_prime'] ? 'Yes' : 'No') . '</p>';
        echo '</div>';
    } else {
        echo 'Item not found.';
    }
} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}

require(__DIR__ . "/../../partials/flash.php");
?>
<link rel="stylesheet" href="<?php echo get_url('styles.css'); ?>">