<html>
<head>
<?php
//Чтение адреса сервера
$file_server = fopen("BD_config/server.conf", "r");
while (!feof($file_server)) 
	{
$line = fgets($file_server);
$server = $line;
	}

//Чтение названия БД
$file_BD1 = fopen("BD_config/BD.conf", "r");
while (!feof($file_BD1)) 
	{
$line = fgets($file_BD1);
$BD = $line;
	}

$file_prefix_BD = fopen("BD_config/prefix_BD.conf", "r");
while (!feof($file_prefix_BD)) 
	{
$line = fgets($file_prefix_BD);
$prefix_BD1 = $line;
	}	
	
//Чтение логина БД	
$file_login = fopen("BD_config/login.conf", "r");
while (!feof($file_login)) 
	{
$line = fgets($file_login);
$BDServerLogin = $line;
	}

//Чтения пароля к БД
$file_password = fopen("BD_config/password.conf", "r");
while (!feof($file_password)) 
	{
$line = fgets($file_password);
$BDServerPassword = $line;
	}

//Подключение к БД
$mysqli = new mysqli("$server", "$BDServerLogin", "$BDServerPassword", "$BD");
if ($mysqli->connect_error) 
{
   die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

$mysqli->set_charset("utf8");

//Организации

$sql = "CREATE TABLE ".$prefix_BD."organizations 
(
id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
Name VARCHAR(100),
OGRN INT(15),
VAT INT(15),
KPP INT(15),
Address VARCHAR(200),
Director INT(11),
Date_on DATE,
Date_off DATE,
1C VARCHAR(100)
)";
 
if (mysqli_query($mysqli,$sql))
{
echo "Table organizations created successfully. <br>";
}
else
{
echo "Error creating table: organizations. " . mysqli_error($mysqli)."<br>";
}

//Работники

$sql = "CREATE TABLE ".$prefix_BD."employees 
(
id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
Name VARCHAR(30),
Surname VARCHAR(30),
Last_name VARCHAR(30),
Date_on DATE,
Date_off DATE,
Organization INT(11),
Position INT(11),
1C VARCHAR(100),
Tel VARCHAR(30),
Email VARCHAR(30),
Address VARCHAR(200)
)";
 
if (mysqli_query($mysqli,$sql))
{
echo "Table employees created successfully. <br>";
}
else
{
echo "Error creating table: employees. " . mysqli_error($mysqli)."<br>";
}

//Справочник должностей

$sql = "CREATE TABLE ".$prefix_BD."positions
(
id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
Name VARCHAR(100),
Name1 VARCHAR(100),
Name2 VARCHAR(100),
Name3 VARCHAR(100),
Description VARCHAR(300)
)";

if (mysqli_query($mysqli,$sql))
{
echo "Table positions created successfully.<br> ";
}
else
{
echo "Error creating table: positions. " . mysqli_error($mysqli)."<br>";
}

//Начисления на заработную плату

$sql = "CREATE TABLE ".$prefix_BD."values_history
(
id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
Employee INT(11),
Organization INT(11),
Date DATE,
Period DATE,
Value INT(11)
)";
 
if (mysqli_query($mysqli,$sql))
{
echo "Table values created successfully.<br> ";
}
else
{
echo "Error creating table: values. " . mysqli_error($mysqli)."<br>";
}

//Всего потрачено зарплаты учреждением

$sql = "CREATE TABLE ".$prefix_BD."values_total
(
id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
Employee INT(11),
Organization INT(11),
Period DATE,
Value INT(11)
)";
 
if (mysqli_query($mysqli,$sql))
{
echo "Table values_total created successfully.<br> ";
}
else
{
echo "Error creating table: values_total. " . mysqli_error($mysqli)."<br>";
}

//Отчеты по заработной плате

$sql = "CREATE TABLE ".$prefix_BD."values_reports 
(
id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
Organization INT(11),
Date DATE,
Period DATE,
Value INT(11)
)";
 
if (mysqli_query($mysqli,$sql))
{
echo "Table values_reports created successfully.<br> ";
}
else
{
echo "Error creating table: values_reports. " . mysqli_error($mysqli)."<br>";
}

//Информация по зарплате по должностям
$sql = "CREATE TABLE ".$prefix_BD."info_values_positions 
(
id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
Position INT(11), 
Value INT(11), 
Date DATE
)";
 
if (mysqli_query($mysqli,$sql))
{
echo "Table info_values_positions created successfully.<br> ";
}
else
{
echo "Error creating table: info_values_positions. " . mysqli_error($mysqli)."<br>";
}

//Информация по зарплате в отрасли
$sql = "CREATE TABLE ".$prefix_BD."info_values_education 
(
id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
Value INT(11), 
Date DATE
)";
 
if (mysqli_query($mysqli,$sql))
{
echo "Table info_values_education created successfully.<br> ";
}
else
{
echo "Error creating table: info_values_education. " . mysqli_error($mysqli)."<br>";
}

//Таблица-очередь писем
$sql = "CREATE TABLE ".$prefix_BD."letter_queue 
(
id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
mail VARCHAR(300), 
Address VARCHAR(30),
Theme VARCHAR(30),
Status INT(2),
Document1 VARCHAR(100),
Document2 VARCHAR(100),
Document3 VARCHAR(100),
Document4 VARCHAR(100)
)";
 
if (mysqli_query($mysqli,$sql))
{
echo "Table letter_queue created successfully.<br> ";
}
else
{
echo "Error creating table: letter_queue. " . mysqli_error($mysqli)."<br>";
}

//Шаблоны писем
$sql = "CREATE TABLE ".$prefix_BD."letter_templates 
(
id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
Name VARCHAR(30), 
Text VARCHAR(300)
)";
 
if (mysqli_query($mysqli,$sql))
{
echo "Table letter_templates created successfully.<br> ";
}
else
{
echo "Error creating table: letter_templates. " . mysqli_error($mysqli)."<br>";
}

//Метрики
$sql = "CREATE TABLE ".$prefix_BD."metrics 
(
id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
Name VARCHAR(100),
Designation VARCHAR(30), 
Value_max INT(11),
Value_min INT(11)
)";
 
if (mysqli_query($mysqli,$sql))
{
echo "Table metrics created successfully.<br> ";
}
else
{
echo "Error creating table: metrics. " . mysqli_error($mysqli)."<br>";
}

//Инциденты
$sql = "CREATE TABLE ".$prefix_BD."incidents 
(
id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
Date DATE, 
Place VARCHAR(200),
Organization INT(11),
Responsible_for_occurrence VARCHAR(100),
Responsible_for_occurrence_id INT(11),
Responsible_for_the_decision VARCHAR(100),
Responsible_for_the_decision_id INT(11),
Description VARCHAR(300)
)";


mysqli_close($mysqli);
?>
</head>
</html>