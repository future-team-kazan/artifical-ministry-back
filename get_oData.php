<?php
// БЕРЕМ ИНФОРМАЦИЮ ИЗ 1С


header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS'); 
header('Access-Control-Allow-Headers: X-Requested-With, Content-type');

$url = 'http://95.68.242.113:8887/zup/odata/standard.odata/CalculationRegister_%D0%9D%D0%B0%D1%87%D0%B8%D1%81%D0%BB%D0%B5%D0%BD%D0%B8%D1%8F?$format=json';

$pass = "";
$auth = base64_encode("СавичеваЮ:");
$authHeader = "Authorization: Basic ". $auth;
//$authHeader = "Authorization: Basic 0KHQsNCy0LjRh9C10LLQsNCuOg==";

$streamContext = stream_context_create(array(
	'http' => array(
		'method'  => 'GET',
		'header'  => $authHeader
	)
));
$result = file_get_contents($url, true, $streamContext);

// ПРОИЗВОДИМ ОБРАБОТКУ ДАННЫХ
echo '<pre>';
//print_r($http_response_header); // Вывод данных
echo '</pre>';

echo '<pre>';
print_r($result); // Вывод данных
echo '</pre>';



?>