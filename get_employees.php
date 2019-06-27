<?php
// В ЭТОМ МОДУЛЕ МЫ ВЫГРУЖАЕМ ИНФОРМАЦИЮ ИЗ 1С по сотрудникам


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


// БЕРЕМ ИНФОРМАЦИЮ ИЗ 1С ПО СОТРУДНИКАМ
$url = 'http://95.68.242.113:8887/zup/odata/standard.odata/Catalog_%D0%A1%D0%BE%D1%82%D1%80%D1%83%D0%B4%D0%BD%D0%B8%D0%BA%D0%B8?$format=json';

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
$employees = json_decode($result, true);

// БЕРЕМ ИНФОРМАЦИЮ ИЗ 1С ПО кадровой ИСТОРИИ СОТРУДНИКОВ
$url = 'http://95.68.242.113:8887/zup/odata/standard.odata/InformationRegister_%D0%9A%D0%B0%D0%B4%D1%80%D0%BE%D0%B2%D0%B0%D1%8F%D0%98%D1%81%D1%82%D0%BE%D1%80%D0%B8%D1%8F%D0%A1%D0%BE%D1%82%D1%80%D1%83%D0%B4%D0%BD%D0%B8%D0%BA%D0%BE%D0%B2?$format=json';

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
$result_1 = file_get_contents($url, true, $streamContext);
$employees_history = json_decode($result_1, true);
//print_r ($employees_history);





foreach($employees['value'] as $item) {
    $key = $item['Ref_Key'];//id сотрудника в базе 1С
	$name = $item['Description'];//ФИО
	$organization = $item['ГоловнаяОрганизация_Key'];
	
	//Получаем данные по организации
	$sql = "SELECT * FROM ".$bd_info_table_prefix."organizations WHERE 1C = '".$organization."'";
	$organization = mysqli_query($bd_info,$sql);
	
	$organization_temp = mysqli_fetch_assoc($organization);
	$organization_id = $organization_temp ['id'];//Получаем id организации в базе по базе
	
	//Смотрим кадровую историю сотрудинка и ищем его должность
	foreach($employees_history['value'] as $item_1) {
		foreach($item_1['RecordSet'] as $item_2) {
			/*foreach(array_keys($item_2) as $key_1)
			{
				echo $key_1;
				echo "<br>";
			}*/
			if ($item_2['Сотрудник_Key'] == $key) {
				if ($item_2['Должность_Key'] != NULL) {$position_1C_id =  $item_2['Должность_Key'];}
			} 
		}
		
		
		
	}
	
	//Получаем данные по должности
	$sql = "SELECT * FROM ".$bd_info_table_prefix."positions WHERE 1C = '".$position_1C_id."'";
	$position = mysqli_query($bd_info,$sql);
	
	$position_temp = mysqli_fetch_assoc($position);
	$position_id = $position_temp ['id'];//Получаем id должности в базе
	//echo $sql;
	
	//Смотрим, если ли уже такой сотрудник в базе
	
	$sql = "SELECT * FROM ".$bd_info_table_prefix."employees WHERE 1C = '".$key."'";
	$employee = mysqli_query($bd_info,$sql);
	
	$rows_employee = mysqli_num_rows($employee);//Смотрим, есть ли такая организация
	
	if ($rows_employee == 0) {
		//Организация отсутствует, добавляем сотрудника
		$sql = "INSERT INTO `".$bd_info_table_prefix."employees`(`id`, `Name`, `Organization`, `Position`, `1C`) VALUES (NULL,'$name','$organization_id','$position_id','$key')";
		//echo $sql;
		mysqli_query($bd_info,$sql);
	}
	else {
		//Организация присутствует, обновляем информацию
	}
}

mysqli_free_result($position);
mysqli_free_result($organization);
mysqli_close($bd_info);
?>