<?php
// -------------------------------------------------------------------------//
// Nuked-KlaN 1.7 - PHP Portal                                              //
// http://www.nuked-klan.org                                                //
// -------------------------------------------------------------------------//
// This program is free software. you can redistribute it and/or modify     //
// it under the terms of the GNU General Public License as published by     //
// the Free Software Foundation; either version 2 of the License.           //
// -------------------------------------------------------------------------//
defined('INDEX_CHECK') or die ('You can\'t run this file alone.');


/* ---------------------------------- */
/* Start version fusion 1.8 */
/* Test Environnement : Apache 2.0.63 and PHP 5.1.0 */
/* ---------------------------------- */

// Include configuration constants
if (file_exists('conf.inc.php')) {
    include('conf.inc.php');
}

// Sets which PHP errors are reported, use error debug popup

if ( defined( 'NK_ERROR_DEBUG' ) && NK_ERROR_DEBUG ) {
    require ROOT_PATH . 'Includes/libs/NK_Exception.php';
}

//Include Light Tpl library
require ROOT_PATH . 'Includes/libs/NK_Tpl.php';
$nkTpl = NK_Tpl::getInstance();

// Include DB library
require ROOT_PATH . 'Includes/libs/NK_' . $db_type .'.php';


/**
 * Try to connect, if false, display a error message.
 */
function nkTryConnect() {
    global $nkTpl;
    if (!nkDB_connect()) {
        echo $nkTpl->nkDisplayError(ERROR_QUERY);
        /**
         * @todo Add log
         */
        return;
    }
}

/**
 * Construct nuked array and control database prefix before initialization connection.
 * @param string $prefixDB : prefix database
 * @return array $nuked : global array construct
 */
function nkConstructNuked($prefixDB) {
    global $nkTpl;
    $nuked = array();
    // Control database prefix before initialize connection.
    $result = nkDB_controlPrefix($prefixDB);
    if (!$result) {
        echo $nkTpl->nkDisplayError(DBPREFIX_ERROR);
        /**
         * @todo Add log
         */
        return;
    } else {
        // Construct nuked array
        $nuked['prefix'] = $prefixDB;
        foreach ($result as $key => $value) {
            // Old version printSecuTags
            $nuked[$value['name']] = printSecuTags(htmlentities($value['value'], ENT_NOQUOTES));
            /**
             * @todo for fix bad code
             * 1. remove line before
             * 2. uncomment line below
             * 3. remove printSecuTags on all files which call $nuked variable
             */
            //$nuked[$value['name']] = $value['value'];
        }
    }
    
    return $nuked;
}

/**
 * Convert date format
 * @global array $nuked 
 * @global string $language language defined
 * @param string $timestamp timestamp
 * @param string $block : date format
 * @return string date converted 
 */
function nkDate($timestamp, $block = false) {
    global $nuked, $language;
    
    if ($block === false) {
        $format = $nuked['isBlock']; // à quoi correspond cette variable, voir function get_blok() ???
    } else {
        $format = $block;
    }
    
    if ($format === true) {
        if ($language == 'french') {
            $format = '%d/%m/%Y';
        } else {
            $format = '%m/%d/%Y';
        }
    } else {
        $format = $nuked['dateformat'];
    }
    
    // Format date, and convert it to ISO format
    return iconv('UTF-8','ISO-8859-1',strftime($format, $timestamp));
    //return iconv('UTF-8','ISO-8859-1',utf8_encode(strftime($format, $timestamp))); // For Windows servers
}

/**
 * Fix printing tags.
 * @param string $value : text to display before filter
 * @return string text to display after filter
 */
function printSecuTags($value){
    $value = htmlentities(html_entity_decode(html_entity_decode($value)));
    return $value;
}


/**
 * Current annual datezone time table
 * @param string $GMT
 * @return string 
 */
function getTimeZoneDateTime($GMT) {
    $timezones = array(
        '-1200'=>'Pacific/Kwajalein',
        '-1100'=>'Pacific/Samoa',
        '-1000'=>'Pacific/Honolulu',
        '-0900'=>'America/Juneau',
        '-0800'=>'America/Los_Angeles',
        '-0700'=>'America/Denver',
        '-0600'=>'America/Mexico_City',
        '-0500'=>'America/New_York',
        '-0400'=>'America/Caracas',
        '-0330'=>'America/St_Johns',
        '-0300'=>'America/Argentina/Buenos_Aires',
        '-0200'=>'Atlantic/Azores',
        '-0100'=>'Atlantic/Azores',
        '+0000'=>'Europe/London',
        '+0100'=>'Europe/Paris',
        '+0200'=>'Europe/Helsinki',
        '+0300'=>'Europe/Moscow',
        '+0330'=>'Asia/Tehran',
        '+0400'=>'Asia/Baku',
        '+0430'=>'Asia/Kabul',
        '+0500'=>'Asia/Karachi',
        '+0530'=>'Asia/Calcutta',
        '+0600'=>'Asia/Colombo',
        '+0700'=>'Asia/Bangkok',
        '+0800'=>'Asia/Singapore',
        '+0900'=>'Asia/Tokyo',
        '+0930'=>'Australia/Darwin',
        '+1000'=>'Pacific/Guam',
        '+1100'=>'Asia/Magadan',
        '+1200'=>'Asia/Kamchatka'
    );
    return $timezones[$GMT];
}

/**
 * Query for user / vistor banishment.
 * @global string $user_ip : ip user
 * @global array $user : user infos
 * @global string $language : language for NK
 * @todo to review ?
 */
function banip() {
    global $user_ip, $user, $language;

    // Delete last number for dynamic IP's
    $ipDyn = substr($user_ip, 0, -1);

    // SQL condition : dynamic IP or user account
    $whereClause = ' WHERE (ip LIKE "%' . $ipDyn . '%") OR pseudo = "' . $user[2] . '"';

    // Search banish
    $banQuery = nkDB_select('SELECT `id`, `pseudo`, `date`, `dure` FROM ' . BANNED_TABLE . $whereClause);

    // If positive result with banish search, assign new ip
    if (nkDB_numRows() > 0) {
        $ipBanned = $user_ip;
    } else if (isset($_COOKIE['ip_ban']) && !empty($_COOKIE['ip_ban'])) { // Seach cookie banish
        // On supprime le dernier chiffre de l'adresse IP contenu dans le cookie
        $ipDynCookie = substr($_COOKIE['ip_ban'], 0, -1);

        // Check IP cookie and current IP address
        if ($ipDynCookie == $ipDyn) {
            // Check banishment existence
            $banCookieQuery  = nkDB_select('SELECT `id` FROM ' . BANNED_TABLE . ' WHERE (ip LIKE "%' . $ipDynCookie . '%")');
            // If positive result, do new ban and assign new IP
            if (nkDB_numRows() > 0) {
                $ipBanned = $user_ip;
            }
        }
    } else{
        $ipBanned = '';
    }

    // Delete expire banishment or update IP
    if (!empty($ipBanned)) {
        // Search expire banishment
        if ($banQuery[0]['dure'] != 0 && ($banQuery[0]['date'] + $banQuery[0]['dure']) < time()) {
            // Delete banishment
            $delBan = nkDB_delete( BANNED_TABLE, $whereClause);
            // Administration notification           
            $fields = array( 'date', 'type', 'texte' );
            $banQuery[0]['pseudo']  .= ' ' . _BANFINISHED . ' : [<a href=\"index.php?file=Admin&page=user&op=main_ip\">' . _LINK . '</a>]';
            $values = array( time(), '4', mysql_real_escape_string($banQuery[0]['pseudo']));
            $rs = nkDB_insert( BANNED_TABLE, $fields, $values );
        } else { // update IP address
            if ($isset($user)) {
                $whereUser = ', pseudo = "' . $user[2] . '"';
            } else {
                $whereUser = '';
            }
            $fields = array('ip', 'pseudo');
            $values = array($user_ip, $whereUser);
            $rs = nkDB_update( BANNED_TABLE, $fields, $values, 'ip = '. nkDB_escape($user_ip. $whereUser . $whereClause));
                
            // Redirection to banish page
            $urlBan = 'ban.php?ip_ban=' . $ipBanned;
            if (!empty($user)) {
                $urlBan .= '&user=' . urlencode($user[2]);
            }
            redirect($urlBan, 0);
        }
    }
}


