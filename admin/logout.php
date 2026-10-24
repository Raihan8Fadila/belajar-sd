<?php
/** Keluar dari admin (hanya lewat POST + CSRF supaya tidak bisa dipicu dari link luar). */
define('AREA_ADMIN', true);
require_once __DIR__ . '/../includes/fungsi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid()) {
    unset($_SESSION['admin_id'], $_SESSION['admin_user']);
    session_regenerate_id(true);
}
redirect('index.php');
