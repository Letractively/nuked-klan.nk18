<?php

//-------------------------------------------------------------------------//
//  Nuked-KlaN - PHP Portal                                                //
//  http://www.nuked-klan.org                                              //
//-------------------------------------------------------------------------//
//  This program is free software. you can redistribute it and/or modify   //
//  it under the terms of the GNU General Public License as published by   //
//  the Free Software Foundation; either version 2 of the License.         //
//-------------------------------------------------------------------------//

/**
 * Temporary file for developper (debug)
 */
if (!defined('INDEX_CHECK')) {
    die('You cannot open this page directly');
}

// Display the popup for debugging
define('NK_DEBUG', true);

if (NK_DEBUG) {
    nkErrorDebug();
}

/**
 * Generate a SQL Debug pop-up for all databases
 */
function nkSQLDebug() {
    // If it's a nuked_nude page...
    if (isset($_GET['nuked_nude'])) {
        // Prepare sql log file
        // Add server request uri
        $txt = ' REQUEST_URI : ' . $_SERVER['REQUEST_URI'] . "\r\n\r\n";

        // Add connexion status
        foreach ($GLOBALS['nkDB']['status_connection'] as $cmd) {
            $txt .= "\t- " . $cmd[0] . ' - ' . html_entity_decode(stripslashes($cmd[1])) . "\r\n";
        }

        // Prepare sql query status list
        $txt .= "\r\n";
        $num = 1;

        foreach ($GLOBALS['nkDB']['status'] as $query) {
            $txt .= "\t" . $num . ' - ' . html_entity_decode(stripslashes($query[0]));

            // Add execution time if exist
            if (array_key_exists(2, $query) && $query[2] != '') {
                $txt .= ' - ' . round(( $query[2] * 1000), 3) . ' ms';
            }

            // Add sql error if exist
            if ($query[1] != 'ok') {
                $txt .= ' - ' . $query[1];
            }

            $txt .= "\r\n\r\n";

            $num++;
        }

        // Create debug directory if don't exist
        if (!is_dir('./debug/sql')) {
            createDebugDirectory('sql');
        }

        // Write sql log file
        file_put_contents('./debug/sql/' . time() . '.txt', $txt);
    }
    // ...Or display the popup
    else {
        // Prepare popup content
        $html = '<table id="connect-status" border="1" cellpadding="5">\n';

        // Add connexion status
        foreach ($GLOBALS['nkDB']['status_connection'] as $cmd) {
            $html .= '\t<tr>\n\t\t<td>' . $cmd[0] . '</td>\n\t\t<td>' . $cmd[1] . '</td>\n\t</tr>\n';
        }

        // Prepare sql query status list
        $html .= '</table>\n<table id="sql-status" border="1" cellpadding="3">\n';
        $num = 1;

        foreach ($GLOBALS['nkDB']['status'] as $query) {
            $req = str_replace("\n", "", $query[0]);
            $req = str_replace("\r", "<br />", $req);
            $req = str_replace("\t", "&nbsp;&nbsp;&nbsp;", $req);

            if ($query[1] != 'ok') {
                $rowspan = 'rowspan="2"';
                $class = 'class="error"';
                $error = '<tr><td>' . $query[1] . '</td></tr>\n';
            } else {
                $rowspan = '';
                $class = 'class="valide"';
                $error = '';
            }

            // Add execution time if exist
            if (array_key_exists(2, $query) && $query[2] != '') {
                $sql_time = '<td style="width:60px;">' . round(( $query[2] * 1000), 3) . ' ms</td>';
            } else {
                $sql_time = '';
            }

            $html .= '\t<tr ' . $class . '>\n\t\t<td ' . $rowspan . ' class="left">' . $num . '</td><td>' . $req . '</td>\n' . $sql_time . $error . '\t</tr>\n';
            $num++;
        }

        $html .= '</table>';

        // Add javascript to generate the sql debug popup
        echo '<script type="text/javascript">' . "\n"
        . '// <![CDATA[' . "\n"
        . 'Sql_console = window.open("","","width=800,height=600,resizable,scrollbars=yes");' . "\n"
        . 'Sql_console.document.write(\'<!DOCTYPE html>\n<html lang="fr">\n<head>\n<title>SQL DEBUG</title>\n<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />\n<link rel="stylesheet" type="text/css" href="css/nkDebug.css" media="screen" />\n</head>\n<body>\n<h1>SQL DEBUG</h1>\n' . $html . '\n</body>\n</html>\n\');' . "\n"
        . 'Sql_console.document.close();' . "\n"
        . '// ]]>' . "\n"
        . '</script>';
    }
}