/**
 * Display blocks.
 * @global array user : user informations
 * @global object $nkTpl : template NK
 * @param string $side : side block to display
 */
function get_blok($side) {
    global $user, $nkTpl;

    $activeTranslation = array(
        'gauche' => 1,
        'droite' => 2,
        'centre' => 3,
        'bas' => 4
    );
    /**
     * @todo to delete ?
     */
    if ($side == 'gauche' || $side == 'droite') {
        $nuked['isBlock'] = TRUE;
    }
    
     // Level of user
    if (!empty($user)) {
        $visiteur = $user[1];
    } else {
        $visiteur = 0;
    }

    if (!array_key_exists($side, $activeTranslation )) {
        echo $nkTpl->nkDisplayError(UNKNOWN_BLOCK . ' : '. $side);
        return;
    }

    if (!function_exists( $themeBlockName = 'block_' . $side )) {
        echo $nkTpl->nkDisplayError(UNKNOWN_FUNCTION_BLOCK . ' : '. $themeBlockName);
        return;
    }

    foreach (getBlockData( $activeTranslation[$side] ) as $block) {
        $display = FALSE;

        $block['page'] = explode( '|', $block['page'] );

        // If we find a block page, a flag is set for including the associated block
        if ((isset($_REQUEST['file']) && in_array($_REQUEST['file'], $block['page'])) || in_array('Tous', $block['page'])) {
            $display = TRUE;
        }

        if ($visiteur >= $block['nivo'] && $display) {
            $block['titre'] = printSecuTags($block['titre']);

            include_once 'Includes/blocks/block_'. $block['type'] .'.php';

            if (function_exists($blockFunction = 'affich_block_'. $block['type'])) {
                $block = $blockFunction( $block );
            } else {
                echo $nkTpl->nkDisplayError(UNKNOWN_FUNCTION_DISPLAY_BLOCK . ' : '. $blockFunction);
                return;
            }

            if (!empty( $block['content'] )) {
                $themeBlockName( $block );
            }
        }
    }
    /**
     * @todo to delete ?
     */
    $nuked['isBlock'] = FALSE;
}

/**
 * Get selected block to display (used like cache).
 * @staticvar array $data : list of blocks
 * @param type $activeSelected : key used to selected block
 * @return array : block selected
 */
function getBlockData($activeSelected) {
    static $data = array();

    if (empty($data)) {
        // Get list of blocks
        $blockList = nkDB_select('SELECT bid, active, position, module, titre, content, type, nivo, page FROM ' .
                                                    BLOCK_TABLE . ' ORDER BY position');
        
        foreach ($blockList as $block) {
            $data[$block['active']][] = array(
                    'bid'		=> $block['bid'],
                    'position'	=> $block['position'],
                    'module'	=> $block['module'],
                    'titre'		=> $block['titre'],
                    'content'	=> $block['content'],
                    'type'		=> $block['type'],
                    'nivo'		=> $block['nivo'],
                    'page'		=> $block['page']
            );
        }
    }

    if (array_key_exists( $activeSelected, $data )) {
        return $data[$activeSelected];
    }
    return array();
}

/**
 * Display pictures if url is correct
 * @param string $url : url to check
 * @return string $url : secure url picture
 */
function checkimg($url){
    $url = rtrim($url);
    if (!in_array( pathinfo( $url, PATHINFO_EXTENSION ), array( 'jpg', 'jpeg', 'gif', 'png', 'bmp' ))) {
        $url = 'images/noimagefile.gif';
    }
    return $url;
}

/**
 * Replace smilies in text.
 * @param array $matches : text to parse
 * @return string : parsing text
 */
function replaceSmilies($matches)
{
    $matches[0] = preg_replace('#<img src=\"(.*)\" alt=\"(.*)\" title=\"(.*)\" />#Usi', '$2', $matches[0]);
    return $matches[0];
}

/**
 * Display smilies.
 * @global array $nuked
 * @param string $texte : text to display with smilies
 * @return string : formatted text
 */
function icon($texte){
    global $nuked;
    
    $texte = str_replace('mailto:', 'mailto!', $texte);
    $texte = str_replace('http://', '_http_', $texte);
    $texte = str_replace('https://', '_https_', $texte);
    $texte = str_replace('&quot;', '_QUOT_', $texte);
    $texte = str_replace('&#039;', '_SQUOT_', $texte);
    
    $smiliesList = getSmiliesData();
    
    foreach($smiliesList as $smiley) {
        $texte = str_replace($smiley['code'],
                '<img src="images/icones/' . $smiley['url'] . '" alt="" title="' . htmlentities($smiley['name']) . '" />',
                $texte);
    }

    $texte = str_replace('mailto!', 'mailto:', $texte);
    $texte = str_replace('_http_', 'http://', $texte);
    $texte = str_replace('_https_', 'https://', $texte);
    $texte = str_replace('_QUOT_', '&quot;', $texte);
    $texte = str_replace('_SQUOT_', '&#039;', $texte);
    
    // Light calculation if <pre> tag is not present in text
    if (strpos($texte, '<pre') !== false) {
        $texte = preg_replace_callback('#<pre(.*)>(.*)<\/pre>#Uis','replaceSmilies', $texte);
    }

    return $texte;
}

/**
 * Get smilies data (used like cache).
 * @return array : all smilies data sorting by id
 */
function getSmiliesData() {
    static $smileyData = array();

    if (empty($smileyData)) {
        $smileyData= nkDB_select( 'SELECT `code`, `url`, `name` FROM `'. SMILIES_TABLE .'` ORDER BY `id`' );
    }

    return $smileyData;
}

/**
 * Configure smilies for CKEditor (used like cache).
 * @return string : string for configuration of CKEditor smilies.
 */
