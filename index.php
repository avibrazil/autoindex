<?php

class DirList {
	function __construct() {
		$this->locale = "pt_BR.UTF-8";
		
		$this->publicHost = "digitalk7.com";
		$this->publicHostRoot = "/home/aviram/digitalk7.com/mus";
		$this->publicHostRootAlias = "/mus";

		$this->userLang        = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
		$this->location        = urldecode($_SERVER["SCRIPT_URL"]);
		$this->tokenizedLocation = explode("/",$this->location);
		$this->isFacebook      = (strpos($_SERVER["HTTP_USER_AGENT"],'facebook')!==FALSE);
		$this->script_location = str_replace("index.php", "", $_SERVER["PHP_SELF"]); 

		if (strpos($_SERVER['SERVER_NAME'],$this->publicHost) !== FALSE) {
            // if we are on the public host...
			$this->root = $this->publicHostRoot;
			$this->rootAlias = $this->publicHostRootAlias;
			$this->publicSite = TRUE;
		} else {
			$this->root = "/media/Media";
			$this->rootAlias = "/media";
			$this->publicSite = FALSE;
			
			$__count=1;
			
			$this->publicHostEquivalentURL =
				"http://" .
				$this->publicHost .
				str_replace(
					$this->rootAlias . "/Musica/",
					$this->publicHostRootAlias . "/",
					$this->location,
					$__count
				);
		}

		$this->script_path     = DirList::physicalPath($this->script_location); 
		$this->path            = DirList::physicalPath($this->location);

		if (count($this->tokenizedLocation)>4) {
			$this->reducedLocation = "…/" . implode("/",array_slice($this->tokenizedLocation,count($this->tokenizedLocation)-3,2));
		} else {
			$this->reducedLocation = $this->location;
		}
	}

	function breadcrumb() {
		global $location;

		$parts=explode("/",rtrim($this->location,'/'));
		$rebuild="";
		$breadcrumb="";

/*
		foreach ($parts as $i => $n) {
			if ($i == 0) continue;
			
			if ($i == 1) $rebuild="/";
			else $breadcrumb.="<span class=\"separator\">»</span>";
			
			$rebuild .= "$n/";
			$class=($i>1 ? "folder" : "home");
			$ii=$i-1;
			$line = "<a class=\"$class\" data-depth=\"$ii\" href=\"$rebuild\">$n</a>";
			$breadcrumb.=$line;
		}
*/

		end($parts);
		$parts_last_index=key($parts);
		
		foreach ($parts as $i => $n) {
			if ($i == 0) continue;
			
			if ($i == 1) $rebuild="/";
			
			$modifier="";
			if ($i == $parts_last_index) $modifier='class="active"';

			$rebuild .= "$n/";
			$ii=$i-1;
			$line = "<li $modifier><a data-depth=\"$ii\" href=\"$rebuild\">" . DirList::revertSpecialChars($n) . "</a></li>";
			$breadcrumb.=$line;
		}

		echo $breadcrumb;
	}
	
	function formatBytes($bytes, $precision = 2) { 
		$units = array('B', 'KB', 'MB', 'GB', 'TB'); 

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
		$pow = min($pow, count($units) - 1); 

		// Uncomment one of the following alternatives
		$bytes /= pow(1024, $pow);
		// $bytes /= (1 << (10 * $pow)); 

		return round($bytes, $precision) . ' ' . $units[$pow]; 
	}

	function physicalPath($l) {
		$ll=$l;

		$p=str_replace($this->rootAlias,$this->root,$ll);

		if ($p === "/") {
			header("HTTP/1.0 404 Not Found");
			exit();
		}

		return $p;
	}

	function join_paths() {
		$args = func_get_args();
		$paths = array();
		
		foreach ($args as $arg) {
			$paths = array_merge($paths, (array)$arg);
		}
		
		foreach ($paths as &$path) {
			$path = trim($path, '/');
		}
		
		return "/".join('/', $paths);
	}
	
	function folderImage() {
		$folderImageName = "folder.jpg";
	
		$i = $this->path . "/" . $folderImageName;
		
		if (!file_exists($i)) return false;
		
		if (is_link($i)) return readlink($i);
		
		return $folderImageName;
	}


	/**
	 * Prepare an array of files and directories to be rendered later by file_list()
	 */
	function list_dir() {
		$files = array();
		$dirs = array();
		$dir = opendir($this->path);

		if (!$dir) return;
		
		while ( ($entry = readdir($dir)) !== false ) {
			//      if (in_excludes($entry)) continue;

			$full_path = DirList::join_paths($this->path, $entry); 
			$e = array(
				"name"=>$entry
			);

			if (is_link($full_path)) $e["link"]=readlink($full_path);
			else $e["link"]=str_replace("%", "%25", $e["name"]);
			
			if (is_dir($full_path) && $full_path != $this->script_path) {				
				$dirs[] = $e;
			}
			
			if (is_file($full_path)) {
				$statstruct=stat($full_path);
				$e['size']  = $statstruct['size'];
				$e['mtime'] = $statstruct['mtime'];
				$e['path']  = $full_path;
				
				$files[] = $e;
			}
		}

		closedir($dir);

		$originalLocale=setlocale(LC_COLLATE,0);
		setlocale(LC_COLLATE,$this->locale);

		usort($files,array("DirList","cmp"));
		usort($dirs,array("DirList","cmp"));
		
		setlocale(LC_COLLATE,$originalLocale);
		
		#reset($files);
		#reset($dirs);

		return array($files, $dirs);
	}
	
