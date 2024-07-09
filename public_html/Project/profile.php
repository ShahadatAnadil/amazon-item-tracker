<?php
//sha38 7/9/2024
require_once(__DIR__ . "/../../partials/nav.php");
if (!is_logged_in()) {
    die(header("Location: login.php"));
}
?>
<?php
if (isset($_POST["save"])) {
    $email = se($_POST, "email", null, false);
    $username = se($_POST, "username", null, false);

    $params = [":email" => $email, ":username" => $username, ":id" => get_user_id()];
    $db = getDB();
    $stmt = $db->prepare("UPDATE Users set email = :email, username = :username where id = :id");
    try {
        $stmt->execute($params);
        flash("Profile saved", "success");
    } catch (Exception $e) {
        if ($e->errorInfo[1] === 1062) {
            //https://www.php.net/manual/en/function.preg-match.php
            preg_match("/Users.(\w+)/", $e->errorInfo[2], $matches);
            if (isset($matches[1])) {
                flash("The chosen " . $matches[1] . " is not available.", "warning");
            } else {
                //TODO come up with a nice error message
                echo "<pre>" . var_export($e->errorInfo, true) . "</pre>";
            }
        } else {
            //TODO come up with a nice error message
            echo "<pre>" . var_export($e->errorInfo, true) . "</pre>";
        }
    }
    //select fresh data from table
    $stmt = $db->prepare("SELECT id, email, username from Users where id = :id LIMIT 1");
    try {
        $stmt->execute([":id" => get_user_id()]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            //$_SESSION["user"] = $user;
            $_SESSION["user"]["email"] = $user["email"];
            $_SESSION["user"]["username"] = $user["username"];
        } else {
            flash("User doesn't exist", "danger");
        }
    } catch (Exception $e) {
        flash("An unexpected error occurred, please try again", "danger");
        //echo "<pre>" . var_export($e->errorInfo, true) . "</pre>";
    }


    //check/update password
    $current_password = se($_POST, "currentPassword", null, false);
    $new_password = se($_POST, "newPassword", null, false);
    $confirm_password = se($_POST, "confirmPassword", null, false);
    if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
        if ($new_password === $confirm_password) {
            //TODO validate current
            $stmt = $db->prepare("SELECT password from Users where id = :id");
            try {
                $stmt->execute([":id" => get_user_id()]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if (isset($result["password"])) {
                    if (password_verify($current_password, $result["password"])) {
                        $query = "UPDATE Users set password = :password where id = :id";
                        $stmt = $db->prepare($query);
                        $stmt->execute([
                            ":id" => get_user_id(),
                            ":password" => password_hash($new_password, PASSWORD_BCRYPT)
                        ]);

                        flash("Password reset", "success");
                    } else {
                        flash("Current password is invalid", "warning");
                    }
                }
            } catch (Exception $e) {
                echo "<pre>" . var_export($e->errorInfo, true) . "</pre>";
            }
        } else {
            flash("New passwords don't match", "warning");
        }
    }
}
?>

<?php
$email = get_user_email();
$username = get_username();
?>
<form method="POST" onsubmit="return validate(this);">
    <div class="mb-3">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" value="<?php se($email); ?>" />
    </div>
    <div class="mb-3">
        <label for="username">Username</label>
        <input type="text" name="username" id="username" value="<?php se($username); ?>" />
    </div>
    <!-- DO NOT PRELOAD PASSWORD -->
    <div>Password Reset</div>
    <div class="mb-3">
        <label for="cp">Current Password</label>
        <input type="password" name="currentPassword" id="cp" />
    </div>
    <div class="mb-3">
        <label for="np">New Password</label>
        <input type="password" name="newPassword" id="np" />
    </div>
    <div class="mb-3">
        <label for="conp">Confirm Password</label>
        <input type="password" name="confirmPassword" id="conp" />
    </div>
    <input type="submit" value="Update Profile" name="save" />
</form>

<script>
    /*
    function validate(form) {
        let email = form.email.value.trim();
        let username = form.username.value.trim();
        let currentPassword = form.currentPassword.value;
        let newPassword = form.newPassword.value;
        let confirmPassword = form.confirmPassword.value;

        let isValid = true;

       
        if (!validateEmail(email)) {
            flash("[Client] Invalid email address", "danger");
            isValid = false;
        }

        
        if (!validateUsername(username)) {
            flash("[Client] Username must only contain 3-16 characters a-z, 0-9, _, or -", "danger");
            isValid = false;
        }

        
        let usernameAvailable = isUsernameAvailable(username);
        if (!usernameAvailable) {
            flash("[Client] Username is already taken", "danger");
            isValid = false;
        }

        
        if (currentPassword.trim() !== "" && !validatePassword(currentPassword)) {
            flash("[Client] Invalid current password", "danger");
            isValid = false;
        }

        
        if ((newPassword.trim() !== "" || confirmPassword.trim() !== "") && !validatePassword(newPassword)) {
            flash("[Client] New password must be at least 8 characters long", "danger");
            isValid = false;
        }

        
        if (newPassword !== confirmPassword) {
            flash("[Client] New passwords do not match", "danger");
            isValid = false;
        }

        
        if (newPassword !== confirmPassword) {
            flash("[Client] Password and confirm password must match", "warning");
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

    /*function flash(message, type) {
        console.log(message);
        // Implement actual flashing logic if needed
    }*/

    function isUsernameAvailable(username) {
        const url = 'check_username.php'; 
        let xhr = new XMLHttpRequest();
        xhr.open('POST', url, false); 
        xhr.setRequestHeader('Content-Type', 'application/json');

        let available = false;
        xhr.onreadystatechange = function () {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    available = response.available;
                } else {
                    console.error('Error checking username availability:', xhr.status);
                }
            }
        };

        xhr.send(JSON.stringify({ username }));
        return available;
    }

    */

</script>
<?php
require_once(__DIR__ . "/../../partials/flash.php");
?>