function configSmiliesCKEditor(){
    
    static $configCK = '';
    
    if (empty($configCK)) {
        // Smilies path configuration for CKeditor.
        $configCK = 'CKEDITOR.config.smiley_path=\'images/icones/\';';

        $smiliesList = getSmiliesData();

        // Construct array data for smilies
        foreach ($smiliesList as $smiley) {
            $smiliesCode[] = addslashes($smiley['code']);
            $smiliesUrl[] = $smiley['url'];
            $smiliesName[] = htmlentities($smiley['name']);
        }

        // Number of smilies
        $nbSmilies = count($smiliesList);

        // Build array config images / descriptions / titles
        $configCKSmilies = 'CKEDITOR.config.smiley_images=[';
        $configCKDescriptions = 'CKEDITOR.config.smiley_descriptions=[';
        $configCKTitles = 'CKEDITOR.config.smiley_titles=[';
        for ($i = 0; $i < $nbSmilies - 1; $i++) {
            $configCKSmilies .= '\'' . $smiliesUrl[$i] . '\', ';
            $configCKDescriptions .= '\'' . $smiliesCode[$i] . '\', ';
            $configCKTitles .= '\'' . $smiliesName[$i] . '\', ';
        }
        $configCKSmilies .= '\'' . $smiliesUrl[$nbSmilies] . '\'];';
        $configCKDescriptions .= '\'' . $smiliesCode[$nbSmilies] . '\'];';
        $configCKTitles .= '\'' . $smiliesName[$nbSmilies] . '\'];';

        $configCK .= $configCKSmilies . $configCKDescriptions . $configCKTitles;
    }
    
    return $configCK;
}

/**
 * Secure HTTP links.
 * @param string $url : url to check
 * @return boolean : true if url is secure, else false 
 */
function secureUrl($url){
    $urlInfos = parse_url(strtolower($url));
    $secureUrl = false;
    // If is not malformed URL and URL does not contain php extension and query
    if ($urlInfos !== false
            && strrchr($urlInfos['path'], '.') != '.php'
            && (!isset($urlInfos['query']) || $urlInfos['query'] == '')) {
            $secureUrl = true;
        }
    return $secureUrl;
}

/**
 * Secure data with filtering CSS.
 * @param string $styles : list of css styles, separe by comma
 * @return string data secure 
 */
function secureCSS($styles){
    $allowedProperties = array(
        'display',
        'margin-left',
        'margin-right',
        'float',
        'padding',
        'text-decoration',
        'text-align',
        'color',
        'align',
        'vertical-align',
        'margin',
        'border',
        'background-color',
        'background',
        'width',
        'height',
        'border-color',
        'background-image',
        'border-width',
        'border-style',
        'padding-left',
        'padding-right',
        'font-size',
        'font-family'
    );
    
    // Prepare each CSS property
    $styles = explode(';', $styles);
    $styles = array_map('trim', $styles);

    foreach ($styles as $key => $cssAttribute){
        // Get authorized CSS attribute in $authAttribute
        preg_match('/ *([^ :]+) *: *(( |.)*)/', $cssAttribute, $authAttribute);
        if (!in_array($authAttribute[1], $allowedProperties)) {
            unset($styles[$key]);
        } elseif (preg_match('/url *\\( *\'?"? *([^ \'"]+) *"?\'?\\)/', $cssAttribute, $authAttribute) > 0){
            if (!secureUrl($authAttribute[1])) {
                unset($styles[$key]);
            }
        }
    }
    return implode(';', $styles);
}

/**
 * HTML Filter.
 * @global array $nuked
 * @param array $matches : list of HTML tags
 * @return string : HTML filtered
 */
function secureArgs($matches){
    global $nuked;

    // List of allowed tags
    $allowedTags = array(
        'p' => array(
            'style',
            'dir',
        ),
        'h1' => array(
            'style',
        ),
        'h2' => array(
            'style',
        ),
        'h3' => array(
            'style',
        ),
        'h4' => array(
            'style',
        ),
        'h5' => array(
            'style',
        ),
        'h6' => array(
            'style',
        ),
        'img' => array(
            'alt',
            'class',
            'dir',
            'id',
            'lang',
            'longdesc',
            'src',
            'style',
            'title',
            'width',
            'height',
            'border',
        ),
        'strong' => array(),
        'em' => array(),
        'u' => array(),
        'strike' => array(),
        'sub' => array(),
        'sup' => array(),
        'ol' => array(),
        'ul' => array(),
        'li' => array(),
        'blockquote' => array(
            'style',
        ),
        'div' => array(
            'class',
            'id',
            'lang',
            'style',
            'title',
            'align',
        ),
        'br' => array(),
        'a' => array(
            'accesskey',
            'charset',
            'class',
            'dir',
            'href',
            'id',
            'lang',
            'name',
            'rel',
            'style',
            'tabindex',
            'target',
            'title',
            'type',
        ),
        'table' => array(
            'align',
            'border',
            'cellpadding',
            'cellspacing',
            'class',
            'dir',
            'id',
            'style',
            'summary',
        ),
        'caption' => array(),
        'thead' => array(),
        'tr' => array(
            'style',
        ),
        'td' => array(
            'style',
            'colspan',
            'rowspan'
        ),
        'th' => array(
            'scope',
        ),
        'tbody' => array(),
        'hr' => array(),
        'span' => array(
            'id',
            'style',
            'dir',
        ),
        'big' => array(),
        'small' => array(),
        'tt' => array(),
        'code' => array(),
        'kbd' => array(),
        'samp' => array(),
        'var' => array(),
        'del' => array(),
        'ins' => array(),
        'cite' => array(),
        'q' => array(),
        'pre' => array(
            'class'
        ),
        'address' => array(),
    );

    // For video plugin
    $videoTags = array(
        'object' => array(
            'width',
            'height',
            'data',
            'type',
        ),
        'param' => array (
            'name',
            'value',
        ),
        'embed' => array (
            'allowfullscreen',
            'allowscriptaccess',
            'height',
            'src',
            'type',
            'width',
        ),
        /*'iframe' => array (
            'src',
            'width',
            'height',
            'frameborder',
        ),*/

    );
    
    // If video editor is activated
    if ($nuked['video_editeur'] == 'on') {
        $allowedTags = array_merge($allowedTags, $videoTags);
    }

    // If it's a authorized tag
    if (in_array(strtolower($matches[1]), array_keys($allowedTags))) {
        
        // Get potential forbidden attributes in $args
        preg_match_all('/([^ =]+)=(&quot;((.(?<!&quot;))*)|[^ ]+)/', $matches[2], $args);

        // Delete forbidden attributes
        foreach ($args[1] as $id => $attribute) {
            if (!in_array($attribute, $allowedTags[$matches[1]])) {
                foreach ($args as $part) {
                    unset($args[$part][$id]);
                }
            }
        }

        // Build remaining attributes
        foreach ($args[2] as $id => $val) {
            $args[1][$id] = trim(strtolower($args[1][$id]));
            $val = trim($val);
            if (preg_match('/^&quot;/', $val, $tmp)) {
                $val .= ';';
            }
            $args[2][$id] = trim(html_entity_decode($val), " \t\n\r\0\"");
            if ($args[1][$id] == 'style') {
                $args[2][$id] = secureCSS($args[2][$id]);
            } elseif ($matches[1] == 'img' && $args[1][$id] == 'src') {
                if (!secureUrl($args[2][$id])) {
                    $args[2][$id] = 'images/noimagefile.gif';
                }
            }
        }

        // Construct tag to return
        $retStr = '<' . $matches[1];
        foreach ($args[1] as $id=>$attribute){
            $retStr .= ' ' . $attribute . '="' . $args[2][$id] . '"';
        }
        if ($matches[3] == '/') {
            $retStr .= ' />';
        } else {
            $retStr .= '>';
        }
        return $retStr;

    // End tags
    } else if (substr($matches[1], 0, 1) == '/' 
            && in_array(strtolower(substr($matches[1], 1)), array_keys($allowedTags))) {
        return '<' . $matches[1] . '>';
    // Forbidden tags
    } else{
        return $matches[0];
    }
}

