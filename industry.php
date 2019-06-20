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

//Получаем данные по школам
$sql = "SELECT * FROM ".$bd_info_table_prefix."organizations";
$schools = mysqli_query($bd_info,$sql);

$rows_schools = mysqli_num_rows($schools); // Подсчитываем количество школ

for ($i = 0; $i < $rows_schools; $i++) 
{
		$schools_temp = mysqli_fetch_assoc($schools);
		$school_id = $schools_temp ['id'];//Получаем id школы по базе
		$school_name = $schools_temp ['Name']; //Получаем название
			
		//Получаем последнюю информацию по средней заработной плате из отчета
		$sql = "SELECT * FROM ".$bd_info_table_prefix."values_reports WHERE Organization = '".$school_id."' ORDER BY Period DESC LIMIT 1";
		if (mysqli_query($bd_info,$sql))
		{
			//Если данные получены, то записываем их значения в тепмовые переменные
			$average_value_by_report_temp1 = mysqli_query($bd_info,$sql);
			$average_value_by_report_temp = mysqli_fetch_assoc($average_value_by_report_temp1);
			$average_value_by_report = $average_value_by_report_temp['Value'];
			$period_by_report = $average_value_by_report_temp['Period']; //Записываем данные, за какой период времени у нас отчет
		}	
		
			
		//Получаем последние данные по заработной плате исходя из начислений
		//Берем данные по всем сотрудникам
		$sql = "SELECT * FROM ".$bd_info_table_prefix."employees WHERE Organization = '".$school_id."'";
		$employees = mysqli_query($bd_info,$sql);

		$rows_employees = mysqli_num_rows($employees); // Подсчитываем количество сотрудников
		$employee_value_by_history_average = 0; //Среднее значение по зараплате по начислениям
		for ($j = 0; $j < $rows_employees; $j++) 
		{
			$employees_temp = mysqli_fetch_assoc($employees);
			$employee_id = $employees_temp ['id'];//Получаем id сотрудника по базе
		
			
			//Получаем последнее число начисления зарплаты
			$sql = "SELECT * FROM ".$bd_info_table_prefix."values_history WHERE Employee = '".$employee_id."' ORDER BY Period DESC LIMIT 1";
			$last_values = mysqli_query($bd_info,$sql);
			$last_values_temp = mysqli_fetch_assoc($last_values);
			$last_value_date = $last_values_temp ['Date'];
			
			//Берем последний месяц получения полной зарплаты
			
			$timestamp = strtotime($last_value_date);
			$day = date('d', $timestamp);
			$month = date('m', $timestamp);
			$year = date('Y', $timestamp);
			//$date_by_values_history = getdate($last_value_date);
			//$day_by_values_history = $date_by_values_history['mday'];
			
			$day_by_values_history = $day;
			$last_month = $month-1;
			$last_period = $year."-".$last_month."-01";
			$now_period = $year."-".$month."-01";
			
						
			if ((15 < $day_by_values_history) && ($day_by_values_history < 25)) {
				//Получаем данные за текущий период
				$sql = "SELECT * FROM ".$bd_info_table_prefix."values_history WHERE Employee = '".$employee_id."' AND Period = '".$now_period."'";
			}
			
			else {
				//Получаем данные за предыдущий период
				$sql = "SELECT * FROM ".$bd_info_table_prefix."values_history WHERE Employee = '".$employee_id."' AND Period = '".$last_period."'";
			}
			
			$employee_value_by_history = 0;
			$period_by_history_start = 0;
			
			$values_by_history = mysqli_query($bd_info,$sql);
			$rows_values_by_history = mysqli_num_rows($values_by_history);
			
			
			
			for ($k = 0; $k < $rows_values_by_history; $k++) 
			{
				$values_by_history_temp = mysqli_fetch_assoc($values_by_history);
				$temp_value_by_history = $values_by_history_temp ['Value'];//Получаем начисляемую сумму
				$employee_value_by_history = $employee_value_by_history + $temp_value_by_history;
				
				
				//Записываем данные, за какой период времени у нас начисления. Берем самую последнюю дату
				if (strtotime($values_by_history_temp ['Period']) > $period_by_history_start) {
					$period_by_history = $values_by_history_temp ['Period'];
				}
				
			}
			
			
			$employee_value_by_history_average = $employee_value_by_history_average + $employee_value_by_history; //Делаем сумму по зарплате в последний расчетный месяц по всем работникам
		}

		if ($rows_employees != null) {
			$employee_value_by_history_average = $employee_value_by_history_average / $rows_employees; //Вычисляем среднее арифметическое по зарплате среди всех работников
		}
		else {
			$employee_value_by_history_average = 0;
		}
		
		//Выбираем наиболее актуальные данные по средней зарплате из отчета и начислений
		
		
		if (strtotime($period_by_report) >= strtotime($period_by_history)) {
			//Отчет актуальнее или такой же
			$value = $average_value_by_report;
		}
		else {
			$value = $employee_value_by_history_average;
		}
		
$value_industry = $value_industry + $value;  //Сумма всех средних зарплат по всем школам
}

$value_industry = $value_industry / $rows_schools; //Вычисление средней зарплаты по отрасли

$schools_data_temp = array(
"value" => $value_industry
);

echo json_encode($schools_data_temp, JSON_UNESCAPED_UNICODE);

mysqli_free_result($employees);
mysqli_free_result($schools);
mysqli_close($bd_info);
?>