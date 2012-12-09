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
 * @param string $savePath
 * @param string $sessionName
 * @return boolean : true session opened 
 */
function session_open($savePath, $sessionName){
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
 * @param string $sessionId : session id
 * @return boolean : true if session read, else false 
 */
function session_read($sessionId){
    nkTryConnect();
    $result = '';
    $sessionVar = nkDB_select('SELECT session_vars FROM ' . TMPSES_TABLE . ' WHERE session_id = "' . $sessionId . '"');
    if (nkDB_numRows() > 0 && !empty($sessionVar)) {
        $result = $sessionVar[0]['session_vars'];
    }
    return $result;
}

/**
 * Write session.
 * @param string $sessionId : session id
 * @param string $data
 * @return boolean : true if session wrote, else false 
 */
function session_write($sessionId, $data){
    $sessionId = mysql_escape_string($sessionId);
    $data = mysql_escape_string($data);
    
    nkTryConnect();
    
    $fields = array( 'session_id', 'session_start', 'session_vars' );
    $values = array( $sessionId, time(), $data );
    $rs = nkDB_insert( TMPSES_TABLE, $fields, $values );
    
    if ($rs === false || nkDB_affected_rows() == 0) {
        $fields = array('session_vars');
        $values = array($data);
        $rs = nkDB_update( TMPSES_TABLE, $fields, $values, 'session_id = "' . $sessionId . '"');
    }
    return $rs;
}

/**
 * Delete session.
 * @param string $id : session id
 * @return mixed : resource if session deleted, else false 
 */
function session_delete($sessionId){
    nkTryConnect();
    
    $rs = nkDB_delete( TMPSES_TABLE, 'session_id = "' . mysql_escape_string($sessionId). '"' );

    return $rs;
}

/**
 * Kill dead session.
 * @param string $maxlife: maxlife time
 * @return boolean : session deleted 
 */
function session_gc($maxlife){
    $time = time() - $maxlife;

    nkTryConnect();
    
    $rs = nkDB_delete( TMPSES_TABLE, 'session_start < ' . $time);

    return true;
}


?>
