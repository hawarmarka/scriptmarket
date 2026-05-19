<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
redirect(is_admin_logged_in() ? ADMIN_URL . '/dashboard.php' : ADMIN_URL . '/login.php');
