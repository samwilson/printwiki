<?php

if ($config['sitename']!=$name) {
	$html_page->addBodyContent("<h2>$name</h2>\n");
}

// $total_revs is the total count of revisions for this page.
$total_revs = mysql_fetch_assoc(mysql_query("SELECT COUNT(*) AS total FROM $diffs_table WHERE page_name='".esc($name)."' ORDER BY `time` DESC"));
$total_revs = $total_revs['total'];

// Give up now if there's only one revision.
if ($total_revs==1) {
	$html_page->addBodyContent("<p>The current version is the first.</p>");
} else {

	// $rev_num is the desired revision number, counting forwards from the first
	// version which is 1.  Default to the previous revision.
	$rev_num = ($path->get(2)!='' && $path->get(2)<=$total_revs && $path->get(2)>0) ? $path->get(2) : $total_revs-1;
	if ($rev_num==0) $rev_num = 1;
	
	// Navigation bar.
	$css->parseString("p.diff-nav {text-align:center; border-bottom:1px solid black}");
	$html_page->addBodyContent("<p class='diff-nav'><strong>");
	if (($rev_num-1)>0) {
		$html_page->addBodyContent("\t<a href='{$config['baseurl']}/$name/diff/".($rev_num-1)."'>&laquo;</a> | ");
	}
	if ($rev_num==$total_revs) {
		$html_page->addBodyContent("\tCurrent Revision | ");
	} else {
		$html_page->addBodyContent("\tRevision $rev_num of $total_revs | ");
	}
	if (($rev_num+1)<=$total_revs) {
		$html_page->addBodyContent("\t<a href='{$config['baseurl']}/$name/diff/".($rev_num+1)."'>&raquo;</a>");
	}
	$html_page->addBodyContent("\t</strong><br />");
	
	// Set up temporary directory.
	$tmp_hash = md5(time());
	$this_ver_file = $config['basepath']."/tmp/$tmp_hash/this_ver";
	$patch_file = $config['basepath']."/tmp/$tmp_hash/patch";
	mkdir($config['basepath']."/tmp/$tmp_hash");
	
	// Write initial 'current version' to file.
	$this_ver_row = mysql_fetch_assoc(mysql_query("SELECT * FROM $pages_table WHERE name='".esc($name)."'"));
	$this_ver = $this_ver_row['body'];
	file_put_contents($this_ver_file, $this_ver);
	
	// Set current revision's date and time.
	$rev_date = mysql_fetch_assoc(mysql_query("SELECT * FROM $diffs_table WHERE page_name='".esc($name)."' ORDER BY `time` DESC LIMIT 1"));
	$rev_date = $rev_date['time'];

	// Loop through all required patches back from current version to $rev_num
	$patches = mysql_query("SELECT * FROM $diffs_table WHERE page_name='".esc($name)."' ORDER BY `time` DESC LIMIT ".($total_revs-$rev_num));
	while ($patch_row = mysql_fetch_assoc($patches)) {
		
		// Write this patch to file.
		$patch = $patch_row['diff'];
		file_put_contents($patch_file, $patch);
		
		// Apply patch.
		shell_exec("patch -R $this_ver_file $patch_file");
		
		// Re-read 'current version' from patch-modified file.
		$this_ver = file_get_contents($this_ver_file);
		
		// Get this revision's date and time.
		$rev_date = mysql_fetch_assoc(mysql_query("SELECT * FROM $diffs_table WHERE page_name='".esc($name)."' ORDER BY `time` ASC LIMIT $rev_num"));
		$rev_date = $rev_date['time'];
	
	}
	
	// Clean up
	system("rm -r ".$config['basepath']."/tmp/$tmp_hash");
	
	// Output revision.
	$html_page->addBodyContent("\tRevision Date: $rev_date");
	$html_page->addBodyContent("</p>\n\n\n".wikiformat($this_ver)."\n\n\n");

} // end if only one revision.

?>