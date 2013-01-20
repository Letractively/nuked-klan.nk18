<?php
// -------------------------------------------------------------------------//
// Nuked-KlaN - PHP Portal                                                  //
// http://www.nuked-klan.org                                                //
// -------------------------------------------------------------------------//
// This program is free software. you can redistribute it and/or modify     //
// it under the terms of the GNU General Public License as published by     //
// the Free Software Foundation; either version 2 of the License.           //
// -------------------------------------------------------------------------//


/* ---------------------------------- */
/* Start version fusion 1.8 */
/* ---------------------------------- */

define('NK_START_TIME', microtime(true));
define('INDEX_CHECK', true);
define('ROOT_PATH', dirname( __FILE__ ) .'/');


// Kernel
include('nuked.php');


/* ---------------------------------- */
/* End version fusion 1.8 */
/* ---------------------------------- */

/***************************************************************************************************************************/

include_once('Includes/php51compatibility.php');
include('globals.php');


// INCLUDE FATAL ERROR LANG
//include('Includes/fatal_errors.php');

// POUR LA COMPATIBILITE DES ANCIENS THEMES ET MODULES - FOR COMPATIBITY WITH ALL OLD MODULE AND THEME
if (defined('COMPATIBILITY_MODE') && COMPATIBILITY_MODE == TRUE) extract($_REQUEST);

# Redirect to INSTALL
if (!defined('NK_INSTALLED')){
    if (file_exists('INSTALL/index.php')){
        header('location: INSTALL/index.php');
        exit();
    }
}

include_once('Includes/hash.php');

if ($nuked['time_generate'] == 'on'){
    $mtime = microtime();
}

// GESTION DES ERREURS SQL - SQL ERROR MANAGEMENT
//if(ini_get('set_error_handler')) set_error_handler('erreursql');

$session = session_check();
$user = ($session == 1) ? secure() : array();
$session_admin = admin_check();

if(isset($_REQUEST['nuked_nude']) && $_REQUEST['nuked_nude'] == 'ajax') {
    if($nuked['stats_share'] == 1) {
        $timediff = (time() - $nuked['stats_timestamp'])/60/60/24/60; // 60 Days
        if($timediff >= 60) {
            include('Includes/nkStats.php');
            $data = getStats($nuked);

            $string = serialize($data);

            $opts = array(
                  'http' => array(
                  'method' => "POST",
                  'content' => 'data=' . $string
                )
            );

            $context = stream_context_create($opts);
            $daurl = 'http://stats.nuked-klan.org/';
            $retour = file_get_contents($daurl, false, $context);
            $value_sql = ($retour == 'YES') ? mysql_real_escape_string(time()) : 'value + 86400';
            $sql = mysql_query('UPDATE ' . CONFIG_TABLE . ' SET value = ' . mysql_real_escape_string($value_sql) . ' WHERE name = "stats_timestamp"');

        }
    }
    die();
}

if (isset($_REQUEST['nuked_nude']) && !empty($_REQUEST['nuked_nude'])){
  $_REQUEST['im_file'] = $_REQUEST['nuked_nude'];
}else if (isset($_REQUEST['page']) && !empty($_REQUEST['page'])){
    $_REQUEST['im_file'] = $_REQUEST['page'];
}else{
    $_REQUEST['im_file'] = 'index';
}

if (preg_match('`\.\.`', $theme) || preg_match('`\.\.`', $language) || preg_match('`\.\.`', $_REQUEST['file']) || preg_match('`\.\.`', $_REQUEST['im_file']) || preg_match('`http\:\/\/`i', $_REQUEST['file']) || preg_match('`http\:\/\/`i', $_REQUEST['im_file']) || is_int(strpos( $_SERVER['QUERY_STRING'], '..' )) || is_int(strpos( $_SERVER['QUERY_STRING'], 'http://' )) || is_int(strpos( $_SERVER['QUERY_STRING'], '%3C%3F' ))){
    die(WHATAREYOUTRYTODO);
}

$_REQUEST['file'] = basename(trim($_REQUEST['file']));
$_REQUEST['im_file'] = basename(trim($_REQUEST['im_file']));
$_REQUEST['page'] = basename(trim($_REQUEST['im_file']));
$theme = trim($theme);
$language = trim($language);
$lang = substr($language, 0, 2);
// Check Ban
//$check_ip = banip();

if (!$user){
    $visiteur = 0;
    $_SESSION['admin'] = false;
}
else $visiteur = $user[1];

//include ('themes/' . $theme . '/colors.php');
translate('lang/' . $language . '.lang.php');