	static function cmp($a, $b) {
		return strcoll($a["name"], $b["name"]);
	}

	function revertSpecialChars($string) {
		$string=str_replace("／","/",$string);
		$string=str_replace("＼","\\",$string);
		$string=str_replace("￨","|",$string);
		$string=str_replace("⁇","?",$string);
		$string=str_replace("♯","#",$string);
		$string=str_replace("＞",">",$string);
		$string=str_replace("＜","<",$string);
		$string=str_replace("＆","&",$string);
		$string=str_replace("✱","*",$string);
		$string=str_replace("∶",":",$string);
		$string=str_replace("∶",":",$string);

		return $string;
	}

	function file_list() {
		// Get list of files and directories
		list($files, $dirs) = $this->list_dir();
		$modifier="";
		$ihighlight=0;

		// Display list of directories:
		if (!empty($dirs)) {
			for ($i = 0; $i < sizeof($dirs); $i++) {
				$modifier="";
				$icon="<span class=\"glyphicon glyphicon-folder-open\"></span>";
			
				$dirdesc = $dirs[$i];
				if ($dirdesc["name"] != ".") {
					if ($dirdesc["name"] == "..") {
						$dirdesc["name"] = "Parent Folder";
						$modifier = "back";
						$icon="<span class=\"glyphicon glyphicon-arrow-up\"></span>";
					}

					$dirdesc['name']=DirList::revertSpecialChars($dirdesc['name']);


					if (!empty($this->meta['highlight'])) {
						if (isset($this->meta['highlight']['fromURL'])) {
							if (array_search($ihighlight,$this->meta['highlight']['fromURL']) !== FALSE)
								$modifier.=" active";
						} else {
							if (array_search($ihighlight,$this->meta['highlight'][0]) !== FALSE)
								$modifier.=" active";
						}
					}

					echo "<li id=\"main-list-$ihighlight\" class=\" folder $modifier\" data-type=\"folder\" data-subtype=\"$modifier\" data-index=\"$ihighlight\" data-target=\"" . $dirdesc['link'] . "\" data-name=\"" . $dirdesc['name'] . "\">";
					echo "<span class=\"link\"><a href=\"" . $dirdesc['link'] . "/\">$icon</a></span>";
					echo "<span class=\"name\"><a href=\"" . $dirdesc['link'] . "/\">" . $dirdesc['name'] . "</a></span> <a class=\"popover-trigger\" title=\"Options for item\"><span class=\"glyphicon glyphicon-circle-arrow-right\"></span></a></li>\n";

	/*               
					echo "<span class=\"list-group-item folder $modifier\">\n";
					echo "<a href=\"{$dirs[$i]}/\"><span class=\"glyphicon glyphicon-folder-open\"></span></a>\n";
					echo "<a href=\"{$dirs[$i]}/\">$dirdesc</a>\n";
					echo "</span>";
	*/				
				
					$ihighlight++;
				}
			}
		}

		// Display list of files
		if (!empty($files)) {
			$microdataWrap="itemprop=\"tracks\" itemscope itemtype=\"http://schema.org/MusicRecording\"";
			$microdataPlay='itemprop="audio"';
			$microdataGet='itemprop="offers"';
			foreach ($files as $file) {
				//for ($i = 0; $i < sizeof($files); $i++) {
				$modifier="";
				$icon="<span class=\"glyphicon glyphicon-file\"></span>";
				$actions="<span class=\"glyphicon glyphicon-link\"></span>";
				$microdataWrap="";
				$microdataPlay='';
				$microdataGet='';
				$play="";
				$audio=FALSE;
				$mime="";        
				$filename = $file["name"];
				$ext = substr(strrchr($filename, '.'), 1);
			
				$modifier="_$ext";

				switch ("$ext") {
					case 'mp3':
						$mime='audio/mpeg';
						break;
					case 'm4a':
						$mime='audio/mp4';
						break;
					case 'ogg':
						$mime='audio/ogg';
						break;
					case 'flac':
						$mime='audio/ogg';
						break;
					case 'mka':
						$mime='audio/webm';
						break;
					case 'wav':
						$mime='audio/wav';
						break;
				}
				
				if (strpos($mime,'audio')!==FALSE) {
					$audio=TRUE;
					$icon="<span class=\"glyphicon glyphicon-volume-up\"></span>";
					$microdataWrap="itemprop=\"tracks\" itemscope itemtype=\"http://schema.org/MusicRecording\"";
					$microdataPlay='itemprop="audio"';
					$microdataGet='itemprop="offers"';
					$play="?play";
				}

				if (!empty($this->meta['highlight'])) {
					if (isset($this->meta['highlight']['fromURL'])) {
						if (array_search($ihighlight,$this->meta['highlight']['fromURL']) !== FALSE)
							$modifier.=" active";
					} else {
						if (array_search($ihighlight,$this->meta['highlight'][0]) !== FALSE)
							$modifier.=" active";
					}
				}

				$url = $file["link"];
				
				$filename=DirList::revertSpecialChars($filename);
				
				$desc=preg_replace('/^(\d+) (.*) ♫ (.*)(\..*)/',
					'<span class="track">$1</span> <span class="artist" itemprop="accountablePerson">$2</span> <span class="separator">♫</span> <span class="song" itemprop="name">$3</span><span class="extension">$4</span>',
					$filename);
				if ($desc==$filename) {
					$desc=preg_replace('/^(\d+) (.*)(\..*)/',
						'<span class="track">$1</span> <span itemprop=\"name\">$2</span><span class="extension">$3</span>',
						$filename);
				}
								
				$size = DirList::formatBytes($file["size"]);

				echo "<li id=\"main-list-$ihighlight\" class=\" file $modifier\" data-mimetype=\"$mime\" data-type=\"file\" data-subtype=\"$modifier\" data-index=\"$ihighlight\" data-target=\"$url\" data-name=\"$filename\" data-size=\"" . $file["size"] . "\">";
				echo "<span class=\"item\" $microdataWrap >\n";
				echo "<span class=\"icon\" $microdataPlay ><a href=\"$url\" title=\"click to download\" \">$icon</a></span>";
//				echo "$actions";
				echo "<span class=\"name\" $microdataGet ><a href=\"$url\" title=\"click to download file\">$desc</a></span> ";
				echo "<small class=\"text-muted\">$size</small>";
				echo "</span> <a class=\"popover-trigger\" title=\"Options for item\"><span class=\"glyphicon glyphicon-circle-arrow-right\"></span></a></li>\n";
	/*
				echo "<span class=\"list-group-item file $modifier\">\n";
				echo "<a href=\"$url" . ((strpos($mime,'audio')!==FALSE) ? "?play" : "") . "\"><span class=\"glyphicon glyphicon-play\"></span></a>\n";
				echo "<a href=\"$url\">$url</a>\n";
				echo "</span>";
	*/


				$ihighlight++;
			}
		}
	  }

