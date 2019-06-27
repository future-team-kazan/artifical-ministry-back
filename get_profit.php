<?php
// В ЭТОМ МОДУЛЕ МЫ ВЫГРУЖАЕМ ИНФОРМАЦИЮ ИЗ 1С по начислениям заработной платы


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


// БЕРЕМ ИНФОРМАЦИЮ ИЗ 1С ПО начислениям
$url = 'http://95.68.242.113:8887/zup/odata/standard.odata/AccumulationRegister_%D0%92%D0%B7%D0%B0%D0%B8%D0%BC%D0%BE%D1%80%D0%B0%D1%81%D1%87%D0%B5%D1%82%D1%8B%D0%A1%D0%A1%D0%BE%D1%82%D1%80%D1%83%D0%B4%D0%BD%D0%B8%D0%BA%D0%B0%D0%BC%D0%B8?$format=json';

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
$profit = json_decode($result, true);




foreach($profit['value'] as $item) {
	foreach($item['RecordSet'] as $item_1) {
		$organization = $item_1['Организация_Key'];//Информация об организации
		$period_temp = $item_1['Period'];//Период начисления
		$user_id = $item_1['Сотрудник_Key'];//Физическое лицо
		$value = $item_1['СуммаВзаиморасчетов'];//Зарплата
		$today = date("Y-m-d");//Текущая дата
			
		//Приводим дататайм к дата
		$timestamp = strtotime($period_temp);
		$day = date('d', $timestamp);
		$month = date('m', $timestamp);
		$year = date('Y', $timestamp);
		$period = $year."-".$month."-01";
		
		//Получаем данные по организации
		$sql = "SELECT * FROM ".$bd_info_table_prefix."organizations WHERE 1C = '".$organization."'";
		$organization = mysqli_query($bd_info,$sql);
		
		$organization_temp = mysqli_fetch_assoc($organization);
		$organization_id = $organization_temp ['id'];//Получаем id организации в базе по базе
		
		
		//Получаем данные по сотруднику
		$sql = "SELECT * FROM ".$bd_info_table_prefix."employees WHERE 1C = '".$user_id."'";
		$employee = mysqli_query($bd_info,$sql);
		
		$employee_temp = mysqli_fetch_assoc($employee);
		$employee_id = $employee_temp ['id'];//Получаем id сотрудника в базе
		
	
	}
	
	$id = $item['Recorder'];//Получаем информаци по id записи о начислениях в 1С
   
	
	//Смотрим, если ли уже такое начисление в базе
	
	$sql = "SELECT * FROM ".$bd_info_table_prefix."values_history WHERE 1C = '".$id."'";
	$values = mysqli_query($bd_info,$sql);
	
	$rows_values = mysqli_num_rows($values);//Смотрим, есть ли начисление
	
	if ($rows_values == 0) {
		//Начисление отсутствует отсутствует, добавляем начисление
		$sql = "INSERT INTO `".$bd_info_table_prefix."values_history`(`id`, `Employee`, `Organization`, `Date`, `Period`, `Value`, `1C`) VALUES (NULL,'$employee_id','$organization_id','$today','$period','$value','$id')";
		//echo $sql;
		mysqli_query($bd_info,$sql);
	}
	else {
		//Организация присутствует, обновляем информацию
	}
}

mysqli_free_result($employee);
mysqli_free_result($organization);
mysqli_close($bd_info);
?>