<?php /* $Id$ */

/**
 *
 *       PrintWiki: A printable wiki.  Copyright 2006 Samuel Wilson.
 *
 *   Author:  Samuel Wilson, Canberra, Australia, <sam@co-operista.com>.
 *
 *  Version:  0.4b  (your constructive criticism is appreciated, please see our
 *            project page on http://sourceforge.net/projects/printwiki/ to post
 *            bug reports and feature requests.
 *
 *  Licence:  This program is free software; you can redistribute it and/or
 *            modify it under the terms of the GNU General Public License as
 *            published by the Free Software Foundation; either version 2 of the
 *            License, or (at your option) any later version.
 *
 *            This program is distributed in the hope that it will be useful,
 *            but WITHOUT ANY WARRANTY; without even the implied warranty of
 *            MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *            GNU General Public License for more details.
 *
 *            You should have received a copy of the GNU General Public License
 *            along with this program; if not, write to the Free Software
 *            Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
 *            MA  02110-1301  USA
 *
 */

//****************************************************************************//
// Main execution block begins here.
if (!file_exists('./data/config.php') || $_GET['install']!="") {
    install();
} else {
    require_once './data/config.php';
    if (!$Config) {
        configError();
        
    // Edit page.
    } else if ($_GET['edit']!="") {
        $title = $_GET['edit'];
        editPage($title);
    
    // PrintWiki admin.
    } else if ($_GET['admin']!="") {
    	admin($_GET['admin']);

    // Title index.
    } else if ($_GET['view'] == "index") {
    	pageIndex();
    	
    // Tex output.
    } else if ($_GET['view'] == "tex") {
    	displayTex();
    	
    // Display page.
    } else {
        if ($_GET['title'] == '') $title = $Config['homepage'];
        else $title = $_GET['title'];
    
        // Logout.
        if ($_GET['logout']) {
            doLogout("title=$title");

        // Get and display page.
        } else {
            displayPage($title);
        }
    }
} // end of main execution block.
//****************************************************************************//

function admin($section) {
	global $Config;
	doLogin("admin=general");
	if ($_SESSION['logged-in']) {
		if ($_POST['submit'] == "Save") {
			$Config['stylesheet'] = $_POST['stylesheet'];
			$configData = ("<?php\n\n".
				"if (\$_SERVER['PHP_SELF'] == \"config.php\") {\n".
				"    header(\"HTTP/1.0 403 Forbidden\");\n".
				"} else {\n".
				"    \$Config[\"homepage\"]   = \"".$Config["homepage"]."\";\n".
				"    \$Config[\"username\"]   = \"".$Config["username"]."\";\n".
				"    \$Config[\"password\"]   = \"".$Config["password"]."\";\n".
				"    \$Config[\"email\"]      = \"".$Config["email"]."\";\n\n".
				"    \$Config[\"stylesheet\"] = \"".$Config['stylesheet']."\";\n\n".
				"}\n".
				"?>");
			if ($configHandle = fopen("./data/config.php",'w')) {
				if ($e = fwrite($configHandle, $configData)) {
					if (fclose($configHandle)) {
						$message = "Administration options saved.";
					} else {
						$message = "Unable to close configuration file; please try again.";
					}
				} else {
					$message = "Unable to write configuration file; please try again.";
				}
			} else {
				$message = "Unable to open configuration file; please try again.";
			}
		}
		$styleSelect = ("<select name='stylesheet'>\n");
		foreach(getStyles() as $styleName=>$styleUri) {
			if ($styleUri == $Config['stylesheet']) {
				$selected = " selected='selected'";
			} else {
				$selected = "";
			}
			$styleSelect .= "  <option$selected value=\"$styleUri\">$styleName</option>\n";
		}
		$styleSelect .= ("</select>\n");
		$style = "";
		print(getHtmlHead("Administration: ".ucfirst($section)."",$style)."<body>\n\n".
				   "<p id='nav'><a href=\"".$_SERVER['PHP_SELF']."?title=".$Config['homepage']."\"".
				   " title=\"Go to homepage: ".$Config['homepage']."\">[home]</a></p>\n\n".
				   "<h1>Administration: ".ucfirst($section)."</h1>\n\n".
				   "<div id='page'>\n");
		if ($message != "") {
			print("<p class='warning'>$message</p>");
		}
		print("  <form action=\"".$_SERVER['PHP_SELF']."?admin=general\" method=\"post\">\n".
				   "    <p>Thanks to the <a href=\"http://www.w3.org/StyleSheets/Core/\">".
				   "W3C's Core Styles</a> for the different styles listed here, any ".
				   "of which you can apply to your wiki.</p>\n    <p>Choose your style: ".
				   "\n$styleSelect</p>\n</div><!-- end div#page -->\n\n<p id='edit'>".
				   "<input name='submit' type='submit' value='Save' /></p>\n".
				   "</form>\n\n</body>\n</html>\n");
    }
}

