<?php
/**
 * WikWiki - simple wiki in one PHP file. http://smasty.net/wikwiki
 * Copyright (c) 2011 Martin Srank, http://smasty.net
 *
 * Licensed under the terms and conditions of
 * the MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

define('PAGE_TITLE', 'WikWiki');
define('BASE_PAGE', 'Home Page');
define('FOOTER_TEXT', 'Copyright %Y Martin Srank. | Powered by <a href="https://github.com/smasty/WikWiki">WikWiki</a>.');

@set_magic_quotes_runtime(false);

if(!@file_exists(dirname(__FILE__) . '/wikdata') || !@is_writable(dirname(__FILE__) . '/wikdata')){
    $ok = @mkdir(dirname(__FILE__) . '/wikdata');
    if(!$ok){
        die('WikWiki cannot access the data directory ./wikdata/.
             Please create the directory and make it writeable by PHP.');
    }
}

$msg = '';

// Instantiate Texy parser.
require dirname(__FILE__) . '/texy.min.php';
$texy = new Texy();
$texy->encoding = 'utf-8';
$texy->headingModule->top = 2;
$texy->headingModule->generateID = true;
$texy->allowed['image'] = FALSE;
$texy->registerLinePattern('parseWikiLinks', '~\[([^|\]]+)(?:\s*\|\s*([^\]]+)\s*)?\]~', 'wikilinks');


// init path
$page = parseQueryString(@$_SERVER['QUERY_STRING']);
if(empty($_GET)){
    $page = titleToId(BASE_PAGE);
}


// Save content.
if(!empty($_POST)){
    if(!savePageContent($_POST)){
        $msg = 'Edit failed. Please, try again.';
    }
}


// Edit/create page
if(array_key_exists('edit', $_GET)){
    $title = idToTitle($_GET['edit']);
    printHeader(!$title ? "Create new page" : "Edit page '$title'");
    printEdit($title);
    printFooter($title);
    exit;
}


// Backlinks
elseif(isset($_GET['backlinks']) && pageExists($_GET['backlinks'])){
    $title = idToTitle($_GET['backlinks']);
    printHeader("Backlinks for '$title'");
    printBacklinks($title);
    printFooter($title);
    exit;
}


elseif(isset($_GET['recent'])){
    $count = $_GET['recent'];
    if(!is_numeric($count)){
        $count = 10;
    }
    printHeader("$count Most Recent Changes");
    printRecentChanges($count);
    printFooter();
    exit;
}


// Show page
elseif($page){
    $title = idToTitle($page);
    printHeader($title);
    printContent($title);
    printFooter($title);
    exit;
}

else{
    header('Location: ./?Special:NotFound');
    exit;
}



/**
 * Get page ID form HTTP query-string.
 * @param string $queryString
 * @return strng|false
 */
function parseQueryString($queryString){
    return preg_match('~\w+=.*~', $queryString) ? false : $queryString;
}


/**
 * Texy parser for Wiki links syntax.
 * @param TexyParser $parser
 * @param array $matches
 * @param string $name
 * @return TexyHtml|string
 */
function parseWikiLinks($parser, $matches, $name){

    $page = trim($matches[1]);
    $id = titleToId($matches[1]);

    $el = TexyHtml::el('a')
        ->href("?$id")
        ->setText(isset($matches[2]) ? $matches[2] : $page);

    if(!pageExists($page)){
        $el->class = 'new';
        $el->title = "Create page '$page'";
        $el->href("?edit=$id");
    }
    return $el;
}


/**
 * Convert page title to ID.
 * @param string $title
 * @return string
 */
function titleToId($title){
    return preg_replace('~\s+~i', '_', trim($title));
}


/**
 * Convert ID to page title.
 * @param string $id
 * @return string
 */
function idToTitle($id){
    return str_replace('_', ' ', $id);
}


/**
 * Print HTML header.
 * @param string $page
 * @return void
 */
