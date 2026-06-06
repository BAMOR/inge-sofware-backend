<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$url = $_GET['url'] ?? '';
if (!$url) { echo json_encode(["error" => "URL requerida"]); exit(); }

$method = $_SERVER['REQUEST_METHOD'];
$body   = file_get_contents("php://input");

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

if (in_array($method, ['POST','PUT','DELETE'])) {
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
}

$response = curl_exec($ch);
curl_close($ch);
echo $response ?: json_encode(["error" => "Sin respuesta"]);