/**
 * Display content with security CSS and HTML
 * @global string $bgcolor3 : color code defined by theme
 * @global array $nuked
 * @global string $language : language of website
 * @param string $texte : text to secure
 * @return string : secure text to display
 */
function secu_html($texte){
    global $bgcolor3, $nuked, $language;
    
    // HTML tag forbidden
    $texte = str_replace(array('&lt;', '&gt;', '&quot;'), array('<', '>', '"'), $texte);
    $texte = stripslashes($texte);
    $texte = htmlspecialchars($texte);
    $texte = str_replace('&amp;', '&', $texte);
    
    // Authorized tag
    $texte = preg_replace_callback('/&lt;([^ &]+)[[:blank:]]?((.(?<!&gt;))*)&gt;/', 'secureArgs', $texte);

    preg_match_all('`<(/?)([^/ >]+)(| [^>]*([^/]))>`', $texte, $tags, PREG_SET_ORDER);

    $tagList = array();
    // Flag used to determine if $texte is secure or not
    $bad = false;
    // Number of tags
    $size = count($tags);
    for ($i=0; $i<$size; $i++) {
        if ($tags[$i][3] == '') {
            $tagName = $tags[$i][2] . $tags[$i][4];
        } else {
           $tagName =  $tags[$i][2];
        }
        if ($tags[$i][1] == '/'){
            $bad = $bad | array_pop($tagList) != $tagName;
        } else {
            array_push($tagList, $tagName);
        }
    }

    $bad = $bad | count($tagList) > 0;

    if ($bad){
        $texte = _HTMLNOCORRECT;
    }
    return $texte;
}

/**
 * Redirect javascript function.
 * @param string $url : url to redirect
 * @param int $tps : time in seconds before redirection
 */
function redirect($url, $tps){
    $temps = $tps * 1000;

    echo '<script type="text/javascript">',"\n"
    , '//<![CDATA[',"\n"
    , "\n"
    , 'function redirect() {',"\n"
    , 'window.location=\'' , $url , '\'',"\n"
    , "}\n"
    , 'setTimeout(\'redirect()\',\'' , $temps ,'\');',"\n"
    , "\n"
    , '//]]>',"\n"
    , '</script>',"\n";
}

/**
  * Create a page link list
 * @param int $total : The total number of subject
 * @param int $limit : The number of subjects per page
 * @param string $url : The basic url to redirect at page
 */
function number($total, $limit, $url){
    
    $currentPage = $_REQUEST['p'];
    
    // If data is correct, we can build pagination
    if ($limit > 0 && $total > $limit) {
        if ($total <= 0) {
            $total   = 1;
        }
        // Current page
        if (!empty($currentPage)){
            $currentPage = $_REQUEST['p'];
        } else {
            $currentPage = 1;
        }
        // Number of pages
        $nbPages = ceil($total / intval($limit));
        // Start string to output display
        $output = '<div class="pages-links"><b class="pgtitle">' . _PAGE . ' :</b>&nbsp;';
        
        // Build links for each page
        for ($i = 1; $i <= $nbPages; $i++) {
            // Current page
            if ($i == $currentPage){
                $output .= sprintf('<b class="pgactuel">[%d]</b> ',$i);
                // Links near current page
            } elseif (abs($i - $currentPage) <= 4) {
                $output .= sprintf('<a href="' . $url . '&amp;p=%d" class="pgnumber">%d</a> ',$i, $i);
                // Display text before forget unnecessary pages
            } else{
                // Before current page
                if (!isset($firstDone) && $i < $currentPage) {
                    $output .= sprintf('...<a href="' . $url . '&amp;p=%d" title="' . _PREVIOUSPAGE . '" class="pgback">&laquo;</a> ',$currentPage-1);
                    $firstDone = true;
                    // After current page
                } elseif (!isset($lastDone) && $i > $currentPage) {
                    $output .= sprintf('<a href="' . $url . '&amp;p=%d" title="' . _NEXTPAGE . '" class="pgnext">&raquo;</a>... ',$currentPage+1);
                    $lastDone = true;
                    // Exceed interesting pages : we stop
                } elseif ($i > $currentPage) {
                    break;
                }
            }
        }
        $output .= '</div>';
        
        echo $output;
    }
}

/**
 * Count the number of current visitors.
 * @global array $user : user informations
 * @global array $nuked
 * @global string $user_ip : user IP address
 * @return int : number of visitors
 *  [0] = visitors
 *  [1] = members
 *  [2] = admin
 *  [3] = members + admin;
 *  [4] = visitors + members + admin
 */
function nbvisiteur(){
    global $user, $nuked, $user_ip;

    static $count = array();

    if (empty($count)) {
        $time = time();
        $limite = $time + (int) $nuked['nbc_timeout'];

        $rsDel = nkDB_delete(NBCONNECTE_TABLE, 'date < ' . nkDB_escape($time));

        if (isset($user_ip)) {
            updateUserConnectData($user, $user_ip, $limite);
        }

        $req = nkDB_select('SELECT COUNT(*) AS recordcount FROM '. NBCONNECTE_TABLE .' WHERE type = 0' );
        $nb_visitor = $req[0]['recordcount'];
        
        $req = nkDB_select( 'SELECT COUNT(*) AS recordcount FROM '. NBCONNECTE_TABLE .' WHERE type BETWEEN 1 AND 2' );
        $nb_member = $req[0]['recordcount'];

        $req = nkDB_select( 'SELECT COUNT(*) AS recordcount FROM '. NBCONNECTE_TABLE .' WHERE type > 2' );
        $nb_admin = $req[0]['recordcount'];

        $count[0] = $nb_visitor;
        $count[1] = $nb_member;
        $count[2] = $nb_admin;
        $count[3] = $nb_member + $nb_admin;
        $count[4] = $nb_visitor + $count[3];
    }

    return $count;
}

/**
 * Update data user connections (used like cache).
 * @param array $user : user informations
 *  [0] = id visitor
 *  [1] = user level
 *  [2] = pseudo
 *  [3] = IP address
 *  [4] = number of new messages unread
 * @param type $user_ip : user IP address
 * @param int $limite : date limit
 */
