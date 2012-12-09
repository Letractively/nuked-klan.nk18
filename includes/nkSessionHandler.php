<?php

// -------------------------------------------------------------------------//
// Nuked-KlaN - PHP Portal                                                  //
// http://www.nuked-klan.org                                                //
// -------------------------------------------------------------------------//
// This program is free software. you can redistribute it and/or modify     //
// it under the terms of the GNU General Public License as published by     //
// the Free Software Foundation; either version 2 of the License.           //
// -------------------------------------------------------------------------//

/**
 * Open session.
 * @param type $path
 * @param type $name
 * @return boolean : true session opened 
 */
function session_open($path, $name){
    return true;
}

/**
 * Close session.
 * @return boolean : true session closed
 */
function session_close(){
    return true;
}

/**
 * Read session.
 * @todo adapt SQL
 * @param type $id : session id
 * @return boolean : true if session read, else false 
 */
function session_read($id){
    nkTryConnect();

    $sql = mysql_query('SELECT session_vars FROM ' . TMPSES_TABLE . ' WHERE session_id = "' . $id . '"');
    if (mysql_num_rows($sql) > 0) {
        return ($sql === false) ? '' : mysql_result($sql, 0);
    }
}

/**
 * Write session.
 * @todo adapt SQL
 * @param string $id : session id
 * @param string $data
 * @return boolean : true if session wrote, else false 
 */
function session_write($id, $data){
    $id = mysql_escape_string($id);
    $data = mysql_escape_string($data);

    nkTryConnect();

    $sql = mysql_query('INSERT INTO ' . TMPSES_TABLE . ' (session_id, session_start, session_vars) VALUES ("' . $id . '", ' . time() . ', \'' . $data . '\')');

    if ($sql === false || mysql_affected_rows() == 0) {
        $sql = mysql_query('UPDATE ' . TMPSES_TABLE . ' SET session_vars = \'' . $data . '\' WHERE session_id = "' . $id . '"');
    }

    return $sql !== false;
}

/**
 * Delete session.
 * @todo adapt SQL
 * @param string $id : session id
 * @return mixed : resource if session deleted, else false 
 */
function session_delete($id){
    nkTryConnect();

    $sql = mysql_query('DELETE FROM ' . TMPSES_TABLE . ' WHERE session_id = "' . mysql_escape_string($id) . '"');

    return $sql;
}

/**
 * Kill dead session.
 * @param string $maxlife: maxlife time
 * @return boolean : session deleted 
 */
function session_gc($maxlife){
    $time = time() - $maxlife;

    nkTryConnect();

    mysql_query('DELETE FROM ' . TMPSES_TABLE . ' WHERE session_start < ' . $time);

    return true;
}


?>
