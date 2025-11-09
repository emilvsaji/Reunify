<?php
require_once 'config/config.php';

if (isLoggedIn()) {
    // Log activity
    logActivity($_SESSION['user_id'], 'logout', 'User logged out');
    
    // Destroy session
    session_destroy();
}

header('Location: index.php');
exit();
?>
