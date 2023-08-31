<?php


$body = file_get_contents('php://input');
$ch = curl_init();
$protocol = "http//";
$server = $_SERVER['SERVER_NAME'];
if (isset($_SERVER['HTTPS']))
{
    $protocol = "https//";
}
echo $server;
curl_setopt($ch, CURLOPT_URL,"http://$server/no_auth/import/queue");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS,
    "message=$body");

// Receive server response ...
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$server_output = curl_exec($ch);

curl_close($ch);