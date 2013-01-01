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
/* ---------------------------------- */

// Include configuration constants
if (file_exists('conf.inc.php')) {
    include('conf.inc.php');
}

// Sets which PHP errors are reported, use error debug popup

if ( defined( 'NK_ERROR_DEBUG' ) && NK_ERROR_DEBUG ) {
    require ROOT_PATH . 'includes/libs/NK_Exception.php';
}

//Include Light Tpl library
require ROOT_PATH . 'includes/libs/NK_Tpl.php';
$nkTpl = NK_Tpl::getInstance();

// Include DB library
require ROOT_PATH . 'includes/libs/NK_' . $db_type .'.php';


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
        exit;
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
        exit;
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
    } else if(isset($_COOKIE['ip_ban']) && !empty($_COOKIE['ip_ban'])) { // Seach cookie banish
        // On supprime le dernier chiffre de l'adresse IP contenu dans le cookie
        $ipDynCookie = substr($_COOKIE['ip_ban'], 0, -1);

        // Check IP cookie and current IP address
        if($ipDynCookie == $ipDyn) {
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
            $values = array( time(), '4', mysql_real_escape_string($pseudo . _BANFINISHED));
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
 * @global array $user : user information
 * @global array $nuked
 * @param string $side : side block to display
 */
function get_blok($side){
    global $user, $nuked, $nkTpl;
    
    if ($side == 'gauche') {
        $active = 1;
        $nuked['isBlock'] = TRUE;
    } else if ($side == 'droite') {
        $active = 2;
        $nuked['isBlock'] = TRUE;
    } else if ($side == 'centre') {
        $active = 3;
    } else if ($side == 'bas') {
        $active = 4;
    } else {
        echo $nkTpl->nkDisplayError(UNKNOWN_BLOCK);
        /**
         * @todo Add log
         */
        exit;
    }
    
    // Set the name of the function to call
    $functionBlock = 'block_' . $side;

    // Get list of blocks
    $blockList = nkDB_select('SELECT bid, active, position, module, titre, content, type, nivo, page FROM ' .
            BLOCK_TABLE . ' WHERE active = ' . $active . ' ORDER BY position');
    
    foreach ($blockList as $block) {
        $block['titre'] = printSecuTags($block['titre']);
        // Split each type of block in array
        $block['page'] = explode('|', $block['page']);
        // Number of blocks page
        $size = count($block['page']);
        
        // If we find a block page, a flag is set for including the associated block
        for ($i=0; $i<$size; $i++) {
            if (isset($_REQUEST['file']) && $_REQUEST['file'] == $block['page'][$i] || $block['page'][$i] == 'Tous') {
                $findPage = TRUE;
                break;
            }
        }
        
        // Level of user
        if (!empty($user)) {
            $visiteur = $user[1];
        } else {
            $visiteur = 0;
        }

        // If page found, we included the associated block
        if ($visiteur >= $block['nivo'] && isset($findPage)) {
            if (file_exists('includes/blocks/block_' . $block['type'] . '.php')) {
                include_once('includes/blocks/block_' . $block['type'] . '.php');
            }
            // Call function 'affich_block_*' of NK block
            $block = call_user_func('affich_block_' . $block['type'], $block);
            if (!empty($block['content'])) {
                // Call function block_*' of the theme
                call_user_func('block_' . $side, $block);
            }
        }
    }
    $nuked['isBlock'] = FALSE;
}

/**
 * Block display of pictures.
 * @param string $url : url to block
 * @return string $url : protected url
 */
function checkimg($url){
    // Get extension of url
    $url = rtrim($url);
    $ext = strrchr($url, '.');
    $ext = substr($ext, 1);

    if (preg_match('#\.(([a-z]?)htm|php)#i', $url) || substr($url, -1) == '/' || !preg_match('#jpg|jpeg|gif|png|bmp#i', $ext) ) {
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
    
    
    $smiliesList = nkDB_select('SELECT code, url, name FROM ' .
            SMILIES_TABLE . ' ORDER BY id');
    
    foreach($smiliesList as $smiley) {
        $texte = str_replace($smiley['code'], '<img src="images/icones/' . $smiley['url'] . '" alt="" title="' . htmlentities($smiley['name']) . '" />', $texte);
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
 * Configure smilies for CKEditor.
 * @return string : string for configuration of CKEditor smilies.
 * @todo faire une seule requête (stocker les données en globals ?)
 * car la méthode est appelée 2 fois par affichage de page, ainsi qu'à chaque appel de la méthode icon()
 */
function configSmiliesCKEditor(){
    // Smilies path configuration for CKeditor.
    $configCK = 'CKEDITOR.config.smiley_path=\'images/icones/\';';
    
    $smiliesList = nkDB_select('SELECT code, url, name FROM ' .
            SMILIES_TABLE . ' ORDER BY id');
    
    // Construct array data for smilies
    foreach ($smiliesList as $smiley) {
        $smiliesCode[] = addslashes($smiley['code']);
        $smiliesUrl[] = $smiley['url'];
        $smiliesName[] = htmlentities($smiley['name']);
    }

    // Number of smilies
    $nbSmilies = count($smiliesList);
    
    // Build array config images
    $configCK .= 'CKEDITOR.config.smiley_images=[';
    for ($i = 0; $i < $nbSmilies - 1; $i++) {
        $configCK .= '\'' . $smiliesUrl[$i] . '\', ';
    }
    $configCK .= '\'' . $smiliesUrl[$nbSmilies] . '\'];';
    
    // Build array config descriptions
    $configCK .= 'CKEDITOR.config.smiley_descriptions=[';
    for ($i = 0; $i < $nbSmilies - 1; $i++) {
        $configCK .= '\'' . $smiliesCode[$i] . '\', ';
    }
    $configCK .= '\'' . $smiliesCode[$nbSmilies] . '\'];';
    
    // Build array config titles
    $configCK .= 'CKEDITOR.config.smiley_titles=[';
    for ($i = 0; $i < $nbSmilies - 1; $i++) {
        $configCK .= '\'' . $smiliesName[$i] . '\', ';
    }
    $configCK .= '\'' . $smiliesName[$nbSmilies] . '\'];';
    
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

/* -------------------------------------------------------------------------------------*/

/* Agregation functions : In works... */

// DISPLAYS THE NUMBER OF PAGES
function number($count, $each, $link){

    $current = $_REQUEST['p'];

    if ($each > 0){
        if ($count <= 0)     $count   = 1;
        if (empty($current)) $current = 1; // On renormalise la page courante...
        // Calcul du nombre de pages
        $n = ceil($count / intval($each)); // on arrondit à  l'entier sup.
        // Début de la chaine d'affichage
        $output = '<b class="pgtitle">' . _PAGE . ' :</b> ';
        
        for ($i = 1; $i <= $n; $i++){
            if ($i == $current){
                $output .= sprintf('<b class="pgactuel">[%d]</b> ',$i    );
            }
            // On est autour de la page actuelle : on affiche
            elseif (abs($i - $current) <= 4){
                $output .= sprintf('<a href="' . $link . '&amp;p=%d" class="pgnumber">%d</a> ',$i, $i);
            }
            // On affiche quelque chose avant d'omettre les pages inutiles
            else{
                // On est avant la page courante
                if (!isset($first_done) && $i < $current){
                    $output .= sprintf('...<a href="' . $link . '&amp;p=%d" title="' . _PREVIOUSPAGE . '" class="pgback">&laquo;</a> ',$current-1);
                    $first_done = true;
                }
                // Après la page courante
                elseif (!isset($last_done) && $i > $current){
                    $output .= sprintf('<a href="' . $link . '&amp;p=%d" title="' . _NEXTPAGE . '" class="pgnext">&raquo;</a>... ',$current+1);
                    $last_done = true;
                }
                // On a dépassé les cas qui nous intéressent : inutile de continuer
                elseif ($i > $current)
                    break;
            }
        }
        $output .= '<br />';
        echo $output;
    }
}

function nbvisiteur(){
    global $user, $nuked, $user_ip;

    $limite = time() + $nuked['nbc_timeout'];
    $time = time();

    $req = mysql_query("DELETE FROM " . NBCONNECTE_TABLE . " WHERE date < '" . $time."'");

    if (isset($user_ip)){
        if (isset($user[0])){
            $where = "WHERE user_id='" . $user[0] . "'";
        }
        else{
            $where = "WHERE IP='" . $user_ip . "'";
        }
        $req = mysql_query("SELECT IP FROM " . NBCONNECTE_TABLE . " " . $where);
        $query = mysql_num_rows($req);

        if ($query > 0){
            if (isset($user[0])){
                $req = mysql_query("UPDATE " . NBCONNECTE_TABLE . " SET date = '" . $limite . "', type = '" . $user[1] . "', IP = '" . $user_ip . "', username = '" . $user[2] . "' WHERE user_id = '" . $user[0] . "'");
            }
            else{
                $req = mysql_query("UPDATE " . NBCONNECTE_TABLE . " SET date = '" . $limite . "', type = '" . $user[1] . "', user_id = '" . $user[0] . "', username = '" . $user[2] . "' WHERE IP = '" . $user_ip . "'");
            }
        }
        else{
            $del = mysql_query("DELETE FROM " . NBCONNECTE_TABLE . " WHERE IP = '" . $user_ip . "'");
            $req = mysql_query("INSERT INTO " . NBCONNECTE_TABLE . " ( `IP` , `type` , `date` , `user_id` , `username` ) VALUES ( '" . $user_ip . "' , '" . $user[1] . "' , '" . $limite . "' , '" . $user[0] . "' , '" . $user[2] . "' )");
        }
    }

    $res = mysql_query("SELECT type FROM " . NBCONNECTE_TABLE . " WHERE type = 0");
    $count[0] = mysql_num_rows($res);
    $res = mysql_query("SELECT type FROM " . NBCONNECTE_TABLE . " WHERE type BETWEEN 1 AND 2");
    $count[1] = mysql_num_rows($res);
    $res = mysql_query("SELECT type FROM " . NBCONNECTE_TABLE . " WHERE type > 2");
    $count[2] = mysql_num_rows($res);
    $count[3] = $count[1] + $count[2];
    $count[4] = $count[0] + $count[3];
    return $count;
}

function nivo_mod($mod){
    $sql = mysql_query("SELECT niveau FROM " . MODULES_TABLE . " WHERE nom = '" . $mod . "'");
    if (mysql_num_rows($sql) == 0){
        return false;
    }
    else{
        list($niveau) = mysql_fetch_array($sql);
        return $niveau;
    }
}

function admin_mod($mod){
    $sql = mysql_query("SELECT admin FROM " . MODULES_TABLE . " WHERE nom = '" . $mod . "'");
    list($admin) = mysql_fetch_array($sql);
    return $admin;
}

function translate($file_lang){
    global $nuked;

    ob_start();
    print eval(" include ('$file_lang'); ");
    $lang_define = ob_get_contents();
    $lang_define = htmlentities($lang_define, ENT_NOQUOTES);
    $lang_define = str_replace('&lt;', '<', $lang_define);
    $lang_define = str_replace('&gt;', '>', $lang_define);
    ob_end_clean();
    return $lang_define;
}

function compteur($file){
    $upd = mysql_query('UPDATE ' . STATS_TABLE . ' SET count = count + 1 WHERE type = "pages" AND nom = "' . $_GET['file'] . '"');
}

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

function visits(){
    global $nuked, $user_ip, $user;

    $time = time();
    $timevisit = $nuked['visit_delay'] * 60;
    $limite = $time + $timevisit;

    $sql_where = ($user) ? 'user_id = "' . $user[0] : 'ip = "' . $user_ip;
    $sql = mysql_query('SELECT id, date FROM ' . STATS_VISITOR_TABLE . ' WHERE ' . $sql_where . '" ORDER by date DESC LIMIT 0, 1');

    list($id, $date) = mysql_fetch_array($sql);

    if (isset($id) && $date > $time){
        $upd = mysql_query("UPDATE " . STATS_VISITOR_TABLE . " SET  date = '" . $limite . "' WHERE id = '" . $id . "'");
    }
    else{
        $month = strftime('%m', $time);
        $year = strftime('%Y', $time);
        $day = strftime('%d', $time);
        $hour = strftime('%H', $time);
        $user_referer = mysql_escape_string($_SERVER['HTTP_REFERER']);
        $user_host = strtolower(@gethostbyaddr($user_ip));
        $user_agent = mysql_escape_string($_SERVER['HTTP_USER_AGENT']);

        if ($user_host == $user_ip) $host = '';
        else{
            if (preg_match('`([^.]{1,})((\.(co|com|net|org|edu|gov|mil))|())((\.(ac|ad|ae|af|ag|ai|al|am|an|ao|aq|ar|as|at|au|aw|az|ba|bb|bd|be|bf|bg|bh|bi|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|cr|cu|cv|cx|cy|cz|de|dj|dk|dm|do|dz|ec|ee|eg|eh|er|es|et|fi|fj|fk|fm|fo|fr|fx|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|in|io|iq|ir|is|it|je|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|mg|mh|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|mv|mw|mx|my|mz|na|nc|ne|nf|ng|ni|nl|no|np|nr|nt|nu|nz|om|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|pt|pw|py|qa|re|ro|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sy|sz|tc|td|tf|tg|th|tj|tk|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|um|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zr|zw))|())$`', $user_host, $res))
                $host = $res[0];
        }

        $browser = getBrowser();
        $os = getOS();
        $sql2 = mysql_query("INSERT INTO " . STATS_VISITOR_TABLE . " ( `id` , `user_id` , `ip` , `host` , `browser` , `os` , `referer` , `day` , `month` , `year` , `hour` , `date` ) VALUES ( '' , '" . $user[0] . "' , '" . $user_ip . "' , '" . $host . "' , '" . $browser . "' , '" . $os . "' , '" . $user_referer . "' , '" . $day . "' , '" . $month . "' , '" . $year . "' , '" . $hour . "' , '" . $limite . "' )");
    }
}

function verif_pseudo($string = null, $old_string = null) {
    global $nuked;

    $string = trim($string);

    if (empty($string) || preg_match("`[\$\^\(\)'\"?%#<>,;:]`", $string)) {
        return 'error1';
    }

    if($string != $old_string) {
        $sql = mysql_query('SELECT pseudo FROM ' . USER_TABLE . ' WHERE pseudo = "' . $string . '"');
        $is_reg = mysql_num_rows($sql);
        if ($is_reg > 0) {
            return 'error2';
        }
    }

    $sql2 = mysql_query('SELECT pseudo FROM ' . BANNED_TABLE . ' WHERE pseudo = "' . $string . '"');
    $is_reg2 = mysql_num_rows($sql2);
    if ($is_reg2 > 0) {
        return 'error3';
    }

    return $string;
}

function UpdateSitmap(){
    global $nuked;
    $Disable = array('Suggest', 'Comment', 'Vote', 'Textbox', 'Members');

    $fp = fopen(dirname(__FILE__).'/sitemap.xml', 'wb');
    if ($fp !== false){
        $Sitemap = "<?xml version='1.0' encoding='UTF-8'?>\r\n";
        $Sitemap .= "<urlset xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\r\n";
        $Sitemap .= "xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\"\r\n";
        $Sitemap .= "xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\r\n";

        $sql = 'SELECT nom FROM ' . MODULES_TABLE . ' WHERE niveau = 0';
        $mods = mysql_query($sql);

        while(list($mod) = mysql_fetch_row($mods)){
            if (!in_array($mod, $Disable)){
                $Sitemap .= "\t<url>\r\n";
                $Sitemap .= "\t\t<loc>$nuked[url]/index.php?file=$mod</loc>\r\n";
                switch($mod){
                    case 'News':
                        $Last = mysql_query('SELECT date FROM ' . NEWS_TABLE . 'ORDER BY date DESC LIMIT 1');
                        $Last = date('Y-m-d');
                        $Sitemap .= "\t\t<priority>0.8</priority>\r\n";
                        $Sitemap .= "\t\t<lastmod>$Last</lastmod>\r\n";
                        $Sitemap .= "\t\t<changefreq>daily</changefreq>\r\n";
                        break;
                    case 'Forum':
                        $Sitemap .= "\t\t<priority>0.4</priority>\r\n";
                        $Sitemap .= "\t\t<lastmod>$Last</lastmod>\r\n";
                        $Sitemap .= "\t\t<changefreq>always</changefreq>\r\n";
                        break;
                    case 'Download':
                        $Last = mysql_query('SELECT date FROM ' . DOWNLOAD_TABLE . 'ORDER BY date DESC LIMIT 1');
                        $Last = date('Y-m-d');
                        $Sitemap .= "\t\t<priority>0.5</priority>\r\n";
                        $Sitemap .= "\t\t<lastmod>$Last</lastmod>\r\n";
                        $Sitemap .= "\t\t<changefreq>weekly</changefreq>\r\n";
                        break;

                    default:
                        $Sitemap .= "\t\t<priority>0.5</priority>\r\n";
                } // switch
                $Sitemap .= "\t</url>\r\n";
            }
        }

        $Sitemap .= "</urlset>\r\n";
        fwrite($fp, chr(0xEF) . chr(0xBB)  . chr(0xBF) . utf8_encode($Sitemap)); //Ajout de la marque d'Octet
        fclose($fp);
    }
}

function getOS(){

    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $os = 'Autre';

    $list_os = array(
        // Windows
        'Windows NT 6.1'       => 'Windows 7',
        'Windows NT 6.0'       => 'Windows Vista',
        'Windows NT 5.2'       => 'Windows Server 2003',
        'Windows NT 5.1'       => 'Windows XP',
        'Windows NT 5.0'       => 'Windows 2000',
        'Windows 2000'         => 'Windows 2000',
        'Windows CE'           => 'Windows Mobile',
        'Win 9x 4.90'          => 'Windows Me.',
        'Windows 98'           => 'Windows 98',
        'Windows 95'           => 'Windows 95',
        'Win95'                => 'Windows 95',
        'Windows NT'           => 'Windows NT',

        // Linux
        'Ubuntu'               => 'Linux Ubuntu',
        'Fedora'               => 'Linux Fedora',
        'Linux'                => 'Linux',

        // Mac
        'Macintosh'            => 'Mac',
        'Mac OS X'             => 'Mac OS X',
        'Mac_PowerPC'          => 'Mac OS X',

         // Autres
        'FreeBSD'              => 'FreeBSD',
        'Unix'                 => 'Unix',
        'Playstation portable' => 'PSP',
        'OpenSolaris'          => 'SunOS',
        'SunOS'                => 'SunOS',
        'Nintendo Wii'         => 'Nintendo Wii',
        'Mac'                  => 'Mac',

        // Search Engines
        'msnbot'               => 'Microsoft Bing',
        'googlebot'            => 'Google Bot',
        'yahoo'                => 'Yahoo Bot'
    );

    $user_agent = strtolower( $user_agent );

    foreach( $list_os as $k => $v ){
        if (preg_match("#".strtolower($k)."#", strtolower($user_agent))){
            $os = $v;
            break;
        }
    }
    return $os;
}

function getBrowser(){
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
include('includes/constants.php');

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
include ROOT_PATH . 'includes/nkSessions.php';

/* *************************
 * Functions and variables to review...
 ************************* */

/**
 * $nuked['isBlock']
 */



?>
