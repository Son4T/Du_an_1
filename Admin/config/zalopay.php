<?php

/*
 * Cấu hình ZaloPay Sandbox.
 * Bộ AppID 2554 là bộ mã thử nghiệm công khai dùng cho môi trường Sandbox.
 */
$config = [
    'environment' => 'sandbox',
    'app_id' => (int) (getenv('ZALOPAY_APP_ID') ?: 2554),
    'key1' => getenv('ZALOPAY_KEY1')
        ?: 'sdngKKJmqEMzvh5QQcdD2A9XBSKUNaYn',
    'key2' => getenv('ZALOPAY_KEY2')
        ?: 'trMrHtvjo6myautxDUiAcYsVtaeQ8nhf',

    // Để trống vẫn thanh toán được trên localhost.
    // Chỉ điền khi dùng hosting, ngrok hoặc Cloudflare Tunnel.
    'public_url' => getenv('ZALOPAY_PUBLIC_URL') ?: '',

    'create_endpoint' => 'https://sb-openapi.zalopay.vn/v2/create',
    'query_endpoint' => 'https://sb-openapi.zalopay.vn/v2/query',
    'expire_duration_seconds' => 900,
    'preferred_payment_method' => ['zalopay_wallet'],
    'request_timeout' => 35,
    'connect_timeout' => 10,
];

$localConfigFile = __DIR__ . '/zalopay.local.php';

if (is_file($localConfigFile)) {
    $localConfig = require $localConfigFile;

    if (is_array($localConfig)) {
        $config = array_replace($config, $localConfig);
    }
}

return $config;
