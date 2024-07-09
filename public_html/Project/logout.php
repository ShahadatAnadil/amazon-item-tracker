<?php
session_start();
require(__DIR__ . "/../../lib/functions.php");
reset_session();
//sha38 7/9/2024
flash("Successfully logged out", "success");
header("Location: login.php");