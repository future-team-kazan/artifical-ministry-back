<?php
// В ЭТОМ МОДУЛЕ МЫ ВЫГРУЖАЕМ ИНФОРМАЦИЮ ПО ЗАТРАТАМ НА ЗАРАБОТНУЮ ПЛАТУ В ОТРАСЛИ


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

//Получаем данные по школам
$sql = "SELECT * FROM ".$bd_info_table_prefix."organizations";
$schools = mysqli_query($bd_info,$sql);

$rows_schools = mysqli_num_rows($schools); // Подсчитываем количество школ

$value = 0;

for ($i = 0; $i < $rows_schools; $i++) 
{
		$schools_temp = mysqli_fetch_assoc($schools);
		$school_id = $schools_temp ['id'];//Получаем id школы по базе
			
		//Получаем последнюю информацию по затратам на заработную Учреждением
		$sql = "SELECT * FROM ".$bd_info_table_prefix."values_total WHERE Organization = '".$school_id."' ORDER BY Period DESC LIMIT 1";
		if (mysqli_query($bd_info,$sql))
		{
			//Если данные получены, то записываем их значения в тепмовые переменные
			$temp_value1 = mysqli_query($bd_info,$sql);
			$temp_value = mysqli_fetch_assoc($temp_value1);
			$temp_calc = $temp_value['Value']; //Заносим данные по заратам на зарплату в темповую переменную
			
		}	
		
		$value = $value + $temp_calc;//Суммируем затраты по школам на зарплату
			
}

$industry_data_temp = array(
"value" => $value
);

echo json_encode($industry_data_temp, JSON_UNESCAPED_UNICODE);

mysqli_free_result($schools);
mysqli_close($bd_info);
?>