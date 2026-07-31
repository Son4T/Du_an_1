<?php
// Cấu hình CSDL. Có thể đổi các giá trị này khi đưa dự án lên hosting.
define('DB_SERVER', getenv('DB_HOST') ?: 'localhost');
define('DB_USERNAME', getenv('DB_USER') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'urban_tribe');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    die('Không thể kết nối cơ sở dữ liệu. Hãy kiểm tra file Admin/config/database.php và import database.sql.');
}
