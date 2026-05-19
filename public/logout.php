<?php
require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . '/auth.php';
logout_user();
flash('info', 'Çıkış yapıldı. Görüşürüz!');
redirect(PUBLIC_URL . '/index.php');
