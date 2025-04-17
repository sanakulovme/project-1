<?php
// Auth ma'lumotlari
$datafeedId = 'TowerEstAPI';
$username = 'TE56938759';
$password = 'zMn{_[eA8J';

// API versiyasi
$version = 13;

// URL — Get Branches uchun (token olish uchun har qanday endpoint ishlaydi)
$url = "https://webservices.vebra.com/export/{$datafeedId}/v{$version}/branch";

// Auth header
$auth = base64_encode("$username:$password");

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Basic {$auth}"
]);
curl_setopt($ch, CURLOPT_HEADER, true); // Token olish uchun header ham kerak
$response = curl_exec($ch);

// Tokenni header ichidan olish
preg_match('/Token: (\S+)/', $response, $matches);
$token = $matches[1] ?? null;

if ($token) {
    echo "Token olindi: $token\n";
} else {
    echo "Token olishda xatolik yuz berdi.\n";
}
curl_close($ch);
?>
