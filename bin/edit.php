<?php

if (isset($_POST['save'])) {
	$old_ver_row = mysql_fetch_assoc(mysql_query("SELECT * FROM $pages_table WHERE name='".esc($name)."'"));
	$old_ver = $old_ver_row['body'];
	$new_ver = $_POST['body'];
	$tmp_hash = md5(time());
	$old_ver_file = $config['basepath']."/tmp/$tmp_hash/old_ver";
	$new_ver_file = $config['basepath']."/tmp/$tmp_hash/new_ver";
	mkdir($config['basepath']."/tmp/$tmp_hash");
	file_put_contents($old_ver_file, $old_ver);
	file_put_contents($new_ver_file, $new_ver);
	$diff = shell_exec("diff $old_ver_file $new_ver_file");
	$page_res = mysql_query("UPDATE $pages_table SET body='".esc($_POST['body'])."' WHERE name = '".esc($name)."'");
	$diff_res = mysql_query("INSERT INTO $diffs_table SET diff='".esc($diff)."', page_name='".esc($name)."'");
	if ($page_res && $diff_res) {
		$html_page->addBodyContent("<p><a href='{$config['baseurl']}/$name'>$name</a> has been saved.</p>");
	} else {
		error_page("Something went wrong with updating <em>$name</em>: ".mysql_error());
	}
}

elseif (isset($_POST['new'])) {
	$page_res = mysql_query("INSERT INTO $pages_table SET name = '".esc($name)."', body = '".esc($_POST['body'])."'");
	$diff_res = mysql_query("INSERT INTO $diffs_table SET page_name='".esc($name)."'");
	if ($page_res && $diff_res) {
		$html_page->addBodyContent("<p><a href='{$config['baseurl']}/$name'>$name</a> has been added.</p>");
	} else {
		error_page("Something went wrong with adding <em>$name</em>: ".mysql_error());
	}
}

else {

	$sql = "SELECT * FROM $pages_table WHERE name='".esc($name)."'";
	$res = mysql_query($sql);
	if (mysql_num_rows($res)>0) {
		$page = mysql_fetch_assoc($res);
		$page['is_new'] = false;
		$html_page->setTitle("Editing $name");
		$html_page->addBodyContent("<h2>Editing <em>$name</em></h2>");
	} else {
		$page = array();
		$page['name']   = $name;
		$page['body']   = '';
		$page['is_new'] = true;
		$html_page->setTitle("Creating $name");
		$html_page->addBodyContent("<h2>Creating <em>$name</em></h2>");
	}
	
	$html_page->addBodyContent("<form action='' method='post'>".
		"<p><textarea name='body' rows='24' cols='80' id='edit-box'>{$page['body']}</textarea></p>");
	if ($page['is_new']) {
		$html_page->addBodyContent("<p class='submit'><input type='submit' name='new' value='New &raquo;' /></p>");
	} else {
		$html_page->addBodyContent("<p class='submit'><input type='submit' name='save' value='Save &raquo;' /></p>");
	}
	$html_page->addBodyContent("</form>");

}
?>