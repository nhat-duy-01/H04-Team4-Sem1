<?php
/**
 * MEDICONNECT LOGOUT HANDLER
 * This script securely clears all user session data and terminates the connection.
 */

// 1. Initialize the session to gain access to current variables
session_start();

// 2. Clear all session variables (fullName, userId, cart, etc.)
// This removes the data but keeps the session ID temporarily.
session_unset();

// 3. Completely destroy the current session on the server
session_destroy();

// 4. (Optional but Recommended) Delete the session cookie from the user's browser
// This ensures the session cannot be hijacked or reused.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 5. Redirect the user back to the authentication/login page
header("Location: authentication.php");

// 6. Terminate the script to ensure no further code is executed after redirection
exit();
?>