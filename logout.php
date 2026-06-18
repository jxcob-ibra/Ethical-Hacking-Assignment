<?php
/**
 * MyEduConnect - Logout Page
 * Learning Management System
 */

require_once 'app/config/config.php';
require_once 'app/security/functions.php';
require_once 'app/security/auth.php';

// Logout user
logout();

// Redirect to home page with message
redirect(APP_URL . '/login.php', 'You have been logged out successfully.', 'success');
