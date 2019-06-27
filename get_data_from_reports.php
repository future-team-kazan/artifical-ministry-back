<?php
// БЕРЕМ ИНФОРМАЦИЮ ИЗ 1С


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

// Скачиваем страницу с каталогом файлов
$dir = 'http://95.68.242.113:8887/zupfiles';
$params = array(
    'username' => 'СавичеваЮ', // в http://localhost/post.php это будет $_POST['param1'] == '123'
    'param2' => 'abc', // в http://localhost/post.php это будет $_POST['param2'] == 'abc'
);
$result = file_get_contents($dir, false, stream_context_create(array(
    'http' => array(
        'method'  => 'GET',
        'header'  => 'Content-type: application/x-www-form-urlencoded'
    )
)));

//print_r($result);

// С помощью регулярных выражений выделяем имена файлов в формате ("salary2017-01.json) 
// Кавычка нужна чтобы избежать дублирования. Каждый файл (salary2017-01.json) в тексте встречается два раза
$reg_expression = '/"[\w\.\d-]+.json/';
preg_match_all($reg_expression, $result, $matches);

//print_r($matches);

foreach ($matches[0] as $file) {
	// В названии файла убираем знак " и получаем адрес в формате (http://95.68.242.113:8887/zupfiles/salary2017-01.json)
	$filePath = str_replace('"', "/", $file);
	$url = $dir.$filePath;
	//print_r($url);
	$params = array(
		'username' => 'СавичеваЮ', // в http://localhost/post.php это будет $_POST['param1'] == '123'
		'password' => '', // в http://localhost/post.php это будет $_POST['param2'] == 'abc'
	);
	$result = file_get_contents($url, false, stream_context_create(array(
		'http' => array(
			'method'  => 'POST',
			'header'  => 'Content-type: application/x-www-form-urlencoded'
		)
	)));
	
	$summ = 0;//Сумма всех затрат на заработную плату
	$count = 0; //Число сотрудников
	$Withsumm = 0; //Всего оплачено налогов

	// ПРОИЗВОДИМ ОБРАБОТКУ ДАННЫХ
	//echo '<pre>';
	//print_r($result); // Вывод данных
	//echo '</pre>';
	
	$fileName = str_replace('"', '', $file);//Имя файла
	
	$sql = "SELECT * FROM ".$bd_info_table_prefix."values_reports WHERE Report_name = '".$fileName."'";
	$temp_rep = mysqli_query($bd_info,$sql);
	
	$rows_temp_rep = mysqli_num_rows($temp_rep);//Смотрим, обрабатывался ли уже отчету
	
	if ($rows_temp_rep == 0) {
		//Отчет отсутствует, обрабатываем отчет
		$result = file_get_contents($url, true, $streamContext);
		$report = json_decode($result, true);
		
		foreach($report as $item) {
			/*"{
				"Период": "2017-06-01T00:00:00",
				"Организация": "МБОУ СШ №85",
				"Подразделение": "Администрация",
				"Сотрудник": "Селезнев Михаил Юрьевич",
				"Должность": "Директор",
				"НачислениеУдержание": "НДФЛ",
				"Начислено": 0,
				"Удержано": 5850
				},
				{
				"Период": "2017-06-01T00:00:00",
				"Организация": "МБОУ СШ №85",
				"Подразделение": "Администрация",
				"Сотрудник": "Селезнев Михаил Юрьевич",
				"Должность": "Директор",
				"НачислениеУдержание": "Оплата по окладу",
				"Начислено": 45000,
				"Удержано": 0
				}*/
			$period_temp = $item['Период'];
			$organization = $item['Организация'];
			$Subdivision = $item['Подразделение'];
			$Employee = $item['Сотрудник'];
			$position = $item['Должность'];
			$Withheld = $item['Удержано'];
			$value = $item['Начислено'];
			
			$today = date("Y-m-d");//Текущая дата
			
			//Приводим дататайм к дата
			$timestamp = strtotime($period_temp);
			$day = date('d', $timestamp);
			$month = date('m', $timestamp);
			$year = date('Y', $timestamp);
			$period = $year."-".$month."-01";
			
			if ($value == 0) {
				foreach($report as $item_1) {
					if ($organization = $item_1['Организация'] && $Subdivision = $item_1['Подразделение'] && $Employee = $item_1['Сотрудник']) {
						$value = $item_1['Начислено'];
					}
				}
				
				//Записываем данные по затратам на зарплату сотрудникам
				$sql = "INSERT INTO `".$bd_info_table_prefix."values_history_by_reports`(`id`, `Employee`, `Organization`, `Date`, `Period`, `Subdivision`, `Value`, `Position`, `1C`, `Withheld`) VALUES (NULL,'$Employee','$organization','$today','$period','$Subdivision','$value','$position',NULL,'$Withheld')";
				//echo $sql;
				mysqli_query($bd_info,$sql);
				
				$summ = $summ + $value + $Withheld;
				$Withsumm = $Withsumm + $Withheld;
				$count = $count + 1;
			}
		
		}
		
		$sql = "SELECT * FROM ".$bd_info_table_prefix."organizations WHERE Short_name = '".$organization."'";
		$organization = mysqli_query($bd_info,$sql);
			
		$organization_temp = mysqli_fetch_assoc($organization);
		$organization_id = $organization_temp ['id'];//Получаем id организации в базе по базе
		
		//Записываем сколько всего потрачего зарплаты
		$sql = "INSERT INTO `".$bd_info_table_prefix."values_total`(`id`, `Organization`, `Period`, `Value`) VALUES (NULL,'$organization_id','$period','$summ')";
		mysqli_query($bd_info,$sql);
		
		//Считаем среднее значение зарплаты
		$aver = ($summ - $Withsumm) / $count;
		//Считаем среднее значение налогов
		$Withsumm_aver = $Withsumm / $count;
		
		//Записываем данные по отчету
		$sql = "INSERT INTO `".$bd_info_table_prefix."values_reports`(`id`, `Organization`, `Date`, `Period`, `Value`, `total_costs`, `Report_name`, `Withheld`) VALUES (NULL,'$organization_id','$today','$period','$aver','$summ','$fileName','$Withsumm_aver')";
		mysqli_query($bd_info,$sql);
	}

}


//echo $result;

mysqli_free_result($organization);
mysqli_close($bd_info);

?>