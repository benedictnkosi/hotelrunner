<?php

$body = file_get_contents('php://input');
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL,"https://ete.hotelrunner.co.za/no_auth/reservations/import/queuel");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS,
    "message=$body");

// In real life you should use something like:
// curl_setopt($ch, CURLOPT_POSTFIELDS,
//          http_build_query(array('postvar1' => 'value1')));

// Receive server response ...
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$server_output = curl_exec($ch);

curl_close($ch);