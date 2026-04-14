<?php
header('Content-Type: application/json');

$locationId = 8118;
$url = "https://api.openaq.org/v3/locations/{$locationId}/latest?limit=10";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'X-API-Key: 0d360e986f5ac175b0e2759096f03a8ac0f74086d0a55aebbbb567b3a1569ebf'
));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(['error' => curl_error($ch)]);
} else {
    http_response_code($httpcode);
    echo $response;
}
curl_close($ch);
?>
