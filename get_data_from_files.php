<?php
// БЕРЕМ ИНФОРМАЦИЮ ИЗ 1С


header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS'); 
header('Access-Control-Allow-Headers: X-Requested-With, Content-type');

// Скачиваем страницу с каталогом файлов
$dir = 'http://95.68.242.113:8887/zupfiles';

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

	$result = file_get_contents($url, false, stream_context_create(array(
		'http' => array(
			'method'  => 'POST',
			'header'  => 'Content-type: application/x-www-form-urlencoded'
		)
	)));

	// ПРОИЗВОДИМ ОБРАБОТКУ ДАННЫХ
	echo '<pre>';
	print_r($result); // Вывод данных
	echo '</pre>';

}


//echo $result;



?>