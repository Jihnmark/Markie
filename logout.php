<?php
require_once 'config.php';

// Clear all session data
session_unset();
session_destroy();

// Redirect to login page
redirect('index.php');
?>