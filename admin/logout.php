<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
logout_admin();
redirect(ADMIN_URL . '/login.php');
