
<?php

/**
 * Passing $redirect as true will auto redirect a logged out user to the $destination.
 * The destination defaults to login.php
 */
if (!function_exists('is_logged_in')) {
    function is_logged_in($redirect = false, $destination = "login.php")
    {
        $isLoggedIn = isset($_SESSION["user"]);
        if ($redirect && !$isLoggedIn) {
            //if this triggers, the calling script won't receive a reply since die()/exit() terminates it
            flash("You must be logged in to view this page", "warning");
            die(header("Location: $destination"));
        }
        return $isLoggedIn;
    }
}

if (!function_exists('get_user_id')) {
    function get_user_id()
    {
        if (is_logged_in()) { //we need to check for login first because "user" key may not exist
            return se($_SESSION["user"], "id", false, false);
        }
        return false;
    }
}

if (!function_exists('has_role')) {
    function has_role($role)
    {
        if (is_logged_in() && isset($_SESSION["user"]["roles"])) {
            foreach ($_SESSION["user"]["roles"] as $r) {
                if ($r["name"] === $role) {
                    return true;
                }
            }
        }
        return false;
    }
}

if (!function_exists('get_username')) {
    function get_username()
    {
        if (is_logged_in()) { //we need to check for login first because "user" key may not exist
            return se($_SESSION["user"], "username", "", false);
        }
        return "";
    }
}

if (!function_exists('get_user_email')) {
    function get_user_email()
    {
        if (is_logged_in()) { //we need to check for login first because "user" key may not exist
            return se($_SESSION["user"], "email", "", false);
        }
        return "";
    }
}
?>