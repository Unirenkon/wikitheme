<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// html.php
// Copyright
//   2002-2016 PukiWiki Development Team
//   2001-2002 Originally written by yu-ji
// License: GPL v2 or (at your option) any later version
//
// HTML-publishing related functions

// Show page-content
function catbody($title, $page, $body)
{
	global $script, $vars, $arg, $defaultpage, $whatsnew, $help_page, $hr;
	global $attach_link, $related_link, $cantedit, $function_freeze;
	global $search_word_color, $_msg_word, $foot_explain, $note_hr, $head_tags;
	global $javascript, $nofollow;
	global $_LANG, $_LINK, $_IMAGE;
	global $auth_type, $auth_user;

	global $pkwk_dtd;     // XHTML 1.1, XHTML1.0, HTML 4.01 Transitional...
	global $page_title;   // Title of this site
	global $do_backup;    // Do backup or not
	global $modifier;     // Site administrator's  web page
	global $modifierlink; // Site administrator's name

	$enable_login = false;
	$enable_logout = false;
	if (AUTH_TYPE_FORM === $auth_type || AUTH_TYPE_EXTERNAL === $auth_type) {
		if ($auth_user) {
			$enable_logout = true;
		} else {
			$enable_login = true;
		}
	} else if (AUTH_TYPE_BASIC === $auth_type) {
		if ($auth_user) {
			$enable_logout = true;
		}
	}
	if (! file_exists(SKIN_FILE) || ! is_readable(SKIN_FILE))
		die_message('SKIN_FILE is not found');

	$_LINK = $_IMAGE = array();

	// Add JavaScript header when ...
	if (! PKWK_ALLOW_JAVASCRIPT) unset($javascript);

	$_page  = isset($vars['page']) ? $vars['page'] : '';
	$r_page = pagename_urlencode($_page);

	// Canonical URL
	$canonical_url = $script;
	if ($_page !== $defaultpage) {
		$canonical_url = $script . '?' . $r_page;
	}

	// Set $_LINK for skin
	$_LINK['add']      = "$script?cmd=add&amp;page=$r_page";
	$_LINK['backup']   = "$script?cmd=backup&amp;page=$r_page";
	$_LINK['copy']     = "$script?plugin=template&amp;refer=$r_page";
	$_LINK['diff']     = "$script?cmd=diff&amp;page=$r_page";
	$_LINK['edit']     = "$script?cmd=edit&amp;page=$r_page";
	$_LINK['filelist'] = "$script?cmd=filelist";
	$_LINK['freeze']   = "$script?cmd=freeze&amp;page=$r_page";
	$_LINK['help']     = "$script?" . pagename_urlencode($help_page);
	$_LINK['list']     = "$script?cmd=list";
	$_LINK['new']      = "$script?plugin=newpage&amp;refer=$r_page";
	$_LINK['rdf']      = "$script?cmd=rss&amp;ver=1.0";
	$_LINK['recent']   = "$script?" . pagename_urlencode($whatsnew);
	$_LINK['reload']   = $canonical_url;
	$_LINK['rename']   = "$script?plugin=rename&amp;refer=$r_page";
	$_LINK['rss']      = "$script?cmd=rss";
	$_LINK['rss10']    = "$script?cmd=rss&amp;ver=1.0"; // Same as 'rdf'
	$_LINK['rss20']    = "$script?cmd=rss&amp;ver=2.0";
	$_LINK['search']   = "$script?cmd=search";
	$_LINK['top']      = "$script?" . pagename_urlencode($defaultpage);
	$_LINK['unfreeze'] = "$script?cmd=unfreeze&amp;page=$r_page";
	$_LINK['upload']   = "$script?plugin=attach&amp;pcmd=upload&amp;page=$r_page";
	$login_link = "#LOGIN_ERROR"; // dummy link that is not used
	switch ($auth_type) {
		case AUTH_TYPE_FORM:
			$login_link = "$script?plugin=loginform&pcmd=login&page=$r_page";
			break;
		case AUTH_TYPE_EXTERNAL:
			$login_link = get_auth_external_login_url($_page, $_LINK['reload']);
			break;
	}
	$_LINK['login']    = htmlsc($login_link);
	$_LINK['logout']   = "$script?plugin=loginform&amp;pcmd=logout&amp;page=$r_page";

	// Compat: Skins for 1.4.4 and before
	$link_add       = & $_LINK['add'];
	$link_new       = & $_LINK['new'];	// New!
	$link_edit      = & $_LINK['edit'];
	$link_diff      = & $_LINK['diff'];
	$link_top       = & $_LINK['top'];
	$link_list      = & $_LINK['list'];
	$link_filelist  = & $_LINK['filelist'];
	$link_search    = & $_LINK['search'];
	$link_whatsnew  = & $_LINK['recent'];
	$link_backup    = & $_LINK['backup'];
	$link_help      = & $_LINK['help'];
	$link_trackback = ''; // Removed (compat)
	$link_rdf       = & $_LINK['rdf'];		// New!
	$link_rss       = & $_LINK['rss'];
	$link_rss10     = & $_LINK['rss10'];		// New!
	$link_rss20     = & $_LINK['rss20'];		// New!
	$link_freeze    = & $_LINK['freeze'];
	$link_unfreeze  = & $_LINK['unfreeze'];
	$link_upload    = & $_LINK['upload'];
	$link_template  = & $_LINK['copy'];
	$link_refer     = ''; // Removed (compat)
	$link_rename    = & $_LINK['rename'];

	// Init flags
	$is_page = (is_pagename($_page) && ! arg_check('backup') && $_page != $whatsnew);
	$is_read = (arg_check('read') && is_page($_page));
	$is_freeze = is_freeze($_page);

	// Last modification date (string) of the page
	$lastmodified = $is_read ?  format_date(get_filetime($_page)) .
		' ' . get_pg_passage($_page, FALSE) : '';

	// List of attached files to the page
	$attaches = ($attach_link && $is_read && exist_plugin_action('attach')) ?
		attach_filelist() : '';

	// List of related pages
	$related  = ($related_link && $is_read) ? make_related($_page) : '';

	// List of footnotes
	ksort($foot_explain, SORT_NUMERIC);
	$notes = ! empty($foot_explain) ? $note_hr . join("\n", $foot_explain) : '';

	// Tags will be inserted into <head></head>
	$head_tag = ! empty($head_tags) ? join("\n", $head_tags) ."\n" : '';

	// 1.3.x compat
	// Last modification date (UNIX timestamp) of the page
	$fmt = $is_read ? get_filetime($_page) + LOCALZONE : 0;

	// Output nofollow / noindex regardless os skin file
	if (!$is_read || $nofollow) {
		if (!headers_sent()) {
			header("X-Robots-Tag: noindex,nofollow");
		}
	}

	// Send Canonical URL for Search Engine Optimization
	if ($is_read && !headers_sent()) {
		header("Link: <$canonical_url>; rel=\"canonical\"");
	}

	// Search words
	if ($search_word_color && isset($vars['word'])) {
		$body = '<div class="small">' . $_msg_word . htmlsc($vars['word']) .
			'</div>' . $hr . "\n" . $body;

		// BugTrack2/106: Only variables can be passed by reference from PHP 5.0.5
		// with array_splice(), array_flip()
		$words = preg_split('/\s+/', $vars['word'], -1, PREG_SPLIT_NO_EMPTY);
		$words = array_splice($words, 0, 10); // Max: 10 words
		$words = array_flip($words);

		$keys = array();
		foreach ($words as $word=>$id) $keys[$word] = strlen($word);
		arsort($keys, SORT_NUMERIC);
		$keys = get_search_words(array_keys($keys), TRUE);
		$id = 0;
		foreach ($keys as $key=>$pattern) {
			$s_key    = htmlsc($key);
			$pattern  = '/' .
				'<textarea[^>]*>.*?<\/textarea>' .	// Ignore textareas
				'|' . '<[^>]*>' .			// Ignore tags
				'|' . '&[^;]+;' .			// Ignore entities
				'|' . '(' . $pattern . ')' .		// $matches[1]: Regex for a search word
				'/sS';
			$decorate_Nth_word = create_function(
				'$matches',
				'return (isset($matches[1])) ? ' .
					'\'<strong class="word' .
						$id .
					'">\' . $matches[1] . \'</strong>\' : ' .
					'$matches[0];'
			);
			$body  = preg_replace_callback($pattern, $decorate_Nth_word, $body);
			$notes = preg_replace_callback($pattern, $decorate_Nth_word, $notes);
			++$id;
		}
	}

	// Compat: 'HTML convert time' without time about MenuBar and skin
	$taketime = elapsedtime();

	require(SKIN_FILE);
}

