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

//Получаем данные по должностям
$sql = "SELECT * FROM ".$bd_info_table_prefix."positions";
$positions = mysqli_query($bd_info,$sql);

$rows_positions = mysqli_num_rows($positions); // Подсчитываем количество должностей

for ($i = 0; $i < $rows_positions; $i++) 
{
		$positions_temp = mysqli_fetch_assoc($positions);
		$positions_id = $positions_temp ['id'];//Получаем id должности
		$positions_name = $positions_temp ['Name'];//Получаем название должности
		
		$pos_value = 0;
		
		//Получаем список сотрудников по данной должности
		$sql = "SELECT * FROM ".$bd_info_table_prefix."employees WHERE Position = '".$positions_id."'";
		$employees_list = mysqli_query($bd_info,$sql);

		$rows_employees = mysqli_num_rows($employees_list); // Подсчитываем количество сотрудников

	
		for ($j = 0; $j < $rows_employees; $j++) 
		{
			$employe_temp = mysqli_fetch_assoc($employees_list);
			$employee_id = $employe_temp ['id'];//Получаем id сотрудника
			
			//Получаем последние данные по заработной плате исходя из начислений
				
			//Получаем последнее число начисления зарплаты
			$sql = "SELECT * FROM ".$bd_info_table_prefix."values_history WHERE Employee = '".$employee_id."' ORDER BY Period DESC LIMIT 1";
				

			$last_values = mysqli_query($bd_info,$sql);
			$last_values_temp = mysqli_fetch_assoc($last_values);
			$last_value_date = $last_values_temp ['Date'];
			$last_value_period = $last_values_temp ['Period'];
			
			if (strtotime($last_value_period) < strtotime($last_value_date)) {
				$dateperiod = 1;
				$last_value_date = $last_value_period;
			}
			

			
			//Берем последний месяц получения полной зарплаты
			
			$timestamp = strtotime($last_value_date);
			$day = date('d', $timestamp);
			$month = date('m', $timestamp);
			$year = date('Y', $timestamp);
			
			$day_by_values_history = $day;
			$last_month = $month-1;
			if ($dateperiod == 1) {
				$last_month = $month;
			}
			$last_period = $year."-".$last_month."-01";
			$now_period = $year."-".$month."-01";

						
			if ((15 < $day_by_values_history) && ($day_by_values_history < 25)) {
				//Получаем данные за текущий период
				$sql = "SELECT * FROM ".$bd_info_table_prefix."values_history WHERE Employee = '".$employee_id."' AND Period = '".$now_period."'";
				if ($now_period > $date) {$date = $now_period;}
			}
			
			else {
				//Получаем данные за предыдущий период
				$sql = "SELECT * FROM ".$bd_info_table_prefix."values_history WHERE Employee = '".$employee_id."' AND Period = '".$last_period."'";
				if ($last_period > $date) {$date = $last_period;}
			}
			
			if ($dateperiod == 1) {
				$sql = "SELECT * FROM ".$bd_info_table_prefix."values_history WHERE Employee = '".$employee_id."' AND Period = '".$last_value_date."'";
				if ($last_value_date > $date) {$date = $last_value_date;}
			}
			
			$timestamp1 = strtotime($date);
			$year_1 = date('Y', $timestamp1);
			
			
			$employee_value_by_history = 0;
			$period_by_history_start = 0;
			
			$values_by_history = mysqli_query($bd_info,$sql);
			$rows_values_by_history = mysqli_num_rows($values_by_history);
			
			
			for ($k = 0; $k < $rows_values_by_history; $k++) 
			{
				$values_by_history_temp = mysqli_fetch_assoc($values_by_history);
				$temp_value_by_history = $values_by_history_temp ['Value'];//Получаем начисляемую сумму
				$employee_value_by_history = $employee_value_by_history + $temp_value_by_history;
			}
			
			//Подсчитываем сумму всех начислений по должностям
			$pos_value = $pos_value + $employee_value_by_history;
		}


//Вычисляем среднее значение зарплаты по должности
if ($rows_employees != 0) {$pos_value = $pos_value / $rows_employees;}
else {
	//echo "Error rows_employees";
	$pos_value = 0;
}

$legend[$i] = array(
"name" => $positions_name,
"field" => $positions_id
);

$name[$i] = $positions_id;
$value[$i] = $pos_value;

/*$positions_data_temp1 = array(
"name" => $positions_name,
"value" => $pos_value
);

$positions_data_temp[$i] = array(
"id" => $positions_id,
"positions_data" => $positions_data_temp1
);*/

}


for ($r = -1; $r < $rows_positions; $r++) {
	if ($r == -1) {$chartData[$r+1] = array("year" => $year_1);}
	else {$chartData[$r+1] = array($name[$r] => $value[$r]);}
}



$chart = array(
"legend" => $legend,
"chartData" => $chartData
);

echo json_encode($chart, JSON_UNESCAPED_UNICODE);

mysqli_free_result($employees_list);
mysqli_free_result($positions);
mysqli_close($bd_info);
?>