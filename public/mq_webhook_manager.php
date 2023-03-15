<?php

echo "test";
$body = file_get_contents('php://input');
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL,"https://ete.hotelrunner.co.za/no_auth/reservations/import/queue");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS,
    "message=$body");

// Receive server response ...
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$server_output = curl_exec($ch);

curl_close($ch);

echo "test2";