// Show 'edit' form
function edit_form($page, $postdata, $digest = FALSE, $b_template = TRUE)
{
	global $script, $vars, $rows, $cols, $hr, $function_freeze;
	global $_btn_preview, $_btn_repreview, $_btn_update, $_btn_cancel, $_msg_help;
	global $whatsnew, $_btn_template, $_btn_load, $load_template_func;
	global $notimeupdate;
	global $_title_list, $_label_template_pages;

	// Newly generate $digest or not
	if ($digest === FALSE) $digest = md5(join('', get_source($page)));

	$refer = $template = '';

 	// Add plugin
	$addtag = $add_top = '';
	if(isset($vars['add'])) {
		global $_btn_addtop;
		$addtag  = '<input type="hidden" name="add"    value="true" />';
		$add_top = isset($vars['add_top']) ? ' checked="checked"' : '';
		$add_top = '<input type="checkbox" name="add_top" ' .
			'id="_edit_form_add_top" value="true"' . $add_top . ' />' . "\n" .
			'  <label for="_edit_form_add_top">' .
				'<span class="small">' . $_btn_addtop . '</span>' .
			'</label>';
	}

	if($load_template_func && $b_template) {
		$tpage_names = array(); // Pages marked as template
		$template_page = ':config/Templates';
		$page_max = 100;
		foreach(get_source($template_page) as $_templates) {
			$m = array();
			if (! preg_match('#\-\s*\[\[([^\[\]]+)\]\]#', $_templates, $m)) continue;
			$tpage = preg_replace('#^./#', "$template_page/", $m[1]);
			if (! is_page($tpage)) continue;
			$tpage_names[] = $tpage;
		}
		$page_names = array();
		foreach(get_existpages() as $_page) {
			if ($_page == $whatsnew || check_non_list($_page))
				continue;
			if (preg_match('/template/i', $_page)) {
				$tpage_names[] = $_page;
			} else {
				if (count($page_names) >= $page_max) continue;
				$page_names[] = $_page;
			}
		}
		$tpage_names2 = array_values(array_unique($tpage_names));
		natcasesort($tpage_names2);
		natcasesort($page_names);
		$tpages = array(); // Template pages
		$npages = array(); // Normal pages
		foreach($tpage_names2 as $p) {
			$ps = htmlsc($p);
			$tpages[] = '   <option value="' . $ps . '">' . $ps . '</option>';
		}
		foreach($page_names as $p) {
			$ps = htmlsc($p);
			$npages[] = '   <option value="' . $ps . '">' . $ps . '</option>';
		}
		if (count($page_names) === $page_max) {
			$npages[] = '   <option value="">...</option>';
		}
		$s_tpages  = join("\n", $tpages);
		$s_npages  = join("\n", $npages);
		$template = <<<EOD
  <select name="template_page">
   <option value="">-- $_btn_template --</option>
   <optgroup label="$_label_template_pages">
$s_tpages
   </optgroup>
   <optgroup label="$_title_list">
$s_npages
   </optgroup>
  </select>
  <input type="submit" name="template" value="$_btn_load" accesskey="r" />
  <br />
EOD;

		if (isset($vars['refer']) && $vars['refer'] != '')
			$refer = '[[' . strip_bracket($vars['refer']) . ']]' . "\n\n";
	}

	$r_page      = rawurlencode($page);
	$s_page      = htmlsc($page);
	$s_digest    = htmlsc($digest);
	$s_postdata  = htmlsc($refer . $postdata);
	$s_original  = isset($vars['original']) ? htmlsc($vars['original']) : $s_postdata;
	$b_preview   = isset($vars['preview']); // TRUE when preview
	$btn_preview = $b_preview ? $_btn_repreview : $_btn_preview;

	// Checkbox 'do not change timestamp'
	$add_notimestamp = '';
	if ($notimeupdate != 0) {
		global $_btn_notchangetimestamp;
		$checked_time = isset($vars['notimestamp']) ? ' checked="checked"' : '';
		// Only for administrator
		if ($notimeupdate == 2) {
			$add_notimestamp = '   ' .
				'<input type="password" name="pass" size="12" />' . "\n";
		}
		$add_notimestamp = '<input type="checkbox" name="notimestamp" ' .
			'id="_edit_form_notimestamp" value="true"' . $checked_time . ' />' . "\n" .
			'   ' . '<label for="_edit_form_notimestamp"><span class="small">' .
			$_btn_notchangetimestamp . '</span></label>' . "\n" .
			$add_notimestamp .
			'&nbsp;';
	}



	// 'margin-bottom', 'float:left', and 'margin-top'
	// are for layout of 'cancel button'
	$body = <<<EOD
<div class="edit_form">
 <form action="$script" method="post" style="margin-bottom:0px;">
$template
  $addtag
  <input type="hidden" name="cmd"    value="edit" />
  <input type="hidden" name="page"   value="$s_page" />
  <input type="hidden" name="digest" value="$s_digest" />
  <textarea name="msg" rows="$rows" cols="$cols">$s_postdata</textarea>
  <br />
  <div style="float:left;">
   <input type="submit" name="preview" value="$btn_preview" accesskey="p" />
   <input type="submit" name="write"   value="$_btn_update" accesskey="s" />
   $add_top
   $add_notimestamp
  </div>
  <textarea name="original" rows="1" cols="1" style="display:none">$s_original</textarea>
 </form>
 <form action="$script" method="post" style="margin-top:0px;">
  <input type="hidden" name="cmd"    value="edit" />
  <input type="hidden" name="page"   value="$s_page" />
  <input type="submit" name="cancel" value="$_btn_cancel" accesskey="c" />
 </form>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.2.0/jquery.js"></script>
<script>

// from http://qiita.com/volpe28v@github/items/dc100c75c2371bb82a3b
$("textarea[name='msg']").on('paste', function(event){
  // event ?????????????????????????????????????????????????????????
  var items = (event.clipboardData || event.originalEvent.clipboardData).items; // ???????????????
  for (var i = 0 ; i < items.length ; i++) {
    var item = items[i];
    if (item.type.indexOf("image") != -1) {
      // ???????????????????????????????????????????????????
      var image = item.getAsFile();
      upload_file_with_ajax(image);
    }
  }
});

// ??????????????????????????????
function upload_file_with_ajax(file){
    var img_name = window.prompt("Image file name", "noname.png");
    if(img_name==null) return;

    var formData = new FormData();

    // from http://hakuhin.jp/js/form_data.html#FORM_DATA_02
    formData.append("attach_file", file, img_name);
    formData.append("encode_hint", "???");
    formData.append("plugin", "attach");
    formData.append("pcmd", "post");
    formData.append("refer", "$s_page");

    $.ajax('$script' , {
      type: 'POST',
      contentType: false,
      processData: false,
      data: formData,
      error: function() {
        // ?????????????????????????????????
        beep();
      },
      success: function(res) {
        insertAtCaret($("textarea[name='msg']"),
          "&ref("+img_name+");");
      }
    });
}

// textarea ??????????????????????????????????????????
// from http://d.hatena.ne.jp/spitfire_tree/20131209/1386575758
function insertAtCaret(target, str) {
  var obj = $(target);
  obj.focus();
  if(navigator.userAgent.match(/MSIE/)) {
    var r = document.selection.createRange();
    r.text = str;
    r.select();
  } else {
    var s = obj.val();
    var p = obj.get(0).selectionStart;
    var np = p + str.length;
    obj.val(s.substr(0, p) + str + s.substr(p));
    obj.get(0).setSelectionRange(np, np);
  }
}

// ????????????????????????
// from http://qiita.com/isseium/items/12b215b6eab26acd2afe
function beep()
{
  new Audio(
    "data:audio/wav;base64,"+
    "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"+
    "U291bmQgRm9yZ2UgNC41AA==").play();
}

</script>

EOD;

	if (isset($vars['help'])) {
		$body .= $hr . catrule();
	} else {
		$body .= '<ul><li><a href="' .
			$script . '?cmd=edit&amp;help=true&amp;page=' . $r_page .
			'">' . $_msg_help . '</a></li></ul>';
	}

	return $body;
}

