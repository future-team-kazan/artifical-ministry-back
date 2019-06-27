<?php
// В ЭТОМ МОДУЛЕ МЫ ВЫГРУЖАЕМ ИНФОРМАЦИЮ ИЗ 1С по должностям


header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS'); 
header('Access-Control-Allow-Headers: X-Requested-With, Content-type');

//Получение данных от Front-end

$input = json_decode(file_get_contents('php://input'), true);

//Чтение данных о базах данных
$db_conf_data = json_decode(file_get_contents('BD_config/server.conf'), true);
$server_array_size = count($db_conf_data->servers); // кол-во серверов в json файле
$server_count = 0; // текущий сервер
$max_loop_count = 10; // максимум 10 раз проходим по всем серверам
$loop_count = 0; // текущий проход по циклу

while ($loop_count < 10) {
	$server = $db_conf_data[$server_count+1]['address'];
	$user = $db_conf_data[$server_count+1]['db_username'];
	$pass = $db_conf_data[$server_count+1]['db_password'];
	$bd = $db_conf_data[$server_count+1]['db_name'];
	$bd_info_table_prefix = $db_conf_data[$server_count+1]['table_prefix'];

	$bd_info = new mysqli("$server", "$user", "$pass", "$bd");
	$bd_info->set_charset("utf8");
	if ($bd_info->connect_error) 
	{
		// Тут лучше записать в лог, что сервер $server->db_name недоступен потому что ('Connect Error (' . $bd_info->connect_errno . ') ' . $bd_info->connect_error)
		$server_count++;
		if ($server_count == $server_array_size) {
			$loop_count++;
			$server_count = 0;
		}
	} else {
		break;
	}
}

// эта проверка нужна на то, что ошибки соединения нет нужна, чтобы избежать ситуации, когда мы прошлись по всем БД и не смогли подключиться ни к одной из них
if (!$bd_info->connect_error) {
	//используем соединение по назначению
}


// БЕРЕМ ИНФОРМАЦИЮ ИЗ 1С
$url = 'http://95.68.242.113:8887/zup/odata/standard.odata/Catalog_%D0%94%D0%BE%D0%BB%D0%B6%D0%BD%D0%BE%D1%81%D1%82%D0%B8?$format=json';

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
//echo '<pre>';
//print_r($http_response_header); // Вывод данных
//echo '</pre>';

//echo '<pre>';
//print_r($result); // Вывод данных
//echo '</pre>';

//$data_json = utf8_encode($result);
//print_r($data_json);

//парсим json
$json = json_decode($result, true);

//foreach($json['value'] as $item) {
//    foreach(array_keys($item) as $key){
//	echo $key;
//	echo "<br>";
//	}
//}


foreach($json['value'] as $item) {
    $key = $item['Ref_Key'];

	if ($item['Description'] != NULL) {$description =  $item['Description'];}
	if ($item['НаименованиеКраткое'] != NULL) {$short_name = $item['НаименованиеКраткое'];}

	print_r($key."<br>");
	//Получаем данные по школам
	$sql = "SELECT * FROM ".$bd_info_table_prefix."positions WHERE \"1C\" = '".$key."'";
	//$positions = mysqli_query($bd_info,$sql);
	
	if ($positions = mysqli_query($bd_info,$sql)) {
	print_r($positions);
		$rows_positions = mysqli_num_rows($positions);//Смотрим, есть ли такая должность
		
		if ($rows_organization == 0) {
			//Организация отсутствует, добавляем органиазцию
			$sql = "INSERT INTO ".$bd_info_table_prefix."positions (`id`, `Name`, `Description`, `\"1C\"`) VALUES (NULL,'$short_name','$description','$key')";
			echo $sql;
			mysqli_query($bd_info,$sql);
			print_r("добавили");
		}
		else {
			//Организация присутствует, обновляем информацию
		}
	}
}

mysqli_free_result($positions);
mysqli_close($bd_info);
?>