/**
 * Generate a Error Debug pop-up.
 */
function nkErrorDebug() {
    // If it's a nuked_nude page...
    if (isset($_GET['nuked_nude'])) {
        // Prepare error log file
        // Add server request uri
        $txt = ' REQUEST_URI : ' . $_SERVER['REQUEST_URI'] . "\r\n\r\n";
        $num = 1;

        // Prepare error message list
        foreach ($GLOBALS['nk_error'] as $query) {
            $query['msg'] = str_replace('<hr />', "\r\n\t\t", $query['msg']);
            $query['msg'] = str_replace('<b>', '', $query['msg']);
            $query['msg'] = str_replace('</b>', '', $query['msg']);

            // Add error message if exist
            $txt .= "\t" . $num . ' - ' . stripslashes($query['msg']) . "\r\n\r\n";

            $num++;
        }

        // Create debug directory if don't exist
        if (!is_dir('./debug/error')) {
            createDebugDirectory('error');
        }

        // Write error log file
        file_put_contents('./debug/error/' . time() . '.txt', $txt);
    }
    // ...Or display the popup
    else {
        // Prepare popup content
        $html = '<table id="error" border="1" cellpadding="3">\n'
                . '<tr>\n<th class="number">Nb</th>\n<th class="type">Type</th>\n<th>Message</th>\n</tr>';
        $num = 1;

        // Prepare error message list
        foreach ($GLOBALS['nk_error'] as $query) {
            $error = str_replace("\n", '', $query['msg']);
            $error = str_replace("\r", '<br />', $error);
            $error = str_replace("\t", '&nbsp;&nbsp;&nbsp;', $error);

            $html .= '<tr><td class="number">' . $num . '</td><td class="type">' . $query['type'] . '</td><td>' . $error . '</td></tr>';
            $num++;
        }

        $html .= '</table>';

        // Add javascript to generate the error debug popup
        echo '<script type="text/javascript">' . "\n"
        . '// <![CDATA[' . "\n"
        . 'Error_console = window.open("","","width=800,height=600,resizable,scrollbars=yes");' . "\n"
        . 'Error_console.document.write(\'<!DOCTYPE html>\n<html lang="fr">\n<head>\n<title>ERROR DEBUG</title>\n<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />\n<link rel="stylesheet" type="text/css" href="css/nkDebug.css" media="screen" /><meta http-equiv="Expires" content="0" />\n<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />\n</head>\n<body>\n<h1>ERROR DEBUG</h1>\n' . $html . '\n</body>\n</html>\n\');' . "\n"
        . 'Error_console.document.close();' . "\n"
        . '// ]]>' . "\n"
        . '</script>';
    }
}

/**
 * Create debug directory, protect it with index.html and .htaccess.
 * @param string $type : name of debug directory to create ( error or sql )
 */
function createDebugDirectory($type) {
    if ($type == 'error' || $type == 'sql') {
        // Create debug directory if don't exist
        if (!is_dir('./debug/' . $type)) {
            @mkdir('./debug/' . $type, 0777, true);
        }

        // Create index.html to protect root debug directory if don't exist
        if (!file_exists('./debug/index.html')) {
            file_put_contents('./debug/index.html', ' ');
        }

        // Create .htaccess to protect debug directory ( error or sql ) if don't exist
        if (!file_exists('./debug/' . $type . '/.htaccess')) {
            $htaccess = 'Options -Indexes' . "\n"
                    . '<FilesMatch "\.(txt)$">' . "\n"
                    . "\t" . 'Order allow,deny' . "\n"
                    . "\t" . 'Deny from all' . "\n"
                    . "\t" . 'Satisfy All' . "\n"
                    . '</FilesMatch>' . "\n\n"
                    . 'ErrorDocument 403 "Sorry you haven\'t permission to open this page"';

            file_put_contents('./debug/' . $type . '/.htaccess', $htaccess);
        }
    }
}

/**
 * Display string with 'pre' format
 * @param mixed $value : object to display
 */
function pre_print_r($value) {
    echo'<pre>';
    print_r($value);
    echo '</pre>';
}

?>