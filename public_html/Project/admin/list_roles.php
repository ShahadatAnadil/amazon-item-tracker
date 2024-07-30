<?php
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    redirect("home.php");
}

// Handle the toggle first so select pulls fresh data
if (isset($_POST["role_id"])) {
    $role_id = se($_POST, "role_id", "", false);
    if (!empty($role_id)) {
        $db = getDB();
        $stmt = $db->prepare("UPDATE Roles SET is_active = !is_active WHERE id = :rid");
        try {
            $stmt->execute([":rid" => $role_id]);
            flash("Updated Role", "success");
        } catch (PDOException $e) {
            flash(var_export($e->errorInfo, true), "danger");
        }
    }
}

$query = "SELECT id, name, description, if(is_active, 'active', 'disabled') as 'Active' FROM Roles";
$params = null;
$search = "";
if (isset($_POST["role"])) {
    $search = se($_POST, "role", "", false);
    $query .= " WHERE name LIKE :role";
    $params =  [":role" => "%$search%"];
}
$query .= " ORDER BY id DESC LIMIT 10";
$db = getDB();
$stmt = $db->prepare($query);
$roles = [];
try {
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($results) {
        $roles = $results;
    } else {
        flash("No matches found", "warning");
    }
} catch (PDOException $e) {
    flash(var_export($e->errorInfo, true), "danger");
}

$table = ["data" => $roles, "post_self_form" => ["name" => "role_id", "label" => "Toggle", "classes" => "btn btn-secondary"]];

?>
<div class="container-fluid">
    <h1>List Roles</h1>
    <form method="POST">
        <?php render_input(["type" => "search", "name" => "role", "placeholder" => "Role Filter", "value" => $search]); ?>
        <?php render_button(["text" => "Search", "type" => "submit"]); ?>
    </form>
    <?php render_table($table); ?>

    <script>
        let forms = [...document.forms];
        forms.shift();
        let search = "<?php se($search); ?>";
        for (let form of forms) {
            let ele = document.createElement("input");
            ele.type = "hidden";
            ele.name = "role";
            ele.value = search;
            form.appendChild(ele);
        }
    </script>
</div>
<?php
require_once(__DIR__ . "/../../../partials/flash.php");
?>