function printHeader($page = BASE_PAGE){
    global $msg;
    $message = $msg ? "<div class=\"msg\">$msg</div>" : '';
    $html_title = "$page | " . PAGE_TITLE;

    echo <<<PAGE_HEAD
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>$html_title</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
  <div id="container">
    <div id="main">
    $message
    <h1>$page</h1>
    <div id="content">
PAGE_HEAD;
}


/**
 * Print HTML footer.
 * @param string $page
 * @return void
 */
function printFooter($page = BASE_PAGE){
    $sidebar = getSidebar($page);
    $footer = strftime(FOOTER_TEXT);
    echo <<<PAGE_FOOT
      </div>
    </div>
    <div id="sidebar">
    $sidebar
    </div>
    <div id="footer">
    $footer
    </div>
  </div>
</body>
</html>
PAGE_FOOT;
}


/**
 * Generate HTML sidebar.
 * @param string $page
 * @return void
 */
function getSidebar($page = BASE_PAGE){
    $title = PAGE_TITLE;
    $id = titleToId($page);
    $mod = 'not yet.';
    $toc = $bl = '';
    if(pageExists($page)){
        $mod = date('d.m.Y, H:i:s', filemtime(getFilePath($page)));
        $toc = generateToc();
        $bl = "<li class=\"backlinks\"><a href=\"./?backlinks=$id\">Backlinks</a></li>";
    }

    return <<<PAGE_SIDEBAR
<p id="title"><a href="./">$title</a></p>

<ul class="sidebar-list">
  <li class="edit-link"><a href="./?edit=$id">Edit page</a></li>
  $bl
  <li class="modified">Last modified: <em>$mod</em></li>
  <li class="create-new-link"><a href="./?edit=">Create new page</a></li>
  <li class="recent-changes-link"><a href="./?recent=10">Recent changes</a></li>
</ul>
$toc
PAGE_SIDEBAR;
}


/**
 * Print the content of the page.
 * @global Texy $texy
 * @param string $page
 * @return void
 */
function printContent($page = BASE_PAGE){
    global $texy;
    if(pageExists($page)){
        echo $texy->process(getContent($page));
    } elseif(pageExists('Special:NotFound')){
        echo $texy->process(str_replace('%PAGE%', $page, getContent('Special:NotFound')));
    } else{
        echo $texy->process("The page you're looking for does not exist at the moment. "
            . "However, you can [$page | create it] right now.");
    }
}


/**
 * Print HTML for edit <form>.
 * @param string $page
 * @return void
 */
function printEdit($page){
    $title = $content = $id = '';
    if($page){
        $title = $page;
        $id = titleToId($page);
        if(pageExists($page)){
            $content = getContent($page);
        }
    }

    echo <<<EDIT_FORM
<form action="./?edit=$id" method="post" id="edit-form">
  <p id="edit-block-title" class="edit-block">
    <label for="edit-title">Page:</label>
    <input type="text" id="edit-title" name="title" value="$title">
  </p>
  <div id="edit-block-content" class="edit-block">
    <label for="edit-content">Content:</label>
    <textarea name="content" id="edit-content" rows="15" cols="80">$content</textarea>
  </div>
  <p id="edit-block-submit" class="edit-block">
    <button type="submit">Save changes</button>
    <a href="./?$id">Cancel</a>
  </p>
  <p id="edit-block-help" class="edit-block">
    You can use <a href="http://texy.info/en/syntax">Texy! syntax</a> and some HTML too.<br>
    <small>Use [Sample Page] to link to "Sample Page". Use [Sample Page | link to sample] for custom label.</small>
</form>
EDIT_FORM;
}


/**
 * Print HTML for backlinks.
 * @global Texy $texy
 * @param string $page
 * @return void
 */
