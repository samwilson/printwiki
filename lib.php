<?php

function wikiformat($in) {
	$out = strip_tags($in);
		// Platform-independent newlines.
	$out = preg_replace("/(\r\n|\r)/", "\n", $out);
		// Paragraphs.
	//$out = preg_replace('|(.*)|', "<p>$1</p>", $out);
	$out = preg_replace("|^|", "<p>", $out);
	$out = preg_replace("|$|", "</p>", $out);
	$out = preg_replace("|\n+\s*\n+|", "</p>\n<p>", $out);
		// Remove paragraphs if they contain nothing (including only whitespace).
	$out = preg_replace('|<p>\s*</p>|', '', $out);
		// Remove nested paragraphs (some pages already have paragraphs marked up).
	$out = preg_replace('|<p>\s*<p>|', '<p>', $out);
	$out = preg_replace('|</p>\s*</p>|', '</p>', $out);
		// Strong emphasis.
	$out = preg_replace("|'''(.*?)'''|s", "<strong>$1</strong>", $out);
		// Emphasis.
	$out = preg_replace("/''(.*?)''/", "<em>$1</em>", $out);
		// Links.
	$out = preg_replace("/\[\[([^\]]*)\]\]/", "<a href='/$1'>$1</a>", $out);
	$out = preg_replace("/[^\"']http:\/\/([^\s]*)/", " <a href='http://$1'>$1</a>", $out);
		// Unordered lists.
	$out = preg_replace("|<p>\*|", "<ul>\n<li>", $out);
	$out = preg_replace("|\n\*|", "</li>\n<li>", $out);
	$out = preg_replace("|<li>(.*)</p>|", "<li>$1</li>\n</ul>", $out);
		// Ordered lists.
	$out = preg_replace("|<p>#|", "<ol>\n<li>", $out);
	$out = preg_replace("|\n#|", "</li>\n<li>", $out);
	$out = preg_replace("|<li>(.*)</p>|", "<li>$1</li>\n</ol>", $out);
		// Headings.
	$out = preg_replace("|<p>==(.*)==</p>|", "\n<h2>$1</h2>\n", $out);
	return $out;
}
function wikiformat_doco() {
	return "<ul>
		<li>Links <code>[[</code>id<code>|</code>text<code>]]</code> and <code>http://</code>blah.com</li>
		<li>Emphasis: <code>''</code>text<code>''</code></li>
		<li>Strong emphasis: <code>'''</code>text<code>'''</code></li>
		<li>Lists: <code>*</code>text and <code>#</code>text (blank line before)</li>
		<li>Images: <code>[[img:</code>id<code>]]</code></li>
		<li>Headings: ==heading== </li>
		</ul>";
}


function error_page($message, $http_status=200) {
	global $config, $html_page;
	switch ($http_status) {
		
		case 404:
			header("HTTP/1.1 404 Not Found"); 
			$html_page->setTitle("404 Not Found &laquo; ".$config['sitename']);
			$html_page->addBodyContent("<h2>404 Not Found&hellip;</h2><div>$message</div>");
			break;
				
		default:
			$html_page->setTitle("Error &laquo; ".$config['sitename']);
			$html_page->addBodyContent("<h2>Error</h2><div>$message</div>");
			
	}
	$html_page->display();
	die();
}

function slug($name) {
	return preg_replace("/[^a-zA-Z0-9_\-\:]/","",$name);
}


/**
 * Quote variables to make them safe for using in SQL.
 * From http://au.php.net/manual/en/function.mysql-real-escape-string.php
 */
function esc($value) {
	// Stripslashes
	if (get_magic_quotes_gpc()) {
		$value = stripslashes($value);
	}
	// Quote if not a number or a numeric string
	if (!is_numeric($value)) {
		$value = mysql_real_escape_string($value);
	}
	return $value;
}


?>