function doLogin($queryString) {
    global $Config;
    session_start();
    if ($_SESSION['logged-in'] != TRUE) {
        if ($_POST['login'] == "Login") {
            if ($_POST['uid'] == $Config['username'] && $_POST['pwd'] == $Config['password']) {
                $_SESSION['logged-in'] = TRUE;
            } else {
                $_SESSION['logged-in'] = FALSE;
                putMessage("<p>Wrong password and/or username.<br />Please try again.</p><p><a
                           href='".$_SERVER['PHP_SELF']."?$queryString'
                           class='button'>Back</a></p>");
            }
        } else {
            putMessage("<h1>Please log in:</h1>\n<form action='".$_SERVER['PHP_SELF']."?$queryString'
                       method='post'><p>Username: <input type='text' name='uid' />
                       </p><p>Password: <input type='password' name='pwd' /></p>
                       <p><input type='submit' name='login' value='Login' /></p>
                       </form>");
        }
    }
} // end doLogin();

function doLogout($queryString) {
    session_start();
    setcookie(session_name(), '', time()-42000, '/');
    session_destroy();
    putMessage("<p>Logged out.</p><p><a
               href='".$_SERVER['PHP_SELF']."?$queryString'
               class='button'>Continue</a></p>");
} // end doLogout()

function url2link($subject) {
    $pattern = "/(http|ftp):\/\/[^\s]+/i";
    $replace = "<a href=\"\$0\">\$0</a>";
    $subject = preg_replace($pattern, $replace, $subject);
    $img_types = "jpg|gif|jpeg|png|bmp|jpe";
    $pattern = "/\">(http:\/\/[^\s]+($img_types))</i";
    $replace = "\"><img src='$1' alt='An image: $1' title='$1' /><";
    return preg_replace($pattern, $replace, $subject);
} // end url2link()

function displayTex() {
    header("Content-Type: text/plain; charset=UTF-8");  
    $dh  = opendir("data/pages");
    while (false !== ($filename = readdir($dh))) {
        if (substr($filename, 0, 1) != ".") $files[] = $filename;
    }
    sort($files);
    print(
    "\documentclass[10pt,a4paper]{book}\n".
	"\usepackage{multicol}\n".
	"\setcounter{secnumdepth}{-1}\n".
	"\begin{document}\n".
	"\begin{multicols}{3}");
    foreach ($files as $title) {
        print("\n\n\medskip\n\bf{".strtoupper($title)."}\\\\\n");
        $page = file_get_contents("data/pages/".$title);
        echo texSyntax($page);
    }
    print("\n\n\end{multicols}\n\end{document}\n");
} // end displayTex()

function getStyles() {
	return(array("W3C Core: Chocolate"=>"http://www.w3.org/StyleSheets/Core/Chocolate",
	             "W3C Core: Midnight"=>"http://www.w3.org/StyleSheets/Core/Midnight",
	             "W3C Core: Modernist"=>"http://www.w3.org/StyleSheets/Core/Modernist",
	             "W3C Core: Oldstyle"=>"http://www.w3.org/StyleSheets/Core/Oldstyle",
	             "W3C Core: Steely"=>"http://www.w3.org/StyleSheets/Core/Steely",
	             "W3C Core: Swiss"=>"http://www.w3.org/StyleSheets/Core/Swiss",
	             "W3C Core: Traditional"=>"http://www.w3.org/StyleSheets/Core/Traditional",
	             "W3C Core: Ultramarine"=>"http://www.w3.org/StyleSheets/Core/Ultramarine"
	             ));
}

function pageIndex() {
	global $Config;
    $dh  = opendir("./data/pages");
    while (false !== ($filename = readdir($dh))) {
        if (substr($filename, 0, 1) != ".") $files[] = $filename;
    }
    sort($files);
    /*$style=("  ol {list-style-type:none}\n".
            "    ol li a {display:block; float:left; width:30%; margin:0 0.3em}\n".
            "    ol li a {text-decoration:none; color:blue}");*/
    print(getHtmlHead("Title Index",$style)."<body>\n\n");
    print("<p id='nav'><a href='".$_SERVER['PHP_SELF']."?title=".$Config['homepage']."'>".
          "[Home]</a></p>\n\n<h1>PrintWiki Title Index</h1>\n\n<div id='page'>\n  <ol>\n");
    foreach ($files as $title) {
        echo "    <li><a href='index.php?title=$title'>$title</a></li>\n";
    }
    echo "  </ol>\n</div><!-- end div#page -->\n\n<p id='edit'>&nbsp;</p>\n\n</body>\n</html>\n";
} // end pageIndex()

function editPage($title) {
    doLogin("edit=$title");
    if ($_SESSION['logged-in']) {
        if ($_POST['save']) {
            $handle = fopen("./data/pages/$title",'w');
            fwrite($handle, stripslashes($_POST['page']));
            fclose($handle);
            putMessage("<p>Page saved.</p><p><a
                        href='".$_SERVER['PHP_SELF']."?title=$title'
                        class='button'>Continue</a></p>");
        } else {
            if (!$page = @file_get_contents("./data/pages/".$title)) {
                $page = "This page has not yet been written.";
            }
            print(getHtmlHead("Edit: $title","")."<body><h1><em>$title</em></h1>\n".
            "<div id='page'>\n".
            "<pre><strong>Links:</strong> [[text]], <strong>Italics:</strong> ''text''</pre>\n".
            "<form action='".$_SERVER['PHP_SELF']."?edit=$title' method='post'>\n".
            "<p id=\"textarea\"><textarea name='page' cols='100' rows='24'>$page</textarea></p>\n".
            "</div><!-- end div#page -->\n\n".
            "<p id='edit'><input type='submit' name='save' value='Save' />\n".
            "<a href='".$_SERVER['PHP_SELF']."?title=$title'>[Cancel]</a>\n".
            "<a href='".$_SERVER['PHP_SELF']."?logout=true'>[Logout]</a>\n".
            "<a href='".$_SERVER['PHP_SELF']."?admin=general'>[Administration]</a>\n".
            "</p></body>\n</html>");        
        }
    }
} // end editPage()


function displayPage($title) {
    if (!$page = @file_get_contents("./data/pages/".$title)) {
        putMessage("<p>&lsquo;<em>$title</em>&rsquo; has not yet been written.  Would
                   you like to write it now?</p><p><a
                   href='".$_SERVER['PHP_SELF']."?edit=$title&referer=".$_GET['referer']."'
                   class='button'>Yes</a> <a href='".$_SERVER['PHP_SELF']."?title=".$_GET['referer']."'
                   class='button'>No</a></p>");
    } else {
        global $Config;
        $page = printWikiSyntax($title, $page);
        print(getHtmlHead($title,"").
        "<body>\n\n<p id='nav'><a ".
        "href='".$_SERVER['PHP_SELF']."?title=".$Config['homepage']."'>[Home]</a> ".
        "<a href=\"".$_SERVER['PHP_SELF']."?view=index\">[Index]</a> \n".
        "<a href=\"".$_SERVER['PHP_SELF']."?view=tex\">[Tex]</a> \n".
        "</p>\n\n<h1>$title</h1>\n\n<div id='page'>\n\n$page\n</div><!-- end ".
        "div#page -->\n\n<p id='edit'>".
        "<a href='".$_SERVER['PHP_SELF']."?edit=$title'>[Edit]</a> ".
        "<a href='".$_SERVER['PHP_SELF']."?admin=general'>[Admin]</a>".
        "</p>\n\n</body>\n</html>");
    }
}

 
function printWikiSyntax($title, $in) {
        // Platform-independent newlines.
    $in = preg_replace("/(\r\n|\r)/", "\n", $in);
        // Remove excess newlines.
    $in = preg_replace("/\n\n+/", "\n\n", $in);
        // Make paragraphs, including one at the end.
    $in = preg_replace('/\n?(.+?)(?:\n\s*\n|\z)/s', "<p>$1</p>\n", $in);
        // Remove paragraphs if they contain only whitespace.
    $in = preg_replace('|<p>\s*?</p>|', '', $in);
        // Strong emphasis.
    $in = preg_replace("/'''(.*?)'''/", "<strong>$1</strong>", $in);
        // Emphasis.
    $in = preg_replace("/''(.*?)''/", "<em>$1</em>", $in);
        // Links.
    $in = preg_replace("/\[\[(.*?)\]\]/", "<a href=\"".$_SERVER['PHP_SELF']."?title=$1\">$1</a>", $in);
    return $in;
}

function texSyntax($in) {
    $out = preg_replace(
        array(
            "/''(.*?)''/",                  // em
            "/\[\[(.*?)\]\]/"),             // small caps
        array(
            '\emph{\1}',                    // em
            '\scshape{\1}'),                // small caps
        $in
    );
    return $out;
}


function configError() {
    if ($_GET['error']=='config-exists') {
        putMessage("<p>I'm sorry, there's nothing more that can be done to
                   make PrintWiki work.</p><p><a class='button'
                   href='".$_SERVER['PHP_SELF']."?install=stage1'>Please
                   re-install.</a></p>");
    } else {
        putMessage("<p>There seems to be something wrong with your configuration
               file.  Prehaps the instalation process was interupted halfway
               through?  Would you like to try installing again?</p><p>
               <a class='button' href='".$_SERVER['PHP_SELF']."?install=stage1'>
               Yes</a> <a class='button' href='".$_SERVER['PHP_SELF']."?error=config-exists'>
               No</a></p>");
    }
}


function install() {
    $style = ("div{margin:2em 25%; color:black; background-color:#B9EA93; padding:1em;
    border:2px groove green}
    h1{text-align:center; margin:0 0.2em 0 0; font-variant:small-caps;
    font-size:2em; text-decoration:underline; display:block}
    p{text-align:justify} p.input{margin:0; text-align:right}
    p.check{text-align:right; margin:0} span.no{color:red} span.yes{color:green}
    input{border:2px solid #999; margin:0.2em; text-align:center; width:45%}");
    print(getHtmlHead("Welcome to PrintWiki",$style));
    print("<body><div>");
    
    if ($_GET['install']=="" || $_GET['install']=="stage1") {
        $currentDir = $_SERVER['SCRIPT_FILENAME'];
        $button="Continue";
        print("<h1>Welcome to PrintWiki</h1><p>You are about to install PrintWiki.
        We will now run a few tests to determine whether everything is in order
        to proceed.</p><form action=\"".$_SERVER['PHP_SELF']."\"method=\"get\">
        <p class=\"check\">Checking that the <code>data</code>
        directory exists . . . ");
        if (file_exists("./data")) {
            print("<span class='yes'>yes</span></p>");
            $data_exists = true;
        } else {
            print("<span class='no'>no</span></p><p>Please create the <code>data
                   </code>directory (in the PrintWiki directory), and try installing
                   again.</p>");
            $data_exists = false;
        }
        if ($data_exists) {
            print('<p class="check">Checking that the <code>data</code> directory is writable . . . ');
            if (is_writable("./data")) {
                print("<span class='yes'>yes</span></p>");
                $data_writable = true;
            } else {
                print("<span class='no'>no</span></p><p>Please make the <code>data
                       </code>directory (in the PrintWiki directory) writable, and
                       try installing again.</p>");
                $data_writable = false;
            }
            print('<p class="check">Checking whether <code>./data/config.php</code> exists . . . ');
            if (file_exists("./data/config.php")) {
                print("<span class='yes'>yes</span></p>");
                $reinstall = true;
            } else {
                print("<span class='yes'>no</span></p><p>(But that's okay, becuase
                it's not supposed to yet.)</p>");
            }
        }
        print('<p style="text-align:center">');
        if ($data_exists && $data_writable) {
            if ($reinstall) {
                print('Everything is okay, you can now re-install PrintWiki.  Your
                      pages will not be overwritten, but we will check your
                      configuration and re-set anything that is wrong.<br />
                      <input type="hidden" name="install" value="stage2" />
                      <input type="submit" value="Re-Install" />');
            } else {
                print('Everything is okay, you can now install PrintWiki.<br />
                      <input type="hidden" name="install" value="stage2" />
                      <input type="submit" value="Continue" />');
            }
        } else {
            print('<input type="submit" value="Try Again" />');
        }
        print('</p></form>');
    }
    
    else if ($_GET['install'] == 'stage2') {
        if (file_exists("./data/config.php")) {
            require_once("./data/config.php");
        }
            if ($Config['homepage']=="") $Config['homepage'] = "Welcome to PrintWiki";
            if ($Config['username']=="") $Config['username'] = "admin";
        print('<h1>Install PrintWiki</h1><p>Before you can begin using
        PrintWiki, we need to set up a few things.  You don\'t need to know
        anything very technical, so don\'t worry!</p>
        <form action="'.$_SERVER['PHP_SELF'].'?install=stage3" method="post">
        <p>Firstly, you need to decide on a name for the homepage of your wiki.</p>
        <p style="text-align:right">Homepage name:
        <input type="text" name="homepage" value="'.$Config['homepage'].'" /></p>
        <p>Next, choose your user name and password.</p>
        <p class="input">User name:
        <input type="text" name="username" value="'.$Config['username'].'" /></p>
        <p class="input">Password:
        <input type="password" name="password" /></p>
        <p class="input">Password (again, for verification):
        <input type="password" name="password-verification" /></p>
        <p>If you would like to email backups to yourself, enter your email address
        here.  (It won\'t be publicly visible anywhere, so don\'t worry about spam.)</p>
        <p class="input">Email address:
        <input type="text" name="email" value="'.$Config['email'].'"/></p>
        <p style="text-align:center"><input type="submit" value="Install" /></p>
        </form>');
    }
    
    else if ($_GET['install'] == 'stage3') {
        if ($_POST['homepage']=="") {
            print("<p>Please start the install process <a
                  href=\"".$_SERVER['PHP_SELF']."?install=stage1\">at the beginning</a>.</p>");
        } else {
            print("<h1>Now installing PrintWiki&hellip;</h1><p></p>");
            print("<p class=\"check\">Verifying password . . . ");
            if ($_POST['password']==$_POST['password-verification']) {
                print("<span class=\"yes\">okay</p>");
                $pwdOk = true;  
                print("<p class=\"check\">Opening <code>./data/config.php</code> with write access . . . ");
                if ($configHandle = fopen("./data/config.php",'w')) {
                    print("<span class=\"yes\">okay</p>");
                    $initialStylesheet = getStyles();
                    $configData = ("<?php\n\n".
                        "if (\$_SERVER['PHP_SELF'] == \"config.php\") {\n".
                        "    header(\"HTTP/1.0 403 Forbidden\");\n".
                        "} else {\n".
                        "    \$Config[\"homepage\"] = \"".$_POST["homepage"]."\";\n".
                        "    \$Config[\"username\"] = \"".$_POST["username"]."\";\n".
                        "    \$Config[\"password\"] = \"".$_POST["password"]."\";\n".
                        "    \$Config[\"email\"]    = \"".$_POST["email"]."\";\n\n".
                        "    \$Config[\"stylesheet\"]    = \"".$initialStylesheet['W3C Core: Oldstyle']."\";\n\n".
                        "}\n".
                        "?>");
                    print("<p class=\"check\">Writing configuration file . . . ");
                    if ($e = fwrite($configHandle, $configData)) {
                        print("<span class=\"yes\">okay</p>");
                        print("<p class=\"check\">Closing <code>./data/config.php</code> . . . ");
                        if (fclose($configHandle)) {
                            print("<span class=\"yes\">okay</p>");
                        } else {
                            print("<span class=\"no\">failed</p>");
                        }
                        print("<p class=\"check\">Checking for <code>./data/pages</code> directory . . . ");
                        if (file_exists("./data/pages")) {
                            print("<span class=\"yes\">found</p>");
                            $pagesExists = true;
                        } else {
                            print("<span class=\"no\">not found</p>");
                            print("<p class=\"check\">Creating <code>./data/pages</code> directory . . . ");
                            if (mkdir("./data/pages")) {
                                print("<span class=\"yes\">okay</p>");
                                $pagesExists = true;
                            } else {
                                print("<span class=\"no\">failed</p>");                         
                            }
                        }
                    } else {
                        print("<span class=\"no\">failed</p>");
                    }
                } else {
                    print("<span class=\"no\">failed</p>");
                }
                if ($pagesExists) {
                    print("<p class=\"check\">Checking for homepage
                          (<code>".$_POST['homepage']."</code>) . . . ");
                    if (file_exists("./data/pages/".$_POST['homepage'])) {
                        print("<span class=\"yes\">found</p>");
                        $homepageExists = true;
                    } else {
                        print("<span class=\"no\">not found</p>");
                        print("<p class=\"check\">Creating homepage
                              (<code>".$_POST['homepage']."</code>) . . . ");
                        if ($homepageHandle = fopen("./data/pages/".$_POST['homepage'],'w')) {
                            $homepageData = ("Welcome to ''PrintWiki''.".
                                             "This is your homepage.  Click the ".
                                             "edit button below to begin editing");
                            fwrite($homepageHandle, $homepageData);
                            print("<span class=\"yes\">okay</p>");
                            $homepageExists = true;
                        } else {
                            print("<span class=\"no\">failed</p>");                         
                        }
                    }
                    print("<p class=\"check\">Checking for basic stylesheet
                          (<code>basic.css</code>) . . . ");
                    if (file_exists("./data/basic.css")) {
                        print("<span class=\"yes\">found</p>");
                        $stylesheetExists = true;
                    } else {
                        print("<span class=\"no\">not found</p>");
                        print("<p class=\"check\">Creating basic stylesheet
                              (<code>basic.css</code> . . . ");
                        if ($stylesheetHandle = fopen("./data/basic.css",'w')) {
                            $stylesheetData = (
                                "body, p#nav, p#edit, div#page, h1 {\n".
                                "   border:1px solid grey;\n".
                                "   margin:1px;\n".
                                "   padding:1px;\n".
                                "}\n".
                                "body {\n".
                                "   margin:1em 20%;\n".
                                "   padding: 0;\n".
                                "}\n".
                                "p#nav {\n".
                                "   font-size:smaller;\n".
                                "}\n".
                                "h1 {\n".
                                "   text-align:center;\n".
                                "   font-variant: small-caps;\n".
                                "   letter-spacing:0.1em;\n".
                                "}\n".
                                "div#page {\n".
                                "   padding:1em;\n".
                                "   font-family: serif;\n".
                                "}\n".
                                "textarea {\n".
                                "   width:97%;\n".
                                "   margin:0;\n".
                                "}\n".
                                "p#edit {\n".
                                "   text-align:center;\n".
                                "   font-size:smaller;\n".
                                "}\n");
                            fwrite($stylesheetHandle, $stylesheetData);
                            print("<span class=\"yes\">okay</p>");
                            $stylesheetExists = true;
                        } else {
                            print("<span class=\"no\">failed</p>");                         
                        }
                    }
                }
                if ($pagesExists && $homepageExists && $stylesheetExists) {
                    print("<p>Congratulations, PrintWiki has been installed, and is
                          now ready for you to start editing.</p><p style='text-align:center'><a
                          href=\"".$_SERVER['PHP_SELF']."\" class=\"button\">Wiki Homepage</a></p>");
                }
            } else {
                print("<span class=\"no\">failed</p><p>Please go back and try again.</p>
                <p><a href=\"".$_SERVER['PHP_SELF']."?install=stage2\" class=\"button\">
                Go Back</a></p>");
                $pwdOk=false;
            }
        }
    }
    
    print('</div></body></html>');
    
}

/** Return string */
function getHtmlHead($title, $style) {
	global $Config;
    $head = ("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
    "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n".
    "     \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n".
    "<html xmlns=\"http://www.w3.org/1999/xhtml\" lang=\"en-AU\" xml:lang=\"en-AU\">\n".
    "<head>  \n<title>$title</title>\n".
    "  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n".
    "  <link type=\"text/css\" rel=\"stylesheet\" href=\"".$Config['stylesheet']."\" />\n".
    "  <link type=\"text/css\" rel=\"stylesheet\" href=\"data/basic.css\" />\n".
    "  <style type=\"text/css\">\n    $style\n</style>\n</head>\n");
    return $head;
}

function putMessage($m) {
	$style = "div.page * {text-align:center}";
    print(getHtmlHead("Attention!",$style)."<body><div id='page'>$m</div>
          </body></html>"); 
}

?>