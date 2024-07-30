<?php
$domain = $_SERVER["HTTP_HOST"];
if (strpos($domain, ":")) {
    $domain = explode(":", $domain)[0];
}

$localWorks = false; // some people have issues with localhost for the cookie params
// if you're one of those people make this false

if (session_status() == PHP_SESSION_NONE) {
    // this is an extra condition added to "resolve" the localhost issue for the session cookie
    if (($localWorks && $domain == "localhost") || $domain != "localhost") {
        session_set_cookie_params([
            "lifetime" => 60 * 60,
            "path" => "/Project",
            "domain" => $domain,
            "secure" => true,
            "httponly" => true,
            "samesite" => "lax"
        ]);
    } else {
        session_set_cookie_params([
            "lifetime" => 60 * 60,
            "path" => "/Project",
            "secure" => true,
            "httponly" => true,
            "samesite" => "lax"
        ]);
    }
    session_start();
}
?>