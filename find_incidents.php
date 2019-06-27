<?php
// В ЭТОМ МОДУЛЕ МЫ ВЫГРУЖАЕМ ИНФОРМАЦИЮ О СРЕДНЕЙ ЗАРПЛАТЕ ПО ДОЛЖНОСТЯМ


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

//Получаем последние данные по заработной плате исходя из начислений
					
//Получаем последний период начисления зарплаты
$sql = "SELECT * FROM ".$bd_info_table_prefix."values_history WHERE Period = (SELECT MAX(Period) FROM ".$bd_info_table_prefix."values_history) ORDER BY Period DESC LIMIT 1";
$last_values = mysqli_query($bd_info,$sql);
$last_values_temp = mysqli_fetch_assoc($last_values);
$last_value_date = $last_values_temp ['Date'];

//Берем последний месяц получения полной зарплаты

$timestamp = strtotime($last_value_date);
$day = date('d', $timestamp);
$month = date('m', $timestamp);
$year = date('Y', $timestamp);

$period = $year."-".$month."-01";

for ($r = 0; $r < 20; $r++) { //Берем Полсдение 2 дат
	
	if ($month == 0) {
		$month = 12;
		$year = $year - 1;					
	}
	
	$period = $year."-".$month."-01";
	
	$chartData[$r][0] = array(
	"date" => $period
	);
	
	//Получаем данные по должностям
	$sql = "SELECT * FROM ".$bd_info_table_prefix."positions";
	$positions = mysqli_query($bd_info,$sql);

	$rows_positions = mysqli_num_rows($positions); // Подсчитываем количество должностей

	for ($i = 0; $i < $rows_positions; $i++) 
	{

			$positions_temp = mysqli_fetch_assoc($positions);
			$positions_id = $positions_temp ['id'];//Получаем id должности
			$positions_name = $positions_temp ['Name'];//Получаем название должности
			
			$pos_value[][] = 0;
			
			//Получаем список сотрудников по данной должности
			$sql = "SELECT * FROM ".$bd_info_table_prefix."employees WHERE Position = '".$positions_id."'";
			$employees_list = mysqli_query($bd_info,$sql);

			$rows_employees = mysqli_num_rows($employees_list); // Подсчитываем количество сотрудников

			for ($j = 0; $j < $rows_employees; $j++) 
			{
				$employe_temp = mysqli_fetch_assoc($employees_list);
				$employee_id = $employe_temp ['id'];//Получаем id сотрудника
				$positions_organization = $positions_temp ['Organization'];//Получаем id организации
				
				
				
				//Получаем данные за текущий период
				$sql = "SELECT * FROM ".$bd_info_table_prefix."values_history WHERE Employee = '".$employee_id."' AND Period = '".$period."'";
				
				$values_by_history = mysqli_query($bd_info,$sql);
				$rows_values_by_history = mysqli_num_rows($values_by_history);
				
				$employee_value_by_history = 0;
				for ($k = 0; $k < $rows_values_by_history; $k++) 
				{
					$values_by_history_temp = mysqli_fetch_assoc($values_by_history);
					$temp_value_by_history = $values_by_history_temp ['Value'];//Получаем начисляемую сумму
					$employee_value_by_history = $employee_value_by_history + $temp_value_by_history;
				}	

				//Проверяем заработную плату на наличие нарушений
				
				//Получаем список метрик
				$sql = "SELECT * FROM ".$bd_info_table_prefix."metrics WHERE Designation = 'profit'";
				
				$mentrics = mysqli_query($bd_info,$sql);
				$rows_mentrics = mysqli_num_rows($mentrics);
				
				for ($e = 0; $e < $rows_mentrics; $e++ ){
					$mentrics_temp = mysqli_fetch_assoc($mentrics);
					$mentric_max = $mentrics_temp ['Value_max'];//Получаем максимальное значение метрики
					$mentric_min= $mentrics_temp ['Value_min'];//Получаем минимальное значение метрики
					$mentric_name= $mentrics_temp ['Name'];//Получаем наименование инцедента
					/*echo $mentric_max;
					echo "-";
					echo $mentric_min;
					echo "<br>";*/
					
					
					$sql = "INSERT INTO `".$bd_info_table_prefix."incidents`(`id`, `Date`, `Place`, `Organization`, `Responsible_for_occurrence`, `Responsible_for_occurrence_id`, `Responsible_for_the_decision`, `Responsible_for_the_decision_id`, `Description`) VALUES (NULL,NULL,NULL,'$positions_organization',NULL,NULL,NULL,NULL,'$mentric_name')";
					
					if ($employee_value_by_history < $mentric_min && $mentric_max = NULL) {
						//Значение показателя меньше минимального значения
						mysqli_query($bd_info,$sql);
					}
					
					if ($employee_value_by_history > $mentric_max && $mentric_min = NULL) {
						//Значение показателя больше максимального значения
						mysqli_query($bd_info,$sql);
					}
					
					if ($employee_value_by_history > $mentric_min && $employee_value_by_history < $mentric_max) {
						//Значение показателя лежит в контролируемых пределах
						mysqli_query($bd_info,$sql);
					}
					
				}
				
				
				
			}
	}
}

mysqli_free_result($employees_list);
mysqli_free_result($positions);
mysqli_close($bd_info);
?>