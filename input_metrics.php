<?php
// В ЭТОМ МОДУЛЕ МЫ ВЫГРУЖАЕМ ИНФОРМАЦИЮ О СРЕДНЕЙ ЗАРАБОТНОЙ ПЛАТЕ ПО ОТРАСЛИ


header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS'); 
header('Access-Control-Allow-Headers: X-Requested-With, Content-type');

$Value = 0;

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

//Заработная плата меньше МРОТ
$profit_min = 11000;
$name = "profit";
$designation = "Нарушение ТК, зп меньше МРОТ";
$sql = "INSERT INTO `".$bd_info_table_prefix."metrics`(`id`, `Name`, `Designation`, `Value_max`, `Value_min`) VALUES (NULL,'$designation','$name',NULL,'$profit_min')";
mysqli_query($bd_info,$sql);
		
//Заработная плата меньше установленного губернатором уровня
$profit_min = 11000;
$profit_max = 30000;
$name = "profit";
$designation = "Нарушение указа Губернатора №123 от 01.02.03, зп меньше установленного уровня";
$sql = "INSERT INTO `".$bd_info_table_prefix."metrics`(`id`, `Name`, `Designation`, `Value_max`, `Value_min`) VALUES (NULL,'$designation','$name','$profit_max','$profit_min')";
mysqli_query($bd_info,$sql);

//Растрата
$profit_max = 200000;
$name = "profit";
$designation = "Нарушение ст. 160 УК РФ, растрата.";
$sql = "INSERT INTO `".$bd_info_table_prefix."metrics`(`id`, `Name`, `Designation`, `Value_max`, `Value_min`) VALUES (NULL,'$designation','$name','$profit_max',NULL)";
mysqli_query($bd_info,$sql);

mysqli_close($bd_info);
?>