// Related pages
function make_related($page, $tag = '')
{
	global $script, $vars, $rule_related_str, $related_str;
	global $_ul_left_margin, $_ul_margin, $_list_pad_str;

	$links = links_get_related($page);

	if ($tag) {
		ksort($links, SORT_STRING);		// Page name, alphabetical order
	} else {
		arsort($links, SORT_NUMERIC);	// Last modified date, newer
	}

	$_links = array();
	foreach ($links as $page=>$lastmod) {
		if (check_non_list($page)) continue;

		$r_page   = pagename_urlencode($page);
		$s_page   = htmlsc($page);
		$passage  = get_passage($lastmod);
		$_links[] = $tag ?
			'<a href="' . $script . '?' . $r_page . '" title="' .
			$s_page . ' ' . $passage . '">' . $s_page . '</a>' :
			'<a href="' . $script . '?' . $r_page . '">' .
			$s_page . '</a>' . $passage;
	}
	if (empty($_links)) return ''; // Nothing

	if ($tag == 'p') { // From the line-head
		$margin = $_ul_left_margin + $_ul_margin;
		$style  = sprintf($_list_pad_str, 1, $margin, $margin);
		$retval =  "\n" . '<ul' . $style . '>' . "\n" .
			'<li>' . join($rule_related_str, $_links) . '</li>' . "\n" .
			'</ul>' . "\n";
	} else if ($tag) {
		$retval = join($rule_related_str, $_links);
	} else {
		$retval = join($related_str, $_links);
	}

	return $retval;
}