if ($nuked['nk_status'] == 'closed' && $user[1] < 9 && $_REQUEST['op'] != 'login_screen' && $_REQUEST['op'] != 'login_message' && $_REQUEST['op'] != 'login'){
?>
    <!DOCTYPE html>
    <html lang="<?php echo $lang; ?>">
        <head>
            <title><?php echo $nuked['name']; ?>&nbsp;-&nbsp;<?php echo $nuked['slogan']; ?></title>
            <meta charset="utf-8" />
            <link title="style" type="text/css" rel="stylesheet" href="css/nkCss.css" />
        </head>
        <body id="nkSiteClose">
            <section>
                <header>
                    <hgroup>
                        <img src="images/logo.png" />
                        <h1><?php echo $nuked['name']; ?></h1>
                        <h2><?php echo $nuked['slogan']; ?></h2>
                    </hgroup>
                </header>
                <article>
                    <p><?php echo SITECLOSED; ?></p>
                    <form action="index.php?file=User&amp;nuked_nude=index&amp;op=login" method="post">
                        <div>
                            <label for="pseudo"><?php echo PSEUDO; ?></label>
                                <input id="pseudo" type="text" name="pseudo" size="15" maxlength="180" />
                         </div>
                        <div>
                            <label for="password"><?php echo PASSWORD; ?></label>
                                <input type="password" id="password" name="pass" size="15" maxlength="15" />                        
                                <input type="hidden" class="checkbox" name="remember_me" value="ok" checked="checked" />
                        </div>
                                <input type="submit" value="<?php echo TOLOG; ?>" />      
                    </form>         
                </article>
                <footer>
                    <p>
                        <a href="/"><?php echo $nuked['name']; ?></a> &copy; 2001, <?php echo date(Y); ?>&nbsp;|&nbsp;<?php echo POWERED; ?> <a href="http://www.nuked-klan.org">Nuked-Klan</a>
                    </p>
                </footer>
            </section>
        </body>
    </html>
<?php
}else if (($_REQUEST['file'] == 'Admin' || $_REQUEST['page'] == 'admin' || (isset($_REQUEST['nuked_nude']) && $_REQUEST['nuked_nude'] == 'admin')) && $_SESSION['admin'] == 0){
    include('modules/Admin/login.php');
}else if (($_REQUEST['file'] != 'Admin' AND $_REQUEST['page'] != 'admin') || ( nivo_mod($_REQUEST['file']) === false || (nivo_mod($_REQUEST['file']) > -1 && (nivo_mod($_REQUEST['file']) <= $visiteur))) ){
    include ('themes/' . $theme . '/theme.php');

    if ($nuked['level_analys'] != -1) visits();

    if (!isset($_REQUEST['nuked_nude'])){
        if (defined('NK_GZIP') && ini_get('zlib_output')){
            ob_start('ob_gzhandler');
        }

        if (!($_REQUEST['file'] == 'Admin' || $_REQUEST['page'] == 'admin' || (isset($_REQUEST['nuked_nude']) && $_REQUEST['nuked_nude'] == 'admin')) || $_REQUEST['page'] == 'login') top();

        if($user[1] == 9 && $_REQUEST['file'] != 'Admin' && $_REQUEST['page'] != 'admin'){ 
            if (is_dir('INSTALL/')){            
                echo $nkTpl->nkDisplayError(REMOVEDIRINST);            
            }
            if (file_exists('install.php')){            
                echo $nkTpl->nkDisplayError(REMOVEINST);             
            }
            if (file_exists('install.php')){            
                echo $nkTpl->nkDisplayError(REMOVEUPDATE);         
            }
        }
    }
    else
        header('Content-Type: text/html;charset=utf-8');

    if (is_file('modules/' . $_REQUEST['file'] . '/' . $_REQUEST['im_file'] . '.php')){
        include('modules/' . $_REQUEST['file'] . '/' . $_REQUEST['im_file'] . '.php');
    }
    else include('modules/404/index.php');    

    if (!isset($_REQUEST['nuked_nude'])){
        
        if (!($_REQUEST['file'] == 'Admin' || $_REQUEST['page'] == 'admin') || $_REQUEST['page'] == 'login'){
            footer();
        }

        include('Includes/copyleft.php');

        if ($nuked['time_generate'] == 'on'){
            $mtime = microtime() - $mtime;
        ?>
            <p class="nkCenter"><?php echo GENERATE.'&nbsp;'.${mtime}; ?>s</p>
        <?php
        }
        //@todo reactive and test it when head inclusion is done
        //sendStatsNk();
    }
}else{  
    include ('themes/' . $theme . '/theme.php');
    top();
    translate('lang/' . $language . '.lang.php');
    badLevel();
    footer();
}

nkDB_disconnect();

/**
 * Error display
 */
if ( defined( 'NK_ERROR_DEBUG' ) && NK_ERROR_DEBUG && isset( $GLOBALS['nk_error'] ) )
{
    include ROOT_PATH .'Includes/nkDebug.php';
}