	function renderRelatedFormItem($uri,$text) {
		$t="<div class=\"row from-group related_group\">";
		$t.="<div class=\"col-md-3\"><input type=\"text\" class=\"form-control uri in\" value=\"" . $uri . "\"></div>";
		$t.="<div class=\"col-md-8\"><input type=\"text\" class=\"form-control text in\" value=\"" . $text . "\"></div>";
		$t.="<div class=\"col-md-1\"><a href=\"" . $uri . "\" target=\"_new\"><span title=\"visit link\" class=\"glyphicon glyphicon-new-window\"></span></a> <span title=\"add entry\" class=\"glyphicon glyphicon-plus\" onclick=\"appendRelatedLink(this);\"></span></div>";
		$t.="</div>";

		return $t;
	}

	function renderRelatedForm() {
		foreach ($this->meta['related'] as $k => $v) {
			$l=NULL;
			$t=NULL;
			$l=DirList::getMetaVarLang($v);
			$lstring=empty($l)?"":"lang=\"$l\"";
			if (!empty($l)) $t=DirList::getMetaVar($v);

			echo DirList::renderRelatedFormItem($v['uri'],$t);
		}

		// render an additional empty item
		echo DirList::renderRelatedFormItem();
	}

	function renderRelated() {		
		foreach ($this->meta['related'] as $k => $v) {
			$l=NULL;
			$t=NULL;
			$l=DirList::getMetaVarLang($v);
			$lstring=empty($l)?"":"lang=\"$l\"";
			//$lstring=DirList::getLangAttribute($v);
			if (!empty($l)) $t=DirList::getMetaVar($v);
			
			if (empty($t)) {
				# autotitle
				
				$t=$v['uri'];
				
				$pat = array(
					'/\.\.\//',
					'/\?.*/',
					'/\/$/',
					'/\//'
				);
				$rep = array(
					'',
					'',
					'',
					' » '
				);
				
				$t=preg_replace($pat,$rep,$t);
			}

			echo "<li $lstring><a href=\"" . $v['uri'] . "\">$t</a></li>";
		}
	}

	function getMetaVar($v) {
		if (is_array($v)) $o=&$v;
		else if (isset($this->meta) && isset($this->meta[$v])) {
			$o=$this->meta[$v];
		} else return NULL;
	
		if (empty($o)) return NULL;

		if (isset($o['fromURL'])) return $o['fromURL'];
				
		if (isset($o['lang.' . $this->userLang]))
			return $o['lang.' . $this->userLang];
		
		if (isset($o['lang_' . $this->userLang]))
			return $o['lang_' . $this->userLang];
		
		if (isset($o['lang.default']))
			return $o['lang.default'];
		
		if (isset($o['lang_default']))
			return $o['lang_default'];
			
//		if (isset($o[0]))
//			return $o[0];
			
		
		$keys=array_keys($o);
		$validKeys=preg_grep("/lang[_\.].*/",$keys);
		
//		var_dump($validKeys);
		
		if (!empty($validKeys))
			return $o[array_shift($validKeys)];
		
		return NULL;
	}

	function getMetaVarLang($v) {
		if (is_array($v)) $o=&$v;
		else if (isset($this->meta) && isset($this->meta[$v])) {
			$o=$this->meta[$v];
		} else return NULL;
		
		if (empty($o)) return NULL;
		if (isset($o['fromURL'])) return NULL;
		if (isset($o['lang.' . $this->userLang]) || isset($o['lang_' . $this->userLang])) return $this->userLang;
		if (isset($o['lang.default']) || isset($o['lang_default'])) return 'en';
		
		$keys=array_keys($o);
		$validKeys=preg_grep("/lang[_\.].*/",$keys);
		//var_dump($validKeys);
		
		if (!empty($validKeys))
			return preg_replace("/lang[_\.]/",'',array_shift($validKeys));
		
		return NULL;
	}
	
	function getLangAttribute($v) {
		$l=$this->getMetaVarLang($v);
		
		if ($l) echo "lang=\"$l\"";
	}
	
	function getMetaJSON() {
		return json_encode($this->meta);
	}
	
	function getURLJSON () {
	}
	
