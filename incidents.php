<?php
// В ЭТОМ МОДУЛЕ МЫ ВЫГРУЖАЕМ ИНФОРМАЦИЮ О ИНЦИДЕНТАХ


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

//Получаем данные по инцидентам
$sql = "SELECT * FROM ".$bd_info_table_prefix."incidents";
$incidents = mysqli_query($bd_info,$sql);

$rows_incidents = mysqli_num_rows($incidents); // Подсчитываем количество школ

for ($i = 0; $i < $rows_incidents; $i++) 
{
		$incidents_temp = mysqli_fetch_assoc($incidents);
		$incident_id = $incidents_temp ['id'];//Получаем id инцидента]
		$incident_date = $incidents_temp ['Date']; //Дата возникновения инцидента
		$incident_Place = $incidents_temp ['Place']; //Место возникновения
		$incident_Organization = $incidents_temp ['Organization']; //Id связанной с инцидентом подведомственной организации
		$incident_Responsible_for_occurrence  = $incidents_temp ['Responsible_for_occurrence']; //Ответственный за возникновение
		$incident_Responsible_for_occurrence_id = $incidents_temp ['Responsible_for_occurrence_id']; //Id ответственного за возникновение
		$incident_Responsible_for_the_decision = $incidents_temp ['Responsible_for_the_decision']; //Ответственный за решение
		$incident_Responsible_for_the_decision_id = $incidents_temp ['Responsible_for_the_decision_id']; //Id ответственного за решение
		$incident_Description = $incidents_temp ['Description']; //Описание сущности инцидента
			
		

		
$incident_data_temp1 = array(
"id" => $incident_id,
"date" => $incident_date,
"place" => $incident_Place,
"organization" => $incident_Organization,
"responsible_for_occurrence" => $incident_Responsible_for_occurrence,
"responsible_for_occurrence_id" => $incident_Responsible_for_occurrence_id,
"responsible_for_the_decision" => $incident_Responsible_for_the_decision,
"responsible_for_the_decision_id" => $incident_Responsible_for_the_decision_id,
"description" => $incident_Description,
);


$incident_data_temp[$i] = array(
"id" => $incident_id,
"incident_data" => $incident_data_temp1
);
}

echo json_encode($incident_data_temp, JSON_UNESCAPED_UNICODE);

mysqli_free_result($incidents);
mysqli_close($bd_info);
?>