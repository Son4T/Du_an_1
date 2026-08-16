<?php
require_once __DIR__ . '/config/bootstrap.php';
if (!empty($_SESSION['admin_id'])) redirect('views/dashboard.php');
redirect('views/login.php');