// User-defined rules (convert without replacing source)
function make_line_rules($str)
{
	global $line_rules;
	static $pattern, $replace;

	if (! isset($pattern)) {
		$pattern = array_map(create_function('$a',
			'return \'/\' . $a . \'/\';'), array_keys($line_rules));
		$replace = array_values($line_rules);
		unset($line_rules);
	}

	return preg_replace($pattern, $replace, $str);
}

// Remove all HTML tags(or just anchor tags), and WikiName-speific decorations
function strip_htmltag($str, $all = TRUE)
{
	global $_symbol_noexists;
	static $noexists_pattern;

	if (! isset($noexists_pattern))
		$noexists_pattern = '#<span class="noexists">([^<]*)<a[^>]+>' .
			preg_quote($_symbol_noexists, '#') . '</a></span>#';

	// Strip Dagnling-Link decoration (Tags and "$_symbol_noexists")
	$str = preg_replace($noexists_pattern, '$1', $str);

	if ($all) {
		// All other HTML tags
		return preg_replace('#<[^>]+>#',        '', $str);
	} else {
		// All other anchor-tags only
		return preg_replace('#<a[^>]+>|</a>#i', '', $str);
	}
}

// Remove AutoLink marker with AutLink itself
function strip_autolink($str)
{
	return preg_replace('#<!--autolink--><a [^>]+>|</a><!--/autolink-->#', '', $str);
}

