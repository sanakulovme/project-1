<?php

$token = $_GET['token'] ?? null;
if (!$token) {
    die("Tokenni olish uchun avval index.php faylini ishga tushiring.");
}
// Tokenni yuqoridagi kod orqali olgan bo‘lishingiz kerak
// $token = 'token_bu_yerga_qo‘yiladi'; // Yuqoridan olgan tokenni shu yerga joylashtiring
$datafeedId = 'TowerEstAPI';
$version = 13;
$branchId = 2; // Misol uchun xususiy filial

$url = "https://webservices.vebra.com/export/{$datafeedId}/v{$version}/branch/{$branchId}/property";

$authToken = base64_encode($token);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Basic {$authToken}"
]);
$response = curl_exec($ch);
curl_close($ch);

// XML ni parse qilish
$xml = simplexml_load_string($response);

echo "<pre>";
print_r($xml);
echo "</pre>";
?>