/*********************
 * TODO
 *********************/
/*
 * Rename class case NK_ (see Architecture_1.8)
 * 
/*********************
 * Informations
 *********************
 * 
 * $GLOBALS['nuked'] : array contains globals informations (date, theme,...)
      'prefix' => string : prefix of database
      'time_generate' => string : 'on' or 'off' for time generation
      'dateformat' => string : dateformat with PHP pattern (see PHP doc
      'datezone' => string : time zone
      'version' => string : version of NK.
      'date_install' => string : timestamp of installation date
      'langue' => string : used language (french, english)
      'stats_share' => string : activation of statistics ('0' if off, else 1 if is 'on')
      'stats_timestamp' => string '0' (length=1)
      'name' => string : website name
      'slogan' => string : slogan of website
     * 
      @todo : will be delete
      'tag_pre' => string 
      'tag_suf' => string 
      @todo : will be delete
     * 
      'url' => string : url of website
      'mail' => string administrator mail
      'footmessage' => string : message on footer website
      'nk_status' => string : 'open' if website is open, else 'closed'
      'index_site' => string : name of main module on website
      'theme' => string : name of default theme activated for all users
      'keyword' => string : keywords used for SEO (tag HTML)
      'description' => string : description used for SEO (tag HTML)
      'inscription' => string : if 'on', inscription is activated, else 'off'
      'inscription_mail' => string : mail send after inscription
      'inscription_avert' => string : text display before inscription
      'inscription_charte' => string : text (charte) display before inscription
      'validation' => string : status of inscription validation : 'auto', of manual
      'user_delete' => string : authorization for an user to delete or not his account ('on' or 'off')
      'video_editeur' => string : activation or no to use video editor ('on' or 'off')
      'scayt_editeur' => string 'on' (length=2)
      'suggest_avert' => string '' (length=0)
      'irc_chan' => string 'nuked-klan' (length=10)
      'irc_serv' => string 'quakenet.org' (length=12)
      'server_ip' => string '' (length=0)
      'server_port' => string '' (length=0)
      'server_pass' => string '' (length=0)
      'server_game' => string '' (length=0)
      'forum_title' => string '' (length=0)
      'forum_desc' => string '' (length=0)
      'forum_rank_team' => string 'off' (length=3)
      'forum_field_max' => string '10' (length=2)
      'forum_file' => string 'on' (length=2)
      'forum_file_level' => string '1' (length=1)
      'forum_file_maxsize' => string '1000' (length=4)
      'thread_forum_page' => string '20' (length=2)
      'mess_forum_page' => string '2' (length=1)
      'hot_topic' => string '20' (length=2)
      'post_flood' => string '10' (length=2)
      'gallery_title' => string '' (length=0)
      'max_img_line' => string '2' (length=1)
      'max_img' => string '6' (length=1)
      'max_news' => string '5' (length=1)
      'max_download' => string '10' (length=2)
      'hide_download' => string 'on' (length=2)
      'max_liens' => string '10' (length=2)
      'max_sections' => string '10' (length=2)
      'max_wars' => string '30' (length=2)
      'max_archives' => string '30' (length=2)
      'max_members' => string '30' (length=2)
      'max_shout' => string '20' (length=2)
      'mess_guest_page' => string '10' (length=2)
      'sond_delay' => string '24' (length=2)
      'level_analys' => string '-1' (length=2)
      'visit_delay' => string '10' (length=2)
      'recrute' => string '1' (length=1)
      'recrute_charte' => string '' (length=0)
      'recrute_mail' => string '' (length=0)
      'recrute_inbox' => string '' (length=0)
      'defie_charte' => string '' (length=0)
      'defie_mail' => string '' (length=0)
      'defie_inbox' => string '' (length=0)
      'birthday' => string 'all' (length=3)
      'avatar_upload' => string 'on' (length=2)
      'avatar_url' => string 'on' (length=2)
      'cookiename' => string 'nuked' (length=5)
      'sess_inactivemins' => string '5' (length=1)
      'sess_days_limit' => string '365' (length=3)
      'nbc_timeout' => string '300' (length=3)
      'screen' => string 'on' (length=2)
      'contact_mail' => string 'admin@admin.com' (length=15)
      'contact_flood' => string '60' (length=2)
 * 
 * $GLOBALS['language'] : user language defined
 * 
 * $GLOBALS['user'] : user informations
    [0] = ID visitor
    [1] = user level
    [2] = pseudo
    [3] = IP address
    [4] = number of new messages unread
 
 * $GLOBALS['user_ip'] : IP address user
 * $GLOBALS['nkTpl'] : light template library
 * $GLOBALS['nuked']['stats_share']
 */
?>
