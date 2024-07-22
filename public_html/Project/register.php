<?php
require(__DIR__ . "/../../partials/nav.php");
reset_session();
?>
<div class="container-fluid">
    <form onsubmit="return validate(this)" method="POST">
        <?php render_input(["type" => "email", "id" => "email", "name" => "email", "label" => "Email", "rules" => ["required" => true]]); ?>
        <?php render_input(["type" => "text", "id" => "username", "name" => "username", "label" => "Username", "rules" => ["required" => true, "maxlength" => 30]]); ?>
        <?php render_input(["type" => "password", "id" => "password", "name" => "password", "label" => "Password", "rules" => ["required" => true, "minlength" => 8]]); ?>
        <?php render_input(["type" => "password", "id" => "confirm", "name" => "confirm", "label" => "Confirm Password", "rules" => ["required" => true, "minlength" => 8]]); ?>
        <?php render_button(["text" => "Register", "type" => "submit"]); ?>
    </form>
</div>

<script>
    function validate(form) {
        //TODO 1: implement JavaScript validation sha38 7/9/2024
        //ensure it returns false for an error and true for success
        //sha38 7/9/2024
        let email = form.email.value.trim();
        let username = form.username.value.trim();
        let password = form.password.value;
        let confirm = form.confirm.value;

        let isValid = true;

        if (!validateEmail(email)) {
            flash("[Client] Invalid email address", "danger");
            isValid = false;
        }

        if (!validateUsername(username)) {
            flash("[Client] Username must only contain 3-16 characters a-z, 0-9, _, or -", "danger");
            isValid = false;
        }

        if (!validatePassword(password)) {
            flash("[Client] Password must be at least 8 characters long", "danger");
            isValid = false;
        }

        if (password !== confirm) {
            flash("[Client] Passwords do not match", "danger");
            isValid = false;
        }

        return isValid;
    }

    function validateEmail(email) {

        let regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    function validateUsername(username) {

        let regex = /^[a-zA-Z0-9_-]{3,16}$/;
        return regex.test(username);
    }

    function validatePassword(password) {

        return password.length >= 8;
    }
    //sha38 7/9/2024

    /*function flash(message, type) {
        // Mocking flash function for demonstration
        console.log(message);
        // Implement your actual flashing logic here (e.g., showing alerts, displaying messages on the page)
    }*/
</script>
<?php
//TODO 2: add PHP Code
if (isset($_POST["email"]) && isset($_POST["password"]) && isset($_POST["confirm"]) && isset($_POST["username"])) {
    $email = se($_POST, "email", "", false);
    $password = se($_POST, "password", "", false);
    $confirm = se($_POST, "confirm", "", false);
    $username = se($_POST, "username", "", false);
    //TODO 3
    $hasError = false;
    if (empty($email)) {
        flash("Email must not be empty", "danger");
        $hasError = true;
    }
    //sanitize
    $email = sanitize_email($email);
    //validate
    if (!is_valid_email($email)) {
        flash("Invalid email address", "danger");
        $hasError = true;
    }
    if (!is_valid_username($username)) {
        flash("Username must only contain 3-16 characters a-z, 0-9, _, or -", "danger");
        $hasError = true;
    }
    if (empty($password)) {
        flash("password must not be empty", "danger");
        $hasError = true;
    }
    if (empty($confirm)) {
        flash("Confirm password must not be empty", "danger");
        $hasError = true;
    }
    if (!is_valid_password($password)) {
        flash("Password too short", "danger");
        $hasError = true;
    }
    if (
        strlen($password) > 0 && $password !== $confirm
    ) {
        flash("Passwords must match", "danger");
        $hasError = true;
    }
    if (!$hasError) {
        //TODO 4
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO Users (email, password, username) VALUES(:email, :password, :username)");
        try {
            $stmt->execute([":email" => $email, ":password" => $hash, ":username" => $username]);
            flash("Successfully registered!", "success");
        } catch (PDOException $e) {
            users_check_duplicate($e->errorInfo);
        }
    }
}
?>
<?php
require(__DIR__ . "/../../partials/flash.php");
?>