function updateUserConnectData($user, $user_ip, $limite) {
    
    // Get IP address of visitor
    if (isset($user[0])) {
        $req = nkDB_select('SELECT IP FROM '. NBCONNECTE_TABLE .' WHERE user_id = '. nkDB_escape($user[0]));
    } else {
        $req = nkDB_select('SELECT IP FROM '. NBCONNECTE_TABLE .' WHERE IP = '. nkDB_escape($user_ip));
    }

    // If IP address exists, update user informations
    if (nkDB_numRows() > 0) {
        if (isset($user[0])) {
            $fieldsUserSet = array('date', 'type', 'IP', 'username');
            $valuesUserSet = array($limite, (int) $user[1], $user_ip, $user[2]);
            $rs = nkDB_update(NBCONNECTE_TABLE, $fieldsUserSet, $valuesUserSet, 'user_id = '. nkDB_escape($user[0]));
        } else {
            $fields = array('date', 'type', 'user_id', 'username');
            $values = array($limite, (int) $user[1], $user[0], $user[2]);
            $rs = nkDB_update(NBCONNECTE_TABLE, $fieldsUserSet, $valuesUserSet, 'IP = '. nkDB_escape($user_ip));
        }
    } else {  // If not, add IP address of user (delete this if it exists before)
        $rsDel = nkDB_delete(NBCONNECTE_TABLE, 'IP = ' . nkDB_escape($user_ip));
        
        $fields = array('`IP`', '`type`', '`date`', '`user_id`', '`username`');
        $values = array($user_ip, (int) $user[1], $limite, $user[0], $user[2]);
        $rsIns = nkDB_insert(NBCONNECTE_TABLE, $fields, $values);
        
    }
}

/**
 * Return the required level for using the module.
 * @param type $moduleName : the module name
 * @return int $requiredLvl : if module exists, return int : the required level for using module,
 * else FALSE
 */
function nivo_mod($moduleName){
    $data = getModuleData();

    if (!array_key_exists($moduleName, $data)) {
        return FALSE;
    }

    return $data[$moduleName]['userLevel'];
}

/**
 * Return the required level to administrate the module.
 * @param type $moduleName : the module name
 * @return mixed $requiredLvl : if module exists, return int : the required admin level for administrate module,
 * else FALSE
 */
function admin_mod($moduleName){
    $data = getModuleData();

    if (!array_key_exists($moduleName, $data)) {
        return FALSE;
    }

    return $data[$moduleName]['adminLevel'];
}

/**
 * Get list of modules (used like cache).
 * @staticvar array $data
 * @return $data list of module
 *  $data[$moduleName][0] : module name
 *  $data[$moduleName][1] : required level for using module
 *  $data[$moduleName][1] : required level for administrating module
 */
function getModuleData() {
    
    static $data = array();

    if (empty($data)) {
        $moduleList = nkDB_select( 'SELECT nom, niveau, admin FROM '. MODULES_TABLE );
        foreach ($moduleList as $module) {
            $data[$module['nom']] = array('userLevel' => $module['niveau'], 'adminLevel' => $module['admin']);
        }
    }

    return $data;
}

/**
 * Include a file of constants for translating.
 * @param string $fileLang the path of file to include
 * @todo suppress comment
 */
function translate($fileLang){
    include $fileLang;
//    global $nuked;
//
//    ob_start();
//    print eval(" include ('$file_lang'); ");
//    $lang_define = ob_get_contents();
//    $lang_define = htmlentities($lang_define, ENT_NOQUOTES);
//    $lang_define = str_replace('&lt;', '<', $lang_define);
//    $lang_define = str_replace('&gt;', '>', $lang_define);
//    ob_end_clean();
//    return $lang_define;
}


/**
 * Count the number of page views module for stats.
 */
function compteur($file){
    $rsUpd = nkDB_update(
        STATS_TABLE,
        array( 'count' ),
        array( array( 'count + 1', 'no-escape' ) ),
        'type = "pages" AND nom = '. nkDB_escape( $file ));
    
    /**
     * Bug PHP 5.3.0 & Apache 2.2 (mysql_close() resource parameter)
     * comment nkDB_disconnect() on index.php
     * or use this line instead :
     * $rsUpd = mysql_query('UPDATE ' . STATS_TABLE . ' SET count = count + 1 WHERE type = "pages" AND nom = "' . $file . '"');
     */
}

/**
 * Protect string with anti-css.
 * @param string $str : The string to check
 * @return string : Return string protected
 */
function nk_CSS($str){
    if ($str != ""){
        $str = str_replace('content-disposition:','&#99;&#111;&#110;&#116;&#101;&#110;&#116;&#45;&#100;&#105;&#115;&#112;&#111;&#115;&#105;&#116;&#105;&#111;&#110;&#58;',$str);
        $str = str_replace('content-type:','&#99;&#111;&#110;&#116;&#101;&#110;&#116;&#45;&#116;&#121;&#112;&#101;&#58;',$str);
        $str = str_replace('content-transfer-encoding:','&#99;&#111;&#110;&#116;&#101;&#110;&#116;&#45;&#116;&#114;&#97;&#110;&#115;&#102;&#101;&#114;&#45;&#101;&#110;&#99;&#111;&#100;&#105;&#110;&#103;&#58;',$str);
        $str = str_replace('include','&#105;&#110;&#99;&#108;&#117;&#100;&#101;',$str);
        $str = str_replace('script','&#115;&#99;&#114;&#105;&#112;&#116;',$str);
        $str = str_replace('eval','&#101;&#118;&#97;&#108;',$str);
        $str = str_replace('javascript','&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;',$str);
        $str = str_replace('embed','&#101;&#109;&#98;&#101;&#100;',$str);
        $str = str_replace('iframe','&#105;&#102;&#114;&#97;&#109;&#101;',$str);
        $str = str_replace('refresh', '&#114;&#101;&#102;&#114;&#101;&#115;&#104;', $str);
        $str = str_replace('onload', '&#111;&#110;&#108;&#111;&#97;&#100;', $str);
        $str = str_replace('onstart', '&#111;&#110;&#115;&#116;&#97;&#114;&#116;', $str);
        $str = str_replace('onerror', '&#111;&#110;&#101;&#114;&#114;&#111;&#114;', $str);
        $str = str_replace('onabort', '&#111;&#110;&#97;&#98;&#111;&#114;&#116;', $str);
        $str = str_replace('onblur', '&#111;&#110;&#98;&#108;&#117;&#114;', $str);
        $str = str_replace('onchange', '&#111;&#110;&#99;&#104;&#97;&#110;&#103;&#101;', $str);
        $str = str_replace('onclick', '&#111;&#110;&#99;&#108;&#105;&#99;&#107;', $str);
        $str = str_replace('ondblclick', '&#111;&#110;&#100;&#98;&#108;&#99;&#108;&#105;&#99;&#107;', $str);
        $str = str_replace('onfocus', '&#111;&#110;&#102;&#111;&#99;&#117;&#115;', $str);
        $str = str_replace('onkeydown', '&#111;&#110;&#107;&#101;&#121;&#100;&#111;&#119;&#110;', $str);
        $str = str_replace('onkeypress', '&#111;&#110;&#107;&#101;&#121;&#112;&#114;&#101;&#115;&#115;', $str);
        $str = str_replace('onkeyup', '&#111;&#110;&#107;&#101;&#121;&#117;&#112;', $str);
        $str = str_replace('onmousedown', '&#111;&#110;&#109;&#111;&#117;&#115;&#101;&#100;&#111;&#119;&#110;', $str);
        $str = str_replace('onmousemove', '&#111;&#110;&#109;&#111;&#117;&#115;&#101;&#109;&#111;&#118;&#101;', $str);
        $str = str_replace('onmouseover', '&#111;&#110;&#109;&#111;&#117;&#115;&#101;&#111;&#118;&#101;&#114;', $str);
        $str = str_replace('onmouseout', '&#111;&#110;&#109;&#111;&#117;&#115;&#101;&#111;&#117;&#116;', $str);
        $str = str_replace('onmouseup', '&#111;&#110;&#109;&#111;&#117;&#115;&#101;&#117;&#112;', $str);
        $str = str_replace('onreset', '&#111;&#110;&#114;&#101;&#115;&#101;&#116;', $str);
        $str = str_replace('onselect', '&#111;&#110;&#115;&#101;&#108;&#101;&#99;&#116;', $str);
        $str = str_replace('onsubmit', '&#111;&#110;&#115;&#117;&#98;&#109;&#105;&#116;', $str);
        $str = str_replace('onunload', '&#111;&#110;&#117;&#110;&#108;&#111;&#97;&#100;', $str);
        $str = str_replace('document', '&#100;&#111;&#99;&#117;&#109;&#101;&#110;&#116;', $str);
        $str = str_replace('cookie', '&#99;&#111;&#111;&#107;&#105;&#101;', $str);
        $str = str_replace('vbscript', '&#118;&#98;&#115;&#99;&#114;&#105;&#112;&#116;', $str);
        $str = str_replace('location', '&#108;&#111;&#99;&#97;&#116;&#105;&#111;&#110;', $str);
        $str = str_replace('object', '&#111;&#98;&#106;&#101;&#99;&#116;', $str);
        $str = str_replace('vbs', '&#118;&#98;&#115;', $str);
        $str = str_replace('href', '&#104;&#114;&#101;&#102;', $str);
        $str = str_replace('src', '&#115;&#114;&#99;', $str);
        $str = str_replace('expression', '&#101;&#120;&#112;&#114;&#101;&#115;&#115;&#105;&#111;&#110;', $str);
        $str = str_replace('alert', '&#97;&#108;&#101;&#114;&#116;', $str);
    }
    return($str);
}