// Make a backlink. searching-link of the page name, by the page name, for the page name
function make_search($page)
{
	global $script;

	$s_page = htmlsc($page);
	$r_page = rawurlencode($page);

	return '<a href="' . $script . '?plugin=related&amp;page=' . $r_page .
		'">' . $s_page . '</a> ';
}

// Make heading string (remove heading-related decorations from Wiki text)
function make_heading(& $str, $strip = TRUE)
{
	global $NotePattern;

	// Cut fixed-heading anchors
	$id = '';
	$matches = array();
	if (preg_match('/^(\*{0,3})(.*?)\[#([A-Za-z][\w-]+)\](.*?)$/m', $str, $matches)) {
		$str = $matches[2] . $matches[4];
		$id  = & $matches[3];
	} else {
		$str = preg_replace('/^\*{0,3}/', '', $str);
	}

	// Cut footnotes and tags
	if ($strip === TRUE)
		$str = strip_htmltag(make_link(preg_replace($NotePattern, '', $str)));

	return $id;
}

// Separate a page-name(or URL or null string) and an anchor
// (last one standing) without sharp
function anchor_explode($page, $strict_editable = FALSE)
{
	$pos = strrpos($page, '#');
	if ($pos === FALSE) return array($page, '', FALSE);

	// Ignore the last sharp letter
	if ($pos + 1 == strlen($page)) {
		$pos = strpos(substr($page, $pos + 1), '#');
		if ($pos === FALSE) return array($page, '', FALSE);
	}

	$s_page = substr($page, 0, $pos);
	$anchor = substr($page, $pos + 1);

	if($strict_editable === TRUE &&  preg_match('/^[a-z][a-f0-9]{7}$/', $anchor)) {
		return array ($s_page, $anchor, TRUE); // Seems fixed-anchor
	} else {
		return array ($s_page, $anchor, FALSE);
	}
}

// Check HTTP header()s were sent already, or
// there're blank lines or something out of php blocks
function pkwk_headers_sent()
{
	if (PKWK_OPTIMISE) return;

	$file = $line = '';
	if (version_compare(PHP_VERSION, '4.3.0', '>=')) {
		if (headers_sent($file, $line))
		    die('Headers already sent at ' .
		    	htmlsc($file) .
			' line ' . $line . '.');
	} else {
		if (headers_sent())
			die('Headers already sent.');
	}
}

