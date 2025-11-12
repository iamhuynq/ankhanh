<?php
header('Content-Type: application/json; charset=utf-8');
const GOOGLE_SCRIPT_URL = 'https://script.google.com/macros/s/AKfycbyyaWGgq8bjKgbp93tDPL59g4TsEgka_52GOnnYyPH5Y0x6D2mLkZRTO53aBCfxKS9MPQ/exec';

// ====== HELPER ======
function sanitizeInput(string $data): string {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function respond(int $status, string $message): void {
    http_response_code($status);
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, 'Phương thức không hợp lệ.');
}

$fullname = sanitizeInput($_POST['fullname'] ?? '');
$phone    = sanitizeInput($_POST['phone'] ?? '');
$email    = sanitizeInput($_POST['email'] ?? '');
$type    = sanitizeInput($_POST['type'] ?? '');

if ($fullname === '' || $phone === '' || $email === '') {
    respond(400, 'Thiếu thông tin bắt buộc.');
}

$payload = [
    'fullname' => $fullname,
    'phone'    => "'" .$phone,
    'email'    => $email,
    'type'     => $type,
    'timestamp'=> date('d-m-Y H:i:s')
];

$ch = curl_init(GOOGLE_SCRIPT_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,  // ✅ Cho phép theo dõi redirect (sửa lỗi 302)
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($payload),
    CURLOPT_TIMEOUT => 10,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
curl_close($ch);

if ($error) {
    respond(500, 'Không thể kết nối đến Google Script: ' . $error);
}

if ($httpCode === 200 && trim($response) === 'SUCCESS') {
    respond(200, 'Gửi thành công! Cảm ơn bạn đã liên hệ.');
} else {
    respond(502, 'Lỗi khi ghi dữ liệu vào Google Sheet (HTTP ' . $httpCode . ').');
}