<?php

if (!($res = @mysql_query("SELECT * FROM $pages_table WHERE LOWER(name) LIKE LOWER('%".esc($name)."%')"))) {
	error_page("Something has gone wrong with requesting that page: ".mysql_error());
}

$count = mysql_num_rows($res);
if ($count < 1) {
	error_page("We searched, and nothing like <em>$name</em> could be found.".
		"Would you like to <a href='{$config['baseurl']}/$name/edit'>create it now</a>?</p>", 404);
	
} elseif ($count > 1) { 
	$err_msg = "<p>Perhaps you were looking for one of these&hellip;</p><ul>";
	while ($place = mysql_fetch_assoc($res)) {
		$err_msg .= "<li><a href='{$config['baseurl']}/{$place['name']}'>&hellip;{$place['name']}</a></li>";
	}
	$err_msg .= "</ul>";
	error_page($err_msg);

} elseif ($count == 1) {
	$page = mysql_fetch_assoc($res);
	$html_page->setTitle($page['name']." &laquo; ".$config['sitename']);
	if ($config['sitename']!=$name) {
		$html_page->addBodyContent("<h2>{$page['name']}</h2>\n");
	}
	$html_page->addBodyContent(wikiformat($page['body']).
		"<ul>\n".
		"  <li><a href='{$config['baseurl']}/$name/diff'>Page Revision History</a></li>\n".
		"  <li><a href='{$config['baseurl']}/$name/edit'>Edit Page</a></li>\n".
		"  <li><a href='{$config['baseurl']}/upload'>Upload File</a></li>\n".
		"  <li><a href='{$config['baseurl']}/files'>View Files</a></li>\n".
		"</ul>"
	);
	
}

?>