/**
 * Registration info of the user for stats
 * @global array $user : user informations
 * @global array $nuked
 * @global string $user_ip : user IP address
 */
function visits(){
    global $nuked, $user_ip, $user;

    // Visit date
    $time = time();
    // Time visit (in seconds)
    $timevisit = $nuked['visit_delay'] * 60;
    // Time limit (in seconds)
    $limite = $time + $timevisit;
    
    if ($user) {
        $strReq = 'SELECT id, date FROM ' . STATS_VISITOR_TABLE . ' WHERE user_id = ' . nkDB_escape($user[0]);
        $userID = $user[0];
    } else {
        $strReq = 'SELECT id, date FROM ' . STATS_VISITOR_TABLE . ' WHERE ip = ' . nkDB_escape($user_ip);
        $userID = $user_ip;
    }
    
    $statsData = nkDB_select($strQuery, array( 'date' ), 'DESC', 1);
    
    //. If ID visitor exists and last visit of user is greater than current time, update this
    if ($statsData[0]['id'] != '' && $statsData[0]['date'] > $time) {
        nkDB_update(STATS_VISITOR_TABLE, array( 'date' ), array( $limite ), 'id = '. nkDB_escape($stats_data[0]['id']));
    } else {
        
        // Get month, year, day and hour actual
        $month = strftime( '%m', $time );
        $year = strftime( '%Y', $time );
        $day	= strftime( '%d', $time );
        $hour = strftime( '%H', $time );
        
        // Get http referer if exists
        if (isset($_SERVER['HTTP_REFERER'])) {
            $userReferer = addslashes($_SERVER['HTTP_REFERER']);
        } else {
            $userReferer = '';
        }
        
        $userHost = strtolower(@gethostbyaddr($user_ip));
        
        // Get hostname of user
        if ($userHost == $user_ip) {
            $host = '';
        } else if (preg_match(
                '#([^.]{1,})((\.(co|com|net|org|edu|gov|mil))|())((\.(ac|ad|ae|af|ag|ai|al|am|an|ao|aq|ar|as|at|au|aw|az|ba|bb|
                bd|be|bf|bg|bh|bi|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|cr|cu|cv|cx|cy|cz|de|dj|dk|
                dm|do|dz|ec|ee|eg|eh|er|es|et|fi|fj|fk|fm|fo|fr|fx|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|
                hn|hr|ht|hu|id|ie|il|im|in|io|iq|ir|is|it|je|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|
                mg|mh|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|mv|mw|mx|my|mz|na|nc|ne|nf|ng|ni|nl|no|np|nr|nt|nu|nz|om|pa|pe|
                pf|pg|ph|pk|pl|pm|pn|pr|pt|pw|py|qa|re|ro|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sy|sz|tc|td|tf|
                tg|th|tj|tk|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|um|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zr|zw))|())$#',
                $userHost, $res)) {
            $host = $res[0];
        }
        
        // Checks the browser used by user
        $browser = getUserBrowser();

        // Checks the Os used by user
        $os = getUserOperatingSystem();

        // Save user stats
        nkDB_insert(
                STATS_VISITOR_TABLE,
                array('`user_id`', '`ip`', '`host`', '`browser`', '`os`', '`referer`', '`day`', '`month`', '`year`', '`hour`', '`date`'),
                array($userID, $user_ip, $host, $browser, $os, $user_referer, $day, $month, $year, $hour, $limite)
        );
    }
}

/**
 * Check if pseudo is conform ( no empty & no special characters ), not used and not banned
 * @param string $pseudo : pseudo to check
 * @param boolean $checkNickUse : true for checking if pseudo is used, else false
 * @param int $maxlength : the max length of pseudo (30)
 * @return string : pseudo string without blank characters or error code
 */
function verif_pseudo($pseudo = '', $checkNickUse = TRUE, $maxlength = 30)
{
    // Clean blank characters of pseudo
    $pseudo = trim($pseudo);

    // Check if special characters in pseudo is used
    if (!$pseudo || $pseudo == '' || ctype_space($pseudo) || preg_match( '#[\$\^\(\)\'"\?%\#<>,;:]#', $pseudo )) {
        return 'error1';
    }

    // Check if pseudo is used
    if ($checkNickUse) {
        $userReg = nkDB_totalNumRows( 'SELECT pseudo FROM '. USER_TABLE .' WHERE pseudo = '. nkDB_escape($pseudo) );
        if ($userReg > 0) {
            return 'error2';
        }
    }

    // Check if pseudo is banned
    $banReg = nkDB_totalNumRows( 'SELECT pseudo FROM '. BANNED_TABLE .' WHERE pseudo = '. nkDB_escape($pseudo) );
    if ($banReg > 0) {
        return 'error3';
    }

    // Check if pseudo is too long if needed
    if (strlen($pseudo) > $maxlength) {
        return 'error4';
    }

    return $pseudo;
}

