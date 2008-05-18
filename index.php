<?php
require_once 'config.php';
date_default_timezone_set($config['timezone']);
set_include_path(get_include_path().PATH_SEPARATOR.$config['pearpath']);

require_once 'lib.php';
require_once 'HTML/Page2.php';
require_once 'HTML/CSS.php';

$html_page = new HTML_Page2();
$html_page->setTitle($config['sitename']);
$html_page->addBodyContent("<h1><a href='{$config['baseurl']}' title='Homepage'>".$config['sitename']."</a></h1>");

$css = new HTML_CSS();
$html_page->addStyleDeclaration($css);
//$page->addStyleSheet('http://www.w3.org/StyleSheets/Core/Traditional');
$html_page->addStyleSheet('/css/screen_default.css','text/css','screen');
$html_page->addStyleSheet('/css/screen_custom.css','text/css','screen');

if (!@mysql_connect($config['db']['host'], $config['db']['user'], $config['db']['password'])) 
	error_page("Unable to connect to database: ".mysql_error());
if (!mysql_select_db($config['db']['dbname'])) 
	error_page("ERROR: Unable to select database: ".mysql_error());
$pages_table = $config['db']['tableprefix'].'pages';
$diffs_table = $config['db']['tableprefix'].'diffs';

require_once 'Text/PathNavigator.php';
$path = new Text_PathNavigator($_SERVER['REQUEST_URI']);
$name = ($path->get(0)!='') ? urldecode($path->get(0)) : $config['sitename'];
$action = ($path->get(1)!='') ? $path->get(1) : 'view';

if (file_exists("bin/{$action}.php")) {
	require_once "bin/{$action}.php";
} else {
	error_page("The specified action ('$action') is not valid.");
}

$html_page->display();

?>