	function loadMeta() {
		$file=$this->path . "meta.json";
		
		if (file_exists($file)) {
			$metameta=json_decode(file_get_contents($file),true);
			
			if (isset($metameta['title']))
			$this->meta['title']         = $metameta['title'];

			if (isset($metameta['notesBefore']))			
			$this->meta['notes_before']  = $metameta['notesBefore'];
			
			if (isset($metameta['notesAfter']))			
			$this->meta['notes_after']   = $metameta['notesAfter'];

			if (isset($metameta['highlight']))			
			$this->meta['highlight'][0]  = $metameta['highlight'];
			
			if (isset($metameta['related']))			
			$this->meta['related']       = $metameta['related'];
		}
	}

	function processForm() {
		$param = array();
		
		$this->loadMeta();

		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			// encode POST vars to base64, put them on URL and redirect
			foreach ($_POST as $k => $v) {
				$_POST[$k]=str_replace("+",".",base64_encode($v));
			}

			$url=$_SERVER["SCRIPT_URI"];
			$url.="?";

			foreach ($_POST as $k => $v) {
				if ($v === "") continue;
				$url.="$k=$v&";
			}

			header("Location: $url");
			exit();

		} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
			foreach ($_GET as $k => $v) {
				# $nk=str_replace("__","",$k);
				
				/* Now overwrite whatever came from the directory META file */
				$this->meta[$k]['fromURL']=base64_decode(str_replace(".","+",$v));
			}

			
			# convert hightlight string (e.g. "1,3,7,8") into an array
			if (!empty($this->meta['highlight'])) {
				foreach ($this->meta['highlight'] as $k => $v) {
					if (!isset($this->meta['highlight'][$k])) continue;
					$h=$v;
					$v=array();
					$v=explode(',',$h);
					$this->meta['highlight'][$k]=$v;
				}
			}
		}
	}
	
	/* while we wait for PHP 5.5... */
	function json_last_error_msg() {
		switch (json_last_error()) {
			case JSON_ERROR_NONE:
				return ' - No errors';
			break;
			case JSON_ERROR_DEPTH:
				return ' - Maximum stack depth exceeded';
			break;
			case JSON_ERROR_STATE_MISMATCH:
				return ' - Underflow or the modes mismatch';
			break;
			case JSON_ERROR_CTRL_CHAR:
				return ' - Unexpected control character found';
			break;
			case JSON_ERROR_SYNTAX:
				return ' - Syntax error, malformed JSON';
			break;
			case JSON_ERROR_UTF8:
				return ' - Malformed UTF-8 characters, possibly incorrectly encoded';
			break;
			default:
				return ' - Unknown error';
			break;
		}

	}

}
  

/**
 * Replaces double line-breaks with paragraph elements.
 *
 * A group of regex replaces used to identify text formatted with newlines and
 * replace double line-breaks with HTML paragraph tags. The remaining
 * line-breaks after conversion become <<br />> tags, unless $br is set to '0'
 * or 'false'.
 *
 * BORROWED FROM WORDPRESS/wp-includes/formatting.php
 *
 * @param string $pee The text which has to be formatted.
 * @param bool $br Optional. If set, this will convert all remaining line-breaks after paragraphing. Default true.
 * @return string Text which has been converted into correct paragraph tags.
 */
function wpautop($pee, $br = true) {
	$pre_tags = array();

	if ( trim($pee) === '' )
		return '';

	$pee = $pee . "\n"; // just to make things a little easier, pad the end

	if ( strpos($pee, '<pre') !== false ) {
		$pee_parts = explode( '</pre>', $pee );
		$last_pee = array_pop($pee_parts);
		$pee = '';
		$i = 0;

		foreach ( $pee_parts as $pee_part ) {
			$start = strpos($pee_part, '<pre');

			// Malformed html?
			if ( $start === false ) {
				$pee .= $pee_part;
				continue;
			}

			$name = "<pre wp-pre-tag-$i></pre>";
			$pre_tags[$name] = substr( $pee_part, $start ) . '</pre>';

			$pee .= substr( $pee_part, 0, $start ) . $name;
			$i++;
		}

		$pee .= $last_pee;
	}

	$pee = preg_replace('|<br />\s*<br />|', "\n\n", $pee);
	// Space things out a little
	$allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|option|form|map|area|blockquote|address|math|style|p|h[1-6]|hr|fieldset|noscript|legend|section|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary)';
	$pee = preg_replace('!(<' . $allblocks . '[^>]*>)!', "\n$1", $pee);
	$pee = preg_replace('!(</' . $allblocks . '>)!', "$1\n\n", $pee);
	$pee = str_replace(array("\r\n", "\r"), "\n", $pee); // cross-platform newlines
	if ( strpos($pee, '<object') !== false ) {
		$pee = preg_replace('|\s*<param([^>]*)>\s*|', "<param$1>", $pee); // no pee inside object/embed
		$pee = preg_replace('|\s*</embed>\s*|', '</embed>', $pee);
	}
	$pee = preg_replace("/\n\n+/", "\n\n", $pee); // take care of duplicates
	// make paragraphs, including one at the end
	$pees = preg_split('/\n\s*\n/', $pee, -1, PREG_SPLIT_NO_EMPTY);
	$pee = '';
	foreach ( $pees as $tinkle )
		$pee .= '<p>' . trim($tinkle, "\n") . "</p>\n";
	$pee = preg_replace('|<p>\s*</p>|', '', $pee); // under certain strange conditions it could create a P of entirely whitespace
	$pee = preg_replace('!<p>([^<]+)</(div|address|form)>!', "<p>$1</p></$2>", $pee);
	$pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee); // don't pee all over a tag
	$pee = preg_replace("|<p>(<li.+?)</p>|", "$1", $pee); // problem with nested lists
	$pee = preg_replace('|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $pee);
	$pee = str_replace('</blockquote></p>', '</p></blockquote>', $pee);
	$pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)!', "$1", $pee);
	$pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee);
	if ( $br ) {
		$pee = preg_replace_callback('/<(script|style).*?<\/\\1>/s', '_autop_newline_preservation_helper', $pee);
		$pee = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $pee); // optionally make line breaks
		$pee = str_replace('<WPPreserveNewline />', "\n", $pee);
	}
	$pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*<br />!', "$1", $pee);
	$pee = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $pee);
	$pee = preg_replace( "|\n</p>$|", '</p>', $pee );

	if ( !empty($pre_tags) )
		$pee = str_replace(array_keys($pre_tags), array_values($pre_tags), $pee);

	return $pee;
}

