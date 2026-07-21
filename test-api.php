<?php
$ch = curl_init('https://agentesolutionsback-production.up.railway.app/api/forgot-password');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => 'ppechkoh@gmail.com']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if(curl_errno($ch)) {
    echo 'Curl error: ' . curl_error($ch);
}
echo "HTTP Code: $httpcode\n";
echo "Response: $response\n";
curl_close($ch);