// Output common HTTP headers
function pkwk_common_headers()
{
	if (! PKWK_OPTIMISE) pkwk_headers_sent();

	if(defined('PKWK_ZLIB_LOADABLE_MODULE')) {
		$matches = array();
		if(ini_get('zlib.output_compression') &&
		    preg_match('/\b(gzip|deflate)\b/i', $_SERVER['HTTP_ACCEPT_ENCODING'], $matches)) {
		    	// Bug #29350 output_compression compresses everything _without header_ as loadable module
		    	// http://bugs.php.net/bug.php?id=29350
			header('Content-Encoding: ' . $matches[1]);
			header('Vary: Accept-Encoding');
		}
	}
}

// DTD definitions
define('PKWK_DTD_XHTML_1_1',              17); // Strict only
define('PKWK_DTD_XHTML_1_0',              16); // Strict
define('PKWK_DTD_XHTML_1_0_STRICT',       16);
define('PKWK_DTD_XHTML_1_0_TRANSITIONAL', 15);
define('PKWK_DTD_XHTML_1_0_FRAMESET',     14);
define('PKWK_DTD_HTML_4_01',               3); // Strict
define('PKWK_DTD_HTML_4_01_STRICT',        3);
define('PKWK_DTD_HTML_4_01_TRANSITIONAL',  2);
define('PKWK_DTD_HTML_4_01_FRAMESET',      1);

define('PKWK_DTD_TYPE_XHTML',  1);
define('PKWK_DTD_TYPE_HTML',   0);

// Output HTML DTD, <html> start tag. Return content-type.
function pkwk_output_dtd($pkwk_dtd = PKWK_DTD_XHTML_1_1, $charset = CONTENT_CHARSET)
{
	static $called;
	if (isset($called)) die('pkwk_output_dtd() already called. Why?');
	$called = TRUE;

	$type = PKWK_DTD_TYPE_XHTML;
	$option = '';
	switch($pkwk_dtd){
	case PKWK_DTD_XHTML_1_1             :
		$version = '1.1' ;
		$dtd     = 'http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd';
		break;
	case PKWK_DTD_XHTML_1_0_STRICT      :
		$version = '1.0' ;
		$option  = 'Strict';
		$dtd     = 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd';
		break;
	case PKWK_DTD_XHTML_1_0_TRANSITIONAL:
		$version = '1.0' ;
		$option  = 'Transitional';
		$dtd     = 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd';
		break;

	case PKWK_DTD_HTML_4_01_STRICT      :
		$type    = PKWK_DTD_TYPE_HTML;
		$version = '4.01';
		$dtd     = 'http://www.w3.org/TR/html4/strict.dtd';
		break;
	case PKWK_DTD_HTML_4_01_TRANSITIONAL:
		$type    = PKWK_DTD_TYPE_HTML;
		$version = '4.01';
		$option  = 'Transitional';
		$dtd     = 'http://www.w3.org/TR/html4/loose.dtd';
		break;

	default: die('DTD not specified or invalid DTD');
		break;
	}

	$charset = htmlsc($charset);

	// Output XML or not
	if ($type == PKWK_DTD_TYPE_XHTML) echo '<?xml version="1.0" encoding="' . $charset . '" ?>' . "\n";

	// Output doctype
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD ' .
		($type == PKWK_DTD_TYPE_XHTML ? 'XHTML' : 'HTML') . ' ' .
		$version .
		($option != '' ? ' ' . $option : '') .
		'//EN" "' .
		$dtd .
		'">' . "\n";

	// Output <html> start tag
	echo '<html';
	if ($type == PKWK_DTD_TYPE_XHTML) {
		echo ' xmlns="http://www.w3.org/1999/xhtml"'; // dir="ltr" /* LeftToRight */
		echo ' xml:lang="' . LANG . '"';
		if ($version == '1.0') echo ' lang="' . LANG . '"'; // Only XHTML 1.0
	} else {
		echo ' lang="' . LANG . '"'; // HTML
	}
	echo '>' . "\n"; // <html>

	// Return content-type (with MIME type)
	if ($type == PKWK_DTD_TYPE_XHTML) {
		// NOTE: XHTML 1.1 browser will ignore http-equiv
		return '<meta http-equiv="content-type" content="application/xhtml+xml; charset=' . $charset . '" />' . "\n";
	} else {
		return '<meta http-equiv="content-type" content="text/html; charset=' . $charset . '" />' . "\n";
	}
}
