<?php
require(__DIR__ . "/../../partials/nav.php");
?>

<div class="container-fluid hero-section text-center text-dark" style="background: url('path/to/your/background-image.jpg') no-repeat center center/cover; height: 80vh;">
    <div class="hero-content d-flex align-items-center justify-content-center h-100">
        <div>
            <h1>Welcome to Amazon Items Tracker</h1>
            <p>Your go-to platform for tracking and managing your favorite Amazon products.</p>
            <a href="<?php echo get_url('items.php'); ?>" class="btn btn-primary">Explore Items</a>
        </div>
    </div>
</div>

<div class="container mt-5">
    
    <div class="row mb-5">
        <div class="col-md-6">
            <h2>About Us</h2>
            <p>Amazon Items Tracker is designed to help you manage and track your favorite products on Amazon. Whether you want to keep an eye on price changes, save items for later, or organize your shopping lists, we have the tools you need to stay organized and make informed purchasing decisions.</p>
        </div>
        <div class="col-md-6">
            <img src="path/to/your/about-image.jpg" alt="About Us" class="img-fluid rounded">
        </div>
    </div>

    
    <div class="row text-center mb-5">
        <h2 class="mb-4">Our Features</h2>
        <div class="col-md-4">
            <div class="feature-box p-4">
                <i class="fas fa-heart fa-3x mb-3"></i>
                <h3>Favorite Products</h3>
                <p>Save and manage your favorite products in one place.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-box p-4">
                <i class="fas fa-tags fa-3x mb-3"></i>
                <h3>Track Prices</h3>
                <p>Monitor price changes and get notified of the best deals.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-box p-4">
                <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                <h3>Organize Cart</h3>
                <p>Keep your shopping cart organized and ready for checkout.</p>
            </div>
        </div>
    </div>

    
    <div class="row mb-5">
        <h2 class="mb-4">Latest Items</h2>
        <?php
        $db = getDB();
        $query = "SELECT id, product_title, product_price, currency, product_photo FROM `IT202-S24-ProductDetails` ORDER BY created DESC LIMIT 3";
        $stmt = $db->prepare($query);
        $items = [];
        try {
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching latest items: " . var_export($e, true));
            flash("Unable to fetch latest items", "danger");
        }
        ?>
        <?php foreach ($items as $item): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <img src="<?php echo htmlspecialchars($item['product_photo']); ?>" class="card-img-top" alt="Product Image">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($item['product_title']); ?></h5>
                        <p class="card-text">Price: <?php echo htmlspecialchars($item['product_price']); ?> <?php echo htmlspecialchars($item['currency']); ?></p>
                        <a href="<?php echo get_url('item.php?id=' . urlencode($item['id'])); ?>" class="btn btn-primary">View</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
if (is_logged_in(true)) {
    
    error_log("Session data: " . var_export($_SESSION, true));
}
require(__DIR__ . "/../../partials/flash.php");
?>


<style>
.hero-section {
    color: #000; 
    background: url('path/to/your/hero-image.jpg') no-repeat center center/cover;
    height: 70vh;
    display: flex;
    justify-content: center;
    align-items: center;
    text-align: center;
}
.hero-section h1 {
    font-size: 3rem;
    font-weight: 700;
}
.hero-section p {
    font-size: 1.5rem;
    margin-bottom: 20px;
}
.feature-box {
    background-color: #f8f9fa;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}
.feature-box i {
    color: #007bff;
}
</style>