/**
 * Newline preservation help function for wpautop
 *
 * @since 3.1.0
 * @access private
 *
 * @param array $matches preg_replace_callback matches array
 * @return string
 */
function _autop_newline_preservation_helper( $matches ) {
	return str_replace("\n", "<WPPreserveNewline />", $matches[0]);
}

$ctx = new DirList();

$ctx->processForm();

?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta charset="utf-8" />
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/2.0.2/jquery.min.js"></script>


    <!-- Bootstrap suite -->
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="<?= $ctx->script_location ?>/bootstrap/css/bootstrap.min.css" />
    <!-- Optional theme -->
    <!--link rel="stylesheet" href="bootstrap/css/bootstrap-theme.min.css" /-->
    <!-- Latest compiled and minified JavaScript -->
    <script src="<?= $ctx->script_location ?>/bootstrap/js/bootstrap.min.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"></meta>
	<meta property="fb:admins" content="543888243" /></meta>
    <!-- Customizations -->
    <!--link rel="stylesheet" href="style.css" /-->

    <title <?php $ctx->getLangAttribute('title'); ?>>
		<?php
			$title=$ctx->getMetaVar('title');
			if ($title !== NULL) {
				echo strip_tags($title);
			} else {
				# compute auto-title
				echo $ctx->revertSpecialChars(strip_tags(implode(" » ",array_slice($ctx->tokenizedLocation,count($ctx->tokenizedLocation)-3,2))));
			}
			
			if (! $ctx->isFacebook) {
				echo " ♬ Index of $ctx->reducedLocation ♬ Digital K7";
			}
		?>
    </title>

 			<?php if ($notes_before=$ctx->getMetaVar('notes_before')) { ?>
				<meta name="description"    content="<?= strip_tags(wpautop($notes_before)) ?>" />
				<meta name="og:description" content="<?= strip_tags(wpautop($notes_before)) ?>" />
			<?php } ?>
   
    
    <style>
.breadcrumb > li + li::before {
	content: "»";
	font-size: 1.3em;
	color: purple;
}    

ol.breadcrumb {
	margin-bottom: 0;
	padding-left: 0;
	margin-top: 0;
	background-color: rgba(0,0,0,0); /* transparent background */
}

.breadcrumb .active a {
	color: #B3D1EA;
}

#cse-search-box {
	margin-right: .5em;
}

.page-header {
	margin-top: 5em;
}

#sidebar_wide {
	max-width: 300px;
}

#sidebar_narrow {
	max-width: 120px;
}

footer {
	background-color: lightyellow !important;
}

footer.navbar {
    margin-bottom: 0;
}

#list li .name {
	padding-left: .7em;
}

#list li {
	padding-bottom: .5em;
	padding-top: .5em;
	padding-left: .5em;
}

#list li .track, #list li .extension, #list li .separator {
	color: #B3D1EA;
}

/*
#list li .artist, #list li .song {
	color: #0066FF;
}
*/

/*
#list .popover-content .menu-item {
	margin-left: 10px;
	text-indent: -10px;
}
*/


#list li .popover-trigger {
/*	display: none; */
	color: red;
}

#list li .popover-trigger.popover-enabled {
	display: inline;
}

#list li .popover-trigger:hover {
	text-decoration: none;
}

/*
#list li:hover .popover-trigger {
	display: inline;
}
*/

#list li.active {
	background-color: #ffffdd;
	border-radius: 5px;
	border: lightgrey 1px solid;
}

#list .glyphicon-folder-open {
	color: orange;
}

#list .glyphicon-arrow-up {
	color: magenta;
}

#list .glyphicon-volume-up {
	color: blue;
}

#list .glyphicon-file {
	color: purple;
}

#related {
	margin-top: 2em;
}

#related ul {
	padding-left: 1.2em;
}

#related li a {
	color: #666;
}

#related li a:visited {
	color: #999;
}

#page_dynamics .section-title {
	color: orange;
}

#page_dynamics_form {
	display: none;
}

#page_dynamics_form #inspector {
	height: 10em;
}