function printBacklinks($page){
    global $texy;
    $s = "[$page | Go back to page]

    This is a list of all pages that link to [$page].\n";

    $l = array();
    foreach(new FilesystemIterator(dirname(__FILE__) . '/wikdata', FilesystemIterator::SKIP_DOTS) as $file){
        if(checkBacklink($file, $page)){
            $l[] = "- [" . fileToTitle($file) . "]";
        }
    }
    if(empty($l)){
        $s .= "\nNo backlinks found.";
    } else{
        $s .= ".[#backlinks-list]\n" . implode("\n", $l);
    }

    echo $texy->process($s);
}


/**
 * Check file for baclinks to $page.
 * @param string $file
 * @param string $page
 * @return string
 */
function checkBacklink($file, $page){
    $c = file_get_contents($file);
    return preg_match("~\[$page(\s*\|\s*[^\w]*)?]~m", $c);
}


/**
 * Convert file path to title.
 * @param string $file
 * @return string
 */
function fileToTitle($file){
    return idToTitle(pathinfo($file, PATHINFO_FILENAME));
}


/**
 * Print HTML for recent changes.
 * @global Texy $texy
 * @param int $count
 * @return void
 */
function printRecentChanges($count){
    global $texy;
    $list = array();
    foreach(new FilesystemIterator(dirname(__FILE__) . '/wikdata', FilesystemIterator::SKIP_DOTS) as $file){
        $list[filemtime($file)] = fileToTitle($file);
    }
    krsort($list);

    $list = array_slice($list, 0, $count, true);
    $s = '';
    foreach($list as $t => $l){
        $s .= "\n- [$l] (" . date("d.m.Y, H:i:s", $t) . ")";
    }

    echo $texy->process($s);

}


/**
 * Get file path for page.
 * @param string $page
 * @return string
 */
function getFilePath($page){
    return dirname(__FILE__) . '/wikdata/' . titleToId($page) . '.wik';
}


/**
 * Checks for existence of page.
 * @param string $page
 * @return bool
 */
function pageExists($page){
    $path = getFilePath($page);
    return file_exists($path) && is_readable($path);
}


/**
 * Get the content of the page.
 * @param string $page
 * @return string|false
 */
function getContent($page){
    $path = getFilePath($page);
    if(pageExists($page)){
        return file_get_contents($path);
    }
    return false;
}


/**
 * Save page.
 * @param array $fields
 * @return bool
 */
function savePageContent($fields){
    if(@get_magic_quotes_gpc()){
        $fields = array_map(stripslashes, $fields);
    }

    $file = getFilePath($fields['title']);

    $do = file_put_contents($file, trim($fields['content']));
    if($do){
        header("Location: ./?" . titleToId($fields['title']));
        exit;
    } else{
        return false;
    }
}


/**
 * Generate table of contents HTML blockz.
 * @global Texy $texy
 * @return TexyHtml
 */
function generateToc(){
    global $texy;
    if(!$texy->headingModule->TOC){
        return '';
    }
    $block = TexyHTML::el('div');
    $block->id = 'toc';
    $block->create('h3', 'Contents');
    $toc = TexyHTML::el('ul');
    $block->add($toc);
    $lists[0] = $toc;
    $aList = 0;
    $level = 2;

    foreach($texy->headingModule->TOC as $heading){
        if($heading['level'] > $level){
            for($level; $heading['level'] > $level; ++$level){
                if($lists[$aList]->count() != 0){
                    $ul = $lists[$aList][$lists[$aList]->count() - 1]->create('ul');
                } else{
                    $li = $lists[$aList]->create('li');
                    $ul = $li->create('ul');
                }
                $lists[] = $ul;
            }
            $aList = count($lists) - 1;
        } elseif($heading['level'] < $level){
            $diff = $level - $heading['level'];

            $lists = array_slice($lists, 0, - $diff);

            $level = $heading['level'];
        }
        $aList = count($lists) - 1;
        $li = $lists[$aList]->create('li');
        $a = $li->create('a')->href('#' . $heading['el']->attrs['id'])->setText($heading['title']);
    }
    return $block->toHtml($texy);
}
