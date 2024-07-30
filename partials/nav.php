<?php
require_once(__DIR__ . "/../lib/functions.php");
require_once(__DIR__ . "/session.php"); // Include session settings

// Note: this is to resolve cookie issues with port numbers
$domain = $_SERVER["HTTP_HOST"];
if (strpos($domain, ":")) {
    $domain = explode(":", $domain)[0];
}
$localWorks = false; // some people have issues with localhost for the cookie params
// if you're one of those people make this false

// this is an extra condition added to "resolve" the localhost issue for the session cookie
if (($localWorks && $domain == "localhost") || $domain != "localhost") {
    if (session_status() == PHP_SESSION_NONE) {
        session_set_cookie_params([
            "lifetime" => 60 * 60,
            "path" => "/Project",
            "domain" => $domain,
            "secure" => true,
            "httponly" => true,
            "samesite" => "lax"
        ]);
    }
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();  // Start the session
}
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- include css and js files -->
<link rel="stylesheet" href="<?php echo get_url('/Project/styles.css'); ?>">
<script src="<?php echo get_url('/Project/helpers.js'); ?>"></script>
<nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Amazon</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if (is_logged_in()) : ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo get_url('/Project/home.php'); ?>">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo get_url('/Project/profile.php'); ?>">Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo get_url('/Project/items.php'); ?>">Listing</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo get_url('/Project/favorites.php'); ?>">Favorites</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo get_url('/Project/my_cart.php'); ?>">My Cart</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo get_url('/Project/my_purchases.php'); ?>">My Purchases</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo get_url('/Project/user_entities.php'); ?>">User Entity</a></li>
                <?php endif; ?>
                <?php if (!is_logged_in()) : ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo get_url('/Project/login.php'); ?>">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo get_url('/Project/register.php'); ?>">Register</a></li>
                <?php endif; ?>
                <?php if (has_role("Admin")) : ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Admin
                        </a>
                        <ul class="dropdown-menu">
                            <li class="dropdown-item"><a class="nav-link" href="<?php echo get_url('/Project/admin/create_role.php'); ?>">Create Role</a></li>
                            <li class="dropdown-item"><a class="nav-link" href="<?php echo get_url('/Project/admin/list_roles.php'); ?>">List Roles</a></li>
                            <li class="dropdown-item"><a class="nav-link" href="<?php echo get_url('/Project/admin/assign_roles.php'); ?>">Assign Roles</a></li>
                            <li class="dropdown-item"><a class="nav-link" href="<?php echo get_url('/Project/admin/all_associations.php'); ?>">See all associations</a></li>
                            <li class="dropdown-item"><a class="nav-link" href="<?php echo get_url('/Project/admin/unassociated_items.php'); ?>">See all unassociations</a></li>
                            <li class="dropdown-item"><a class="nav-link" href="<?php echo get_url('/Project/admin/admin_associate_entities.php'); ?>">Assign associations</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Item Management
                        </a>
                        <ul class="dropdown-menu">
                            <li class="dropdown-item"><a class="nav-link" href="<?php echo get_url('/Project/admin/create_item.php'); ?>">Create Item</a></li>
                            <li class="dropdown-item"><a class="nav-link" href="<?php echo get_url('/Project/admin/list_items.php'); ?>">Manage Items</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
                <?php if (is_logged_in()) : ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo get_url('/Project/logout.php'); ?>">Logout</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>