/**
 * Update the sitemap.xml file (SEO)
 * @global array $nuked 
 */
function updateSitemapXML(){
    global $nuked;
    
    // Modules which are not included in sitemap.xml
    $excludedModules = array('Suggest', 'Comment', 'Vote', 'Textbox', 'Members', 'Contact');

    $resource = fopen( ROOT_PATH . 'sitemap.xml', 'wb' );
    
    if ($resource !== false) {
        
        $sitemap = "<?xml version='1.0' encoding='UTF-8'?>\r\n";
        $sitemap .= "<urlset xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\r\n";
        $sitemap .= "xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\"\r\n";
        $sitemap .= "xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\r\n";
        
        $modules= nkDB_select('SELECT nom FROM ' . MODULES_TABLE . ' WHERE niveau = 0');        
        
        // Foreach module, build XML tree
        foreach ($modules as $module) {
            if (!in_array($module['nom'], $excludedModules)){
                $sitemap .= "\t<url>\r\n";
                $sitemap .= '\t\t<loc>' . $nuked['url'] . '/index.php?file='. $module['nom'] . '</loc>\r\n';
                
                switch ($module['nom']) {  
                    case 'News' :
                        $lastNewsDate = nkDB_select('SELECT date FROM ' . NEWS_TABLE, array( 'date' ), 'DESC', 1);
                        $lastNewsDate = buildDateSitemapXML($lastNewsDate);
                        $sitemap .= "\t\t<priority>0.8</priority>\r\n";
                        $sitemap .= "\t\t<lastmod>$lastNewsDate</lastmod>\r\n";
                        $sitemap .= "\t\t<changefreq>daily</changefreq>\r\n";
                        break;
                    case 'Forum' :
                        $lastForumDate = nkDB_select('SELECT date FROM ' . FORUM_THREADS_TABLE, array( 'date' ), 'DESC', 1);
                        $lastForumDate = buildDateSitemapXML($lastForumDate);
                        $sitemap .= "\t\t<priority>0.4</priority>\r\n";
                        $sitemap .= "\t\t<lastmod>$lastForumDate</lastmod>\r\n";
                        $sitemap .= "\t\t<changefreq>always</changefreq>\r\n";
                        break;
                    case 'Download' :
                        $lastDownloadDate = nkDB_select('SELECT date FROM ' . DOWNLOAD_TABLE, array( 'date' ), 'DESC', 1);
                        $lastDownloadDate = buildDateSitemapXML($lastDownloadDate);
                        $sitemap .= "\t\t<priority>0.5</priority>\r\n";
                        $sitemap .= "\t\t<lastmod>$lastDownloadDate</lastmod>\r\n";
                        $sitemap .= "\t\t<changefreq>weekly</changefreq>\r\n";
                        break;
                    default :
                        $sitemap .= "\t\t<priority>0.5</priority>\r\n";
                        break;
                }
                $sitemap .= "\t</url>\r\n";
            }
        }

        $sitemap .= "</urlset>\r\n";
        
        // Add octed mark
        fwrite($resource, chr(0xEF) . chr(0xBB)  . chr(0xBF) . utf8_encode($sitemap));
        fclose($resource);
        
    }
}

/**
 * Build date with format like : "2013-08-01" for sitemap.xml
 * @param array $lastDate : array build like $lastDate[0]['date']
 * @return string : formatted date. ex : "2013-08-01"
 */
function buildDateSitemapXML($lastDate) {
    if (!empty($lastDate)
            && !empty($lastDate[0])
            &&!empty($lastDate[0]['date'])) {
        $lastDate = date('Y-m-d', $lastDate[0]['date']);
    } else {
        $lastDate = date('Y-m-d');
    }
    return $lastDate;
}

/* -------------------------------------------------------------------------------------*/

/* Agregation functions : In works... */

/**
 * Get the OS used by visitor.
 * @return string : OS used
 */
function getUserOperatingSystem() {

    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $os = 'Autre';

    $listOS = array(
        // Windows
        'Windows NT 6.2'       => 'Windows 8',
        'Windows NT 6.1'       => 'Windows 7',
        'Windows NT 6.0'       => 'Windows Vista',
        'Windows NT 5.2'       => 'Windows Server 2003',
        'Windows NT 5.1'       => 'Windows XP',
        'Windows NT 5.0'       => 'Windows 2000',
        'Windows 2000'         => 'Windows 2000',
        'Win 9x 4.90'          => 'Windows Me.',
        'Windows 98'           => 'Windows 98',
        
        // Tablets / mobiles
        'iPhone' => 'iPhone',
        'iPad' => 'iPad',
        'Android' => 'Android',
        'Windows Phone' => 'Windows Phone',

        // Linux
        'Ubuntu'               => 'Linux Ubuntu',
        'Fedora'               => 'Linux Fedora',
        'Linux'                => 'Linux',

        // Mac
        'Mac OS X'             => 'Mac OS X',
        'Mac_PowerPC'          => 'Mac OS X',
        'Mac'                  => 'Mac',

         // Autres
        'FreeBSD'              => 'FreeBSD',
        'Unix'                 => 'Unix',
        'Playstation portable' => 'PSP',
        'Playstation Vita' => 'PS Vita',
        'OpenSolaris'          => 'SunOS',
        'SunOS'                => 'SunOS',
        'Nintendo WiiU'         => 'Nintendo WiiU',
        'Nintendo Wii'         => 'Nintendo Wii',

        // Search Engines
        'msnbot'               => 'Microsoft Bing',
        'googlebot'            => 'Google Bot',
        'yahoo'                => 'Yahoo Bot'
    );

    $userAgent = strtolower($userAgent);

    foreach ($listOS as $key => $value) {
        if (stripos(strtolower($key), strtolower($userAgent) !== FALSE)) {
            $os = $value;
            break;
        }
    }
    return $os;
}

