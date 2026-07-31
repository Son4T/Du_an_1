<?php
// File khung ban đầu. Thành viên 1 sẽ hoàn thiện session, redirect,
// kiểm tra đăng nhập, CSRF và các hàm dùng chung.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}
