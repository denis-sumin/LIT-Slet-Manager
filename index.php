<?php
session_name("SletManager");
session_start();

function print_header( $year ) {
	echo '
	<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
	<html>
		<head>
			<title>Слет&mdash;'.$year.'</title>
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
			<link type="text/css" rel="stylesheet" media="all" href="style/style.css">
			<link type="text/css" rel="stylesheet" media="print" href="style/print.css">

			<script language="javascript" type="text/javascript">
				function getHTTPObject(){
					if (window.ActiveXObject) return new ActiveXObject("Microsoft.XMLHTTP");
					else if (window.XMLHttpRequest) return new XMLHttpRequest();
					else {
						alert("Your browser does not support AJAX.");
						return null;
					}
				}
				function participantlist() {
					document.getElementById(\'main\').style.right = "30%";
				}
			</script>

		</head>
		<body>

		<div id="header">&nbsp;</div>
		<div id="indexlink"><a href="'.$_SERVER["PHP_SELF"].'">Слет&mdash;'.$year.'</a></div>';
	return TRUE;
}

function print_footer() {
	echo '

		</body>
	</html>
	';

	return TRUE;
}

function menu ($curAction) {
	global $modules, $user;
	echo '<ul>';
	foreach ($modules as $module=>$m) {
		if ( ($module !== 'welcome') && check_user_access ($module, $user)) {
			if ( isset ($modules[$module]['name']) ) echo '<li class="modulename">'.$modules[$module]['name']."</li>\n";
			echo "<ul>\n";
			foreach ($modules[$module]['action'] as $key=>$action) {
				if (isset($modules[$module]['menu'][$key])) {
					echo '<li>';
					if ($action == $curAction) echo '<i class="cur">';
					echo '<a class="menu" href="'.$_SERVER["SCRIPT_NAME"].'?action='.$action.'">'.$modules[$module]['menu'][$key].'</a>';
					if ($action == $curAction) echo '</i>';
					echo '</li>'."\n";
				}
			}
			echo "</ul>\n";
		}
	}
	echo '</ul>';
	return TRUE;
}

function title ($curAction) {
	global $modules, $user;

	foreach ($modules as $module=>$m) {
		foreach ($modules[$module]['action'] as $key=>$action) {
			if ($action == $curAction) {
				if (isset($modules[$module]['title'][$key])) {
					$title = $modules[$module]['title'][$key];
					return $title;
				}
			}
		}
	}
	return '';
}

function find_module ($action) {
	global $modules;
	foreach ($modules as $module=>$m) {
		foreach ($modules[$module]['action'] as $key=>$value) {
			if ($action === $value) return $module;
		}
	}
	report_error("Не найден модуль, реализующий запрашиваемое действие");
	return FALSE;
}

function exec_module ($module) {
	global $action, $user;
	if ($module === FALSE) return FALSE;

	if (! check_user_access ($module, $user) ) {
		show_auth ($action);
		report_error ("У Вас нет права использования этого модуля");
		return FALSE;
	}
	else {
		$func = 'show_'.$module;
		$func($action);
		return TRUE;
	}
}

function check_user_access ( $module ) {
	global $modules, $user;
	foreach ($user['group'] as $ukey=>$ugroup) {
		foreach ($modules[$module]['groups'] as $mkey=>$mgroup) {
			if ( $ugroup == $mgroup ) return TRUE;
		}
	}
	return FALSE;
}


//////////////////////////////////////////////////////////////////////////////

include ("./config.php");
include ("./functions.php");

$participantlist = FALSE;

print_header( $year );

if (!mysql_connect($mysql_server,$mysql_user,$mysql_password))
	report_error ('Не удалось подключиться к серверу баз данных.');

if (!mysql_select_db($mysql_db))
	report_error ('Не удалось подключиться к базе данных.');

if (empty($_GET['action'])) $action = 'welcome';
	else $action = $_GET['action'];

// Загружаем данные о пользователе
if ( isset ($_SESSION['userid']) ) $user = get_participant_info ($_SESSION['userid']);
else $user['group'][] = 'guest';

// Подключаем модули
define ("RequestModule", 'core');
$modpath = "./modules/";
$path = opendir($modpath);
while (($file = readdir($path))!== false)
    {
	$files[] = $file;
    }
closedir($path);
sort ($files);
foreach ($files as $file) {
	if ($file == '.' || $file == '..') continue;
	$modfile = file($modpath.$file);
	if ($modfile[1] != "// ItIsSletManagerModule\n") continue;
	include ($modpath.$file);
}


echo '<div id="menu">';
menu ($action);
echo '</div>';

echo '<div id="main">';
if ( ( $title=title ($action) ) != '' ) echo '
<script language="javascript" type="text/javascript">document.title = document.title+\': '.$title.'\';</script>
<h1>'.$title.'</h1>';
exec_module (find_module ($action) );
echo '</div>';

print_errors();

if ($participantlist) {
echo '
<script language="javascript" type="text/javascript">
	participantlist();
</script>
<div id="participantlist"><iframe src="./participantlist.php" frameborder="0" width="100%" height="100%"></iframe></div>';
}

print_footer();
?>
