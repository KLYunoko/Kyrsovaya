<?php
require_once 'config.php';

// Unset all of the session variables.
$_SESSION = [];

// If it's desired to kill the session completely, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Delete "remember me" cookies if they exist
if (isset($_COOKIE['remember_me_selector']) && isset($_COOKIE['remember_me_token'])) {
    $params = session_get_cookie_params();
    setcookie('remember_me_selector', '', time() - 42000, '/', '', true, true);
    setcookie('remember_me_token', '', time() - 42000, '/', '', true, true);

    // Also remove the tokens from the database
    try {
        $stmt = $pdo->prepare("DELETE FROM auth_tokens WHERE selector = ?");
        $stmt->execute([$_COOKIE['remember_me_selector']]);
    } catch (PDOException $e) {
        // Handle error appropriately (log it, but don't show to the user)
        error_log("Database error during logout: " . $e->getMessage());
    }
}


header("Location: index.php");
exit;