function getUserBrowser(){
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $browser = 'Autre';

    $list_browser = array(
        'Firefox'   => 'Firefox',
        'Lynx'      => 'Lynx',
        'Konqueror' => 'Konqueror',
        'Netscape'  => 'Netscape',
        'Opera'     => 'Opera',
        'MSIE'      => 'Internet Explorer',
        'Chrome'    => 'Google Chrome',
        'Safari'    => 'Apple Safari',
        'Mozilla'   => 'Mozilla',

        // Search Engines
        'msnbot'    => 'Microsoft Bing',
        'googlebot' => 'Google Bot',
        'yahoo'     => 'Yahoo Bot'
    );

    foreach( $list_browser as $k => $v ){
        if (preg_match("#".$k."#i", $user_agent)){
            $browser = $v;
            break;
        }
    }
    return $browser;

}
function erreursql($errno, $errstr, $errfile, $errline, $errcontext){
    global $user, $nuked, $language;

    switch ($errno){
        case E_WARNING:
            break;
        case 8192:
            break;
        case 8:
            break;
        default:
            $content = ob_get_clean();
            // CONNECT TO DB AND OPEN SESSION PHP
            if(file_exists('conf.inc.php')) include ('conf.inc.php');
            nkTryConnect();
            session_name('nuked');
            session_start();
            if (session_id() == '') exit(ERROR_SESSION);
            $date = time();
            echo ERROR_SQL;
            $texte = _TYPE . ': ' . $errno . _SQLFILE . $errfile . _SQLLINE . $errline;
            $upd = mysql_query("INSERT INTO " . $nuked['prefix'] . "_erreursql  (`date` , `lien` , `texte`)  VALUES ('" . $date . "', '" . mysql_escape_string($_SERVER["REQUEST_URI"]) . "', '" . $texte . "')");
            $upd2 = mysql_query("INSERT INTO " . $nuked['prefix'] . "_notification  (`date` , `type` , `texte`)  VALUES ('".$date."', '4', '" . _ERRORSQLDEDECTED . " : [<a href=\"index.php?file=Admin&page=erreursql\">" . _TLINK . "</a>].')");
            exit();
            break;
    }
    /* Ne pas exécuter le gestionnaire interne de PHP */
    return true;
}

function send_stats_nk() {
    global $nuked;

    if($nuked['stats_share'] == "1")
    {
        $timediff = (time() - $nuked['stats_timestamp'])/60/60/24/60; // Tous les 60 jours
        if($timediff >= 60) 
        {
			
            ?>
            <script type="text/javascript" src="modules/Admin/scripts/jquery-1.6.1.min.js"></script>
            <script type="text/javascript">
                //<![CDATA[
                $(document).ready(function() {
                                                data="nuked_nude=ajax";
                                                $.ajax({ url:'index.php', data:data, type: "GET", success: function(html){} });
                                                });
                //]]>
            </script>
            <?php
        }
    }
}

/* ---------------------------------- */
/* End version fusion 1.8 */
/* ---------------------------------- */

/* ************************************************************************************************************************* */


/**
 * Connection to DB.
 */
nkTryConnect();


/**
 * Query nuked 'CONFIG_TABLE'.
 */
$nuked = nkConstructNuked($db_prefix);

// Include constant table
include('Includes/constants.php');

// $_REQUEST['file'] & $_REQUEST['op'] DEFAULT VALUE.
if (empty($_REQUEST['file'])) $_REQUEST['file'] = $nuked['index_site'];
if (empty($_REQUEST['op'])) $_REQUEST['op'] = 'index';

// SELECT THEME, USER THEME OR NOT FOUND THEME : ERROR
if (isset($_REQUEST[$nuked['cookiename'] . '_user_theme'])
        && is_file(ROOT_PATH . 'themes/' . $nuked['user_theme'] . '/theme.php')) {
    $theme = $_REQUEST[$nuked['cookiename'] . '_user_theme'];
} elseif (is_file(ROOT_PATH . 'themes/' . $nuked['theme'] . '/theme.php')) {
    $theme = $nuked['theme'];
} else {
    exit(THEME_NOTFOUND);
}

// SELECT LANGUAGE AND USER LANGUAGE
if (isset($_REQUEST[$nuked['cookiename'] . '_user_langue'])
        && is_file(ROOT_PATH . 'lang/' . $nuked['user_lang'] . '.lang.php')) {
    $language = $_REQUEST[$nuked['cookiename'] . '_user_langue'];    
} else {
    $language =  $nuked['langue'];    
}



// FORMAT DATE FR/EN
if($language == 'french') {
    // On verifie l'os du serveur pour savoir si on est en windows (setlocale : ISO) ou en unix (setlocale : UTF8)
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') setlocale (LC_ALL, 'fr_FR','fra');
    else setlocale(LC_ALL, 'fr_FR.UTF8','fra');    
}
elseif($language == 'english') setlocale(LC_ALL, 'en_US');

// DATE FUNCTION WITH FORMAT AND ZONE FOR DATE
$dateZone = getTimeZoneDateTime($nuked['datezone']);
date_default_timezone_set($dateZone);
    



// Include configuration sessions
include ROOT_PATH . 'Includes/nkSessions.php';

/* *************************
 * Functions and variables to review...
 ************************* */

/**
 * $nuked['isBlock'] to delete
 */

/**
 * New pagination function (number()) ?
 * Create a page link list
 * @param int $total : The total number of subject
 * @param int $limit : The number of subjects per page
 * @param string $url : The basic url to redirect at page
 */
/*
function number($total, $limit, $url){
    
    $currentPage = $_REQUEST['p'];
    
    // If data is correct, we can build pagination
    if ($limit > 0 && $total > $limit) {
        if ($total <= 0) {
            $total   = 1;
        }
        // Current page
        if (!empty($currentPage)){
            $currentPage = $_REQUEST['p'];
        } else {
            $currentPage = 1;
        }
        // Number of pages
        $nbPages = ceil($total / intval($limit));
        // Start string to output display
        $output = '<div class="pages-links"><b class="pgtitle">' . _PAGE . ' :</b>&nbsp;';
        
        // Value of startup loop
        if ($current <= 2) {
            $start = 1;
        } else {
            $start = $current - 2;
        }
        // Value of end loop
        if (($current + 3) > $nbPages) {
            $end = $nbPages;
        } else {
            $end = $current + 3;
        }

        if (($current - 3) >= 1) {
            $output .= sprintf( '<a id="nkPage-1" href="%s&amp;p=%d" title="%s">1</a>&nbsp;', $url, 1, _FIRSTPAGE );
        }

        // It shows something to show that there are pages omitted if necessary
        if ($current > 4) {
            $output .= '...&nbsp;';
        }

        for ($i = $start; $i <= $end; $i++) {
            // If it's the curent page
            if ( $i == $current ) {
                $output .= sprintf( '<b class="page-active">[%d]</b>&nbsp;', $i );
            } else {
                $output .= sprintf( '<a id="nkPage-%1$d" href="%2$s&amp;p=%1$d">%1$d</a>&nbsp;', $i, $url );
            }
        }

        // It shows something to show that there are pages omitted if necessary
        if (($end + 1) < $nbPages) {
            $output .= '...&nbsp;';
        }

        // Number of decade to total pages
        $nbDecade = floor($nbPages / 10);

        // Number of first decade to display
        $firstDecade = ceil(($current + 5) / 10);

        for ($i = $firstDecade; $i <= $nbDecade; $i++) {
            $output .= sprintf( '<a id="nkPage-%d0" href="%s&amp;p=%d0">%d0</a>&nbsp;', $i, $url, $i, $i );
        }

        if (($current + 4) <= $n) {
            $output .= sprintf( '<a id="nkPage-%1$d" href="%2$s&amp;p=%1$d" title="%3$s">%1$d</a>', $n, $url, _LASTPAGE );
        }

        $output .= '</div>';
        
        echo $output;
    }
}
 * */

?>