#page_dynamics_form .json-output {
	height: 40em;
}
    </style>
    
    
    <meta name="google-site-verification" content="sRIqQwvRxVt59geZeLHADB2MHb5jL1k_F_yND3LFsso" />
    
  </head>
  <body>
  
  
  
	<!-- ###### HEADER AND NAVBAR ###### -->
	
    <header class="container">
      <nav class="navbar navbar-default navbar-fixed-top" role="navigation">
		<div class="container-fluid">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar-elements">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<ol class="navbar-text breadcrumb">
					<?= $ctx->breadcrumb() ?>
				</ol>
			</div>

			<div class="collapse navbar-collapse" id="navbar-elements">
				<form class="navbar-form navbar-right" id="cse-search-box" action="http://google.com/cse" role="search">
					<input type="hidden" name="cx" value="009840708878432820926:rrni3_nyqj0" />
					<input type="hidden" name="ie" value="UTF-8" />
					<input type="text" name="q" size="31" class="form-control" placeholder="Search DK7 music with Google" />
				</form>
			</div>
        </div>
      </nav>

      <hgroup id="title" class="page-header" style="margin-top: 5em">
		<h1 <?php $ctx->getLangAttribute('title') ?>><?php
			if ($title !== NULL) {
				echo $title;
			} else {
				# compute auto-title
				echo $ctx->revertSpecialChars(implode(" » ",array_slice($ctx->tokenizedLocation,count($ctx->tokenizedLocation)-3,2)));
			}
		?></h1>
      </hgroup>
    </header>
  
  
  
  
  
  
    <section id="main" class="container">
    
    
	  <!-- ###### LEFT SIDEBAR ###### -->

      <section id="sidebar_left" class="pull-left">
      	<div id="sidebar_narrow">
			<?php if ($ctx->publicSite) { ?>
			<div id="main_ad_small" class="ad">
			  <!-- DK7 Main Vertical -->
			  <ins class="adsbygoogle" style="display:inline-block;width:120px;height:600px" data-ad-client="ca-pub-6579238986403678" data-ad-slot="9336774654"></ins>
			  <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
			</div>
			<?php } ?>
        </div>
      </section>




      <section id="content_wrapper">
        
        
          <!-- ###### RIGHT SIDEBAR ###### -->
          
          <section id="sidebar_right" class="pull-right">
          	<div id="sidebar_wide">
				<?php if ($ctx->publicSite) { ?>
				<div class="container"><div class="fb-like" data-href="<?= $_SERVER['SCRIPT_URI'] ?>" data-colorscheme="light" data-layout="button_count" data-action="like" data-show-faces="false" data-send="false"></div></div>
				<?php } ?>
				<aside id="folder_image">
					<?php if ($folder_image = $ctx->folderImage()) { ?>
						<img src="<?= $folder_image ?>"  class="img-thumbnail img-responsive" />
					<?php } ?>
				</aside>
				<?php if ($ctx->publicSite) { ?>            
				<div id="main_ad_large" class="ad">
				  <!-- DK7 Main Large -->
				  <ins class="adsbygoogle" style="display:inline-block;width:300px;height:250px" data-ad-client="ca-pub-6579238986403678" data-ad-slot="6383308257"></ins>
				  <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
				</div>
				<?php } ?>
            </div>
          </section>
          
          
          
          <!-- ###### MAIN CONTENT ###### -->
          
          <article id="content">
			<?php if ($notes_before=$ctx->getMetaVar('notes_before')) { ?>
				<section id="notes_before" class="notes lead" <?php $ctx->getLangAttribute('notes_before'); ?>>
					<?= wpautop($notes_before) ?>
				</section>
			<?php } ?>
			<section id="list">
				<ul class="list-unstyled">
						<?php $ctx->file_list();?>
				</ul>
			</section>
			
			
          <!-- ###### NOTES AFTER ###### -->
						
			<?php if ($notes_after=$ctx->getMetaVar('notes_after')) { ?>
				<section id="notes_after" class="notes" <?php $ctx->getLangAttribute('notes_after'); ?>>
					<?= wpautop($notes_after) ?>
				</section>
			<?php } ?>

          <!-- ###### RELATED LINKS ###### -->

			<?php if (isset($ctx->meta['related'])) { ?>
				<section id="related">
					<h4>If you like this, you may also like…</h4>
					<ul>
						<?php $ctx->renderRelated() ?>
					</ul>
				</section>
			<?php } ?>
			
          </article>
        </div>
      </section>
    </section>


    
  <!-- ###### FOOTER ###### -->
    
    
    <footer class="navbar navbar-default navbar-bottom"><div class="container">
	<?php if ($ctx->publicSite) { ?>
	  <p id="about" class="lead">Digital K7 is a large music collection well organized and tagged. Ready to be browsed, listened and downloaded as never seen before.</p>
      <div class="row">
      	<div class="col-md-6">
			<section id="infobox_left" class="panel panel-info">
				<div class="panel-heading"><h4>Social Media</h4></div>
				<div class="panel-body">
					<p class="subtitle">Follow us on social media to get updates and musical tips</p>
					<ul>
						<li><a href="https://www.facebook.com/digitalk7" target="_new">Facebook</a></li>
						<li><a href="https://www.facebook.com/pages/K7-Digital/511554182248195" target="_new">Facebook with more Brazilian music tips</a></li>
					</ul>
				</div>
			</section>
        </div>
		<div class="col-md-6">
			<section id="infobox_center" class="panel panel-info">
				<div class="panel-heading"><h4>About Digital K7</h4></div>
				<div class="panel-body">
				  <ul>
					<li><a href="/">About</a></li>
					<li><a href="/download-how-to">How to download media from Digital K7</a></li>
					<li><a href="/organization">How the collection is organized</a></li>
				  </ul>
			  </div>
			</section>
		</div>
      </div>
      <?php } ?>
      <?php if ($ctx->publicSite===FALSE) {?>
      <p class="lead"><a href="<?php echo $ctx->publicHostEquivalentURL ?>">On public site</a></p>
      <section class="row" id="page_dynamics">
	    <h5 onclick="javascript:toogleDisplayForm()" class="section-title">Admin...</h5>
	    <div id="page_dynamics_form">
        <div class="infobox col-md-9">
          <form id="custom_link" name="custom_link" method="post" onsubmit="clearFormElements()">
            <input class="in col-md-12 form-control" type="text" id="forTitle" placeholder="Text for dynamic title" value="<?= $ctx->getMetaVar('title') ?>" name="title" />
            <br/>
            <textarea rows="5" class="in col-md-12 form-control" id="forNotesBefore" placeholder="Notes before list" name="notes_before"><?= $ctx->getMetaVar('notes_before') ?></textarea>
            <br/>
            <input class="in col-md-12 form-control" type="text" id="forHighlight" placeholder="Items to highlight e.g. 1,4,7" value="<?= implode(',',$ctx->getMetaVar('highlight')) ?>" name="highlight" />
            <br/>
            <textarea rows="2" class="in col-md-12 form-control" id="forNotesAfter" placeholder="Notes after list" name="notes_after"><?= $ctx->getMetaVar('notes_after') ?></textarea>
            <br/>
            
            <!-- ###### RELATED LINKS INPUT FORMS ###### -->
            
            <div id="related-links">
            <?= $ctx->renderRelatedForm() ?>
            </div>
            
			<br/>
            <button type="submit" class="btn btn-primary">Customize page</button>
            <br/>
            <textarea class="in col-md-12 form-control" id="inspector" placeholder="INSPECTOR"><!--?php var_dump($_SERVER); ?--></textarea>
          </form>
		</div>
		<div class="col-md-3">
			<textarea id="meta-json" class="form-control json-output" placeholder="JSON for meta.json"></textarea>
		</div>
		</div>
	  </section>
	  <?php } ?>
      <div style="clear:both"></div>
    </div></footer>
  </body>

	<?php if ($ctx->publicSite===FALSE) { ?>
    <script type="text/javascript" id="internals-js">
		function toogleDisplayForm() {
			if ($('#page_dynamics_form').css('display') == 'none') {
				$('#page_dynamics_form').css('display','block');
			} else {
				$('#page_dynamics_form').css('display','none');
			}
		}

		// Serialize input forms into JSON
		function toJSON() {
			var asString;
			var obj = new Object();
			var v;

			if ((v=$('#custom_link #forTitle').val()) !== "") {
				obj.title = new Object();
				obj.title.lang_default=v;
			}

			if ((v=$('#custom_link #forNotesBefore').val()) !== "") {
				obj.notesBefore = new Object();
				obj.notesBefore.lang_default = new Object();
				obj.notesBefore.lang_default = v;
			}

			if ((v=$('#custom_link #forNotesAfter').val()) !== "") {
				obj.notesAfter = new Object();
				obj.notesAfter.lang_default = new Object();
				obj.notesAfter.lang_default = v;
			}

			if ((v=$('#custom_link #forHighlight').val()) !== "") {
				obj.highlight = v;
			}

			$('#custom_link #related-links div.related_group').each(function(i) {
				var u=$(this).find('.uri').val();

				if (u !== "") {
					// http://stackoverflow.com/a/1961539
					obj.related = obj.related || []

					obj.related[i] = new Object();
					obj.related[i].uri=$(this).find('.uri').val();

					var t=$(this).find('.text').val();

					if (t !== "")
						obj.related[i].lang_default=$(this).find('.text').val();
				}
			});

			$('#meta-json').val(JSON.stringify(obj));
		}

		function appendRelatedLink(e) {
			var d=$(e).parents('.related_group');
			var n=d.clone();

			n.insertAfter(d);

			toJSON();
		}

		function initFormElements(par) {	
			$('#custom_link .in').blur(function(){
				toJSON();
			});
		}

	</script>
	<?php } ?>

    
    
    
    
    <script type="text/javascript" id="digitalk7-js">
		var parameters= {
			layout: 'layout2',
			ads: 'false'
		}

		function imgError(image) {
			// http://stackoverflow.com/a/12994474
			$(image).hide();
		}    


		function getURLParameters() {
			// returns a hash with what was passed on the URL (?key1=val1&key2=val2...)

			var searchString = window.location.search.substring(1)
			, params = searchString.split("&")
			, hash = {};

			if (searchString == "") return {};
			for (var i = 0; i < params.length; i++) {
				var val = params[i].split("=");
				hash[unescape(val[0])] = decodeURIComponent(val[1]);
			}
			return hash;
		}

		function handleAds(par) {
			if (par['ads'] == 'false') {
		//       			var w1=parseInt($('#main_ad_small').css('width').replace("px",""));
		//       			var w2=parseInt($('#content_wrapper').css('width').replace("px",""));
		
				$('.ad>ins').each(function() {
					$(this).parent().hide();
					$(this).remove();
				});
		
				// console.log("w1: " + w1);
				// console.log("w2: " + w2);
		
		//       			$('#content_wrapper').css('width',w1+w2 + "px");
			}
		}

		function enablePopovers() {
			$('li .popover-trigger').click(function() {
				var selected=this;

				if ($(selected).hasClass('popover-enabled')) {
				//	$(selected).popover('hide');
					$(selected).popover('destroy');
					$(selected).removeClass('popover-enabled');
				//	$(selected).removeAttr('data-original-title');
				//	$(selected).removeAttr('title');
				} else {
					// Delete any other active popovers 
				//	$('li .popover-trigger.popover-enabled').popover('hide');
					$('li .popover-trigger.popover-enabled').popover('destroy');
					$('li .popover-trigger.popover-enabled').removeClass('popover-enabled');

					var index=$(selected).parent('li').data('index');
					var target=$(selected).parent('li').data('target');
					var name=$(selected).parent('li').data('name');
					var mime=$(selected).parent('li').data('mimetype');
					var type=$(selected).parent('li').data('type');

					var menu=$('<ul/>').addClass('menu list-unstyled');

					// Menu item: plain file download link
					if (type=='file')
						menu.append($('<li/>').append($('<a/>')
						.addClass('menu-item')
						.attr('href',target)
						.append($('<span/>')
							.addClass('glyphicon glyphicon-cloud-download'))
						.append(' download file')));

					// Menu item: plain file play
					if (typeof(mime) !== 'undefined' && mime.indexOf('audio',0)==0)
						menu.append($('<li/>').append($('<a/>')
						.addClass('menu-item')
						.attr('href', target + "\?play")
						.append($('<span/>')
							.addClass('glyphicon glyphicon-play'))
						.append(' play file')));

					// Menu item: highlight item
					menu.append($('<li/>').append($('<a/>')
						.addClass('menu-item')
						.attr('href', "\?highlight=" + window.btoa(index) + "#main-list-" + (parseInt(index)-1).toString())
						.append($('<span/>')
							.addClass('glyphicon glyphicon-fire'))
						.append(' highlight this item')));

					// Menu item: field with HTML with pretty text and link to target
					menu.append($('<li/>').append($('<span/>')
						.append($('<span/>')
							.addClass('glyphicon glyphicon-share-alt')
							.attr('title','Formatted link to file'))
						.append($('<input/>')
							.attr('type', 'text')
							.attr('value', '<a href="' + decodeURI(qualifyURL(target)) + '">' + URLtoPrettyPath(qualifyURL(target)) + '</a>')
							.focus(function() {
								$(this).select();
							})
						)));

					// Menu item: field with HTML with pretty text and link to parent with highlighted target
					menu.append($('<li/>').append($('<span/>')
						.append($('<span/>')
							.addClass('glyphicon glyphicon-share')
							.attr('title','Formatted highlighted share'))
						.append($('<input/>')
							.attr('type', 'text')
							.attr('value', '<a href="' + decodeURI(qualifyURL(location.href + "\?highlight=" + window.btoa(index) + "#main-list-" + (parseInt(index)-1).toString())) + '">' + URLtoPrettyPath(qualifyURL(target)) + '</a>')
							.focus(function() {
								$(this).select();
							})
						)));

					// Menu item: Fully encoded URL, including the bullet char
					menu.append($('<li/>').append($('<span/>')
						.append($('<span/>')
							.addClass('glyphicon glyphicon-share-alt')
							.attr('title','Fully encoded URL, including bullet char'))
						.append($('<input/>')
							.attr('type', 'text')
							.attr('value', encodeURI(decodeURI(qualifyURL(target))))
							.focus(function() {
								$(this).select();
							})
						)));


					$(selected).popover({content: menu, html: true, trigger: 'manual'}).popover('show');
					$(selected).addClass('popover-enabled');
				}
			});
		}

        // http://james.padolsey.com/javascript/getting-a-fully-qualified-url/#comment-3432
        function qualifyURL(url) {
            var a = document.createElement('a');
            var protoRE = new RegExp('^' + location.protocol + '//');

            a.href = url;
            if (protoRE.test(a.href)) {
                // IE7, FF, Op, Sf
                return a.href;
            } else {
                // IE6
                var img = document.createElement('img');
                img.src = url;
                return img.src;
            }
        }
        
        function URLtoPrettyPath(url) {
            var path = decodeURI(url);
            var pathPrefixRE = new RegExp('^' + '<?= $ctx->rootAlias ?>' + '/')
            
            // Remove "protocol://host.name.com:port"
            //path.replace(location.protocol+'//'+location.hostname+(location.port ? ':'+location.port: ''), '');
            path=path.replace(location.origin, '');
            path=path.replace(pathPrefixRE, '');
            
            // URL's undesired parts are gone, now prettify
            path=path.replace(new RegExp('/', 'g'),' ▸ ');
            
            return path;
        }
        
		/*
		function animateHighlights(par) {
			$('.highlight').animate(
				{backgroundColor: "blue"},
				2000
			);
		}
		*/



		$(document).ready(function() {
			$.extend(parameters,getURLParameters());

			handleAds(parameters);
			enablePopovers(parameters);
		//			reworkFileList(parameters);
		//            setLayout(parameters);
		//			setBreadCrumb(parameters);
		//			setTitle(parameters);

			if (typeof(initFormElements) === 'function') {
				initFormElements(parameters);
			}

		//			fixContainersHeight(parameters);
		//			animateHighlights(parameters);
		});
		-->
    </script>


	<?php if ($ctx->publicSite) { ?>
	<script id="google_analytics_block">
			(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
			(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
			m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
			})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

			ga('create', 'UA-43018483-1', 'digitalk7.com');
			ga('send', 'pageview');
	</script>

	<div id="fb-root"></div>
	<script>(function(d, s, id) {
	  var js, fjs = d.getElementsByTagName(s)[0];
	  if (d.getElementById(id)) return;
	  js = d.createElement(s); js.id = id;
	  js.src = "//connect.facebook.net/pt_BR/all.js#xfbml=1";
	  fjs.parentNode.insertBefore(js, fjs);
	}(document, 'script', 'facebook-jssdk'));
	</script>	
	<?php }?>


</html>
