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
 * Functions NK use for connection to MySQL database.
 */

/**
 * A global Array with info about database layer and querys
 */

$GLOBALS['nkDB'] = array(
    'database' => 'MySql',
    'querys' => array(),
    'error' => false,
    'selects' => array(),
    'latest_ressource'	=> '',
    'connection' => false,
    'status_connection' => array(),
    'status' => array()
);


// -------------------------------------------------------------------------//
//                             Main function                                //
// -------------------------------------------------------------------------//

/**
 * Connection to database
 * @return bool : Status of connect to MySql database
 */
function nkDB_connect()
{
    global $global;

    $db = false;

    // If $global array don't exist, include main config file
    if ( !is_array( $global ) ) {
        require dirname( __FILE__ ) .'/../../conf.inc.php';
    }

    // Open a persistency or normal connection to a MySQL Server
    if (  array_key_exists( 'db_persistency', $global ) && $global['db_persistency'] == true ) {
        $GLOBALS['nkDB']['connection'] = @mysql_pconnect( $global['db_host'], $global['db_user'], $global['db_pass'] );
    } else {
        $GLOBALS['nkDB']['connection'] = @mysql_connect( $global['db_host'], $global['db_user'], $global['db_pass'] );
    }

    // If the connection is etablished...
    if ( $GLOBALS['nkDB']['connection'] == true ) {
        // Add status
        $GLOBALS['nkDB']['status_connection'][] = array( 'mysql_connect', 'ok' );

        // Select the MySQL database
        $db = @mysql_select_db( $global['db_name'], $GLOBALS['nkDB']['connection'] );

        // If failed, add status and start nkDb error
        if ( $db == false ) {
            $GLOBALS['nkDB']['status_connection'][] = array( 'mysql_select_db', htmlspecialchars( addslashes( mysql_error() ) ) );
            $GLOBALS['nkDB']['error'] = true;
        }
        else {
            $GLOBALS['nkDB']['status_connection'][] = array( 'mysql_select_db', 'ok' );
            // Force encoding latin1
            mysql_query('SET NAMES "latin1"');
        }
    } else { // Otherwise add status and start nkDb error
        $GLOBALS['nkDB']['status_connection'][] = array( 'mysql_connect', htmlspecialchars( addslashes( mysql_error() ) ) );
        $GLOBALS['nkDB']['error'] = true;
    }

    return $db;
}




/**
 * Disconnect to database.
 */
function nkDB_disconnect()
{
    if ( $GLOBALS['nkDB']['connection'] ) {
        @mysql_close( $GLOBALS['nkDB']['connection'] );
    }
}


/**
 * Show version of MySql server
 * @return string : Version of Mysql server
 */
function nkDB_show_version()
{
    if ( !$GLOBALS['nkDB']['connection'] ) {
        nkDB_connect();
    }

    return mysql_get_server_info();
}


/**
 * A simple layer to handle select querys
 * @param string $query : The SQL part, should be database independant
 * @param mixed $order : Array of sorting field
 * @param string $dir : Sorting direction
 * @param int $limit : Maximum result to fetch
 * @param int offset : Offset to start at (in case of use of the limit parameter)
 * @return array : Numeric indexed array of rows
 */
function nkDB_select( $query, $order = false, $dir = 'ASC', $limit = false, $offset = 0 )
{
    $result = array();

    // Build the query
    $sql = $query;

    $GLOBALS['nkDB']['selects'][] = $query;

    // Sort field order by mutiple directions
    if ( is_array( $order ) ) {
        $nb_order = count( $order );

        $sql .= ' ORDER BY ';

        if ( is_array( $dir ) ) {
            for ( $i = 0; $i < $nb_order; $i++ ) {
                
                if ( !array_key_exists( $i, $dir ) ) {
                    $dir[$i] = 'ASC';
                }

                if ( $i > 0 ) {
                    $sql .= ', ';
                }

                //$sql .= '`'. $order[$i] .'` '. $dir[$i];
                $sql .= ''. $order[$i] .' '. $dir[$i];
            }
        }
        // Sort field order by same direction
        else {
            for ( $i = 0; $i < $nb_order; $i++ ) {
                if ( $i > 0 ) {
                    $sql .= ', ';
                }

                //$sql .= '`'. $order[$i] .'` '. $dir;
                $sql .= ''. $order[$i] .' '. $dir;
            }
        }
    }
    
    if ( $limit !== false ) {
        $sql .= ' LIMIT '. $offset .', '. $limit;

        if ( !is_numeric( $offset ) || !is_numeric( $limit ) ) {
            $GLOBALS['nkDB']['status'][count( $GLOBALS['nkDB']['querys'] ) - 1] = array(
                    htmlspecialchars( addslashes( $sql ) ),
                    htmlspecialchars( addslashes( 'Offset and limit must be a numeric vars!' ) )
            );

            return $result;
        }
    }

    // Execute the query
    $ressource = nkDB_execute( $sql );

    if ( $ressource ) {
        // Build the numeric indexed array of rows of query data
        while ( $data = mysql_fetch_assoc( $ressource ) ) {
            $result[] = $data;
        }
    }

    return $result;
}


/**
 * Get the row_count for a query.
 * By default, the latest select query is used
 * @param mixed $ressource : The Mysql Ressource pointer returned by a query. If false, the latest ressource is used
 *		So you don't need to specify this parameter if used immediatly after the select query
 * @return int : Number of rows returned by the query
 */
function nkDB_numRows( $ressource = false )
{
    if ( !$ressource ) {
        $ressource = $GLOBALS['nkDB']['latest_ressource'];
    }

    return mysql_num_rows( $ressource );
}


function nkDB_totalNumRows( $query = false )
{
    if ( !$query ) {
        $query = $GLOBALS['nkDB']['selects'][count( $GLOBALS['nkDB']['selects'] ) - 1];
    }

    $fromOffset = strpos( $query, ' FROM' );

    $sql = 'SELECT COUNT(*) AS `recordcount` '. substr( $query, $fromOffset, ( strlen( $query ) - $fromOffset ) );

    $query_data = nkDB_select( $sql );

    return $query_data[0]['recordcount'];
}


/**
 * A simple layer to handle insert querys
 * @param string $table : Table name
 * @param array $fields : List of fields to insert
 * @param array $values : List of values to insert, in the same order as $fields
 * @return bool : The result of insert query
 */
function nkDB_insert( $table, $fields, $values )
{
    // Prepares data to insert
    foreach ( $values as $i => $value ) {
        if ( is_array( $values[$i] ) && count( $values[$i] ) > 1 ) {
            if ( $values[$i][1] == 'no-escape' ) {
                //$values[$i] = nkDB_escape( $values[$i][0], true ); <-- pas echaper par mysql_real_escape_string ??
                $values[$i] = $values[$i][0];
            }
        } else {
            $values[$i] = nkDB_escape( $values[$i] );
        }
    }

    // Build the query
    $sql = 'INSERT INTO `'. $table .'` ('. implode( ', ', $fields ) .') VALUES ('. implode( ', ', $values ) .')';

    return nkDB_execute( $sql );
}


/**
 * Get last inserted id
 * @return mixed : The value of auto-increment field if the query was successful, else returns false
 */
function nkDB_insert_id()
{
    if ( $GLOBALS['nkDB']['latest_ressource'] ) {
        return mysql_insert_id( $GLOBALS['nkDB']['connection'] );
    }
    else {
        return false;
    }
}


/**
 * A simple layer to handle update queries
 * @param string $table : Table name
 * @param array $fields : List of fields to insert
 * @param array $values : List of values to insert, in the same order as $fields
 *                                  Values are automaticly escaped
 *                                  You may disable escaping by placing value in a sub-array
 *                                  ex. : 
 *                                      $field = array( 'field_foo', 'field_bar' );
 *                                      $values = array( 'foo', 'bar' ) // values will be escaped
 *                                      $values = array( 'foo', array('field_bar + 1', 'no-escape') ) // Second value won't be escaped
 *
 * @param string $where : SQL part to identify the row to update (ie. "id = 56")
 * @return bool : The result of insert query
 */
function nkDB_update( $table, $fields, $values, $where )
{
    $separator = '';

    $fieldsLength = count( $fields ) - 1;

    // Build the query
    $sql = 'UPDATE `'. $table .'` SET ';

    for ( $i = 0; $i <= $fieldsLength; $i++ ) {
        $sql .= $separator . $fields[$i] .' = ';

        if ( is_array( $values[$i] ) && count( $values[$i] ) > 1 ) {
            if ( $values[$i][1] == 'no-escape' ) {
                    $sql .= $values[$i][0];
                    //$sql .= nkDB_escape( $values[$i][0], true ); <-- pas echaper par mysql_real_escape_string ??
            }
        } else {
                $sql .= nkDB_escape( $values[$i] );
        }

        $separator = ', ';
    }

    $sql .= ' WHERE '. $where;

    return nkDB_execute( $sql );
}


/**
 * Get the number of affected rows by the last INSERT, UPDATE, REPLACE or DELETE query
 * @return int : The number of affected rows if the query was successful, returns -1 if the last query failed. 
 */
function nkDB_affected_rows()
{
    return mysql_affected_rows();
}


/**
 * A simple layer to handle delete querys
 * @param string $table : Table name
 * @param string $where : SQL part to identify the row to delete (ie. "id = 56")
 * if this parameter isn't defined, this requete delete all row in the table
 * @return mixed : The result of nkDB_execute call
 */
function nkDB_delete( $table, $where = 'all' )
{
    $where = ( $where != 'all' ) ? ' WHERE '. $where : '';

    $sql = 'DELETE FROM `'. $table .'`'. $where;

    return nkDB_execute( $sql );
}


/**
 * Exec queries...
 */
function nkDB_execute( $sql )
{
    // Save query for debug propose
    $GLOBALS['nkDB']['querys'][] = $sql;

    if ( !$GLOBALS['nkDB']['connection'] ) {
        nkDB_connect();
    }

    // For debug time to exec query, move the comment //
    //$sql_start = microtime();
    $ressource = mysql_query( $sql );
    //$sql_time = microtime() - $sql_start;

    if ( $ressource == true ) {
        $GLOBALS['nkDB']['status'][count( $GLOBALS['nkDB']['querys'] ) - 1] = array( 
            htmlspecialchars( addslashes( $sql ) ),
            'ok'
        );
        /*
        $GLOBALS['nkDB']['status'][count( $GLOBALS['nkDB']['querys'] ) - 1] = array( 
                htmlspecialchars( addslashes( $sql ) ),
                'ok',
                $sql_time
        );
        */
    } else {
        $GLOBALS['nkDB']['status'][count( $GLOBALS['nkDB']['querys'] ) - 1] = array(
                htmlspecialchars( addslashes( $sql ) ),
                htmlspecialchars( addslashes( mysql_error() ) )
        );
        $GLOBALS['nkDB']['error'] = true;
    }

    // Save result ressource in order to perform a row count via mysql_num_rows
    $GLOBALS['nkDB']['latest_ressource'] = $ressource;

    return $ressource;
}


/**
 * Escape a string for insertion into a text field
 * @param string $value : String to ptotect
 * @param bool $no_quote : If value is enclosed into double quote
 */
function nkDB_escape( $value, $no_quote = false )
{
    if ( is_array( $value ) ) {
        foreach( $value as $key => $val ) {
            $value[$key] = nkDB_escape( $val );
        }
    } else {
        if ( get_magic_quotes_gpc() ) {
            $value = stripslashes( $value );
        }

        $value = mysql_real_escape_string( $value, $GLOBALS['nkDB']['connection'] );
        $value = str_replace( '`', '\`', $value );
    }

    if ( $no_quote )  {
        return $value;
    } else {
        return '"'. $value .'"';
    }
}


/**
 * Formating a date with MySQL
 * @param string $field : Field name ( datetime type)
 * @param string $dateFormat_Mask : Mask of date / hour
 * @return string : The MySql code to using for formated the date
 */
function nkDB_date_format( $field, $dateFormat_Mask )
{
    /*
     * http://dev.mysql.com/doc/refman/5.0/fr/date-and-time-functions.html
     * %d	=> jour
     * %m	=> mois
     * %Y	=> année
     *
     * %H	=> heure
     * %i	=> minute
     */

    return 'DATE_FORMAT ('. $field .', "'. $dateFormat_Mask .'") AS `'. $field .'`';
}


// -------------------------------------------------------------------------//
//                         Install & Update function                        //
// -------------------------------------------------------------------------//

/**
 * List all MySql database ( Only with Mysql )
 * @return array : Numeric indexed array of rows
 */
function nkDB_list_db()
{
    if ( !defined( 'nkInstallMod' ) ) {
        return;
    }

    if ( !$GLOBALS['nkDB']['connection'] ) {
        nkDB_connect();
    }

    // Get list of database name
    $ressource = mysql_list_dbs( $GLOBALS['nkDB']['connection'] );

    if ( $ressource ) {
        // Build the numeric indexed array of rows of query data
        while ( $data = mysql_fetch_assoc( $ressource ) ) {
            $result[] = $data;
        }

        // Save result ressource in order to perform a row count via mysql_num_rows
        $GLOBALS['nkDB']['latest_ressource'] = $ressource;

        return $result;
    } else {
        return $ressource;
    }
}


/**
 * Create a table
 * @param string $table : The name of the table
 * @param array $data :  List of fields to create
 * @param bool $drop_table : Drop the table if exists
 */
function nkDB_create_table( $table, $data, $drop_table = false )
{
    if ( !defined( 'nkInstallMod' ) ) {
        return;
    }

    if ( !$GLOBALS['nkDB']['connection'] ) {
        nkDB_connect();
    }

    if ( $drop_table == true ) {
        nkDB_execute( 'DROP TABLE IF EXISTS `'. $table .'`' );
    }

    $sql = 'CREATE TABLE `'. $table .'` (' ."\n";
    $separator = '';

    foreach ( $data['fields'] as $field => $options ) {
        $sql .= $separator .'`'. $field .'`';

        if ( array_key_exists( 'type', $options ) ) {
            switch ( $options['type'] ) {
                case 'INT' :
                    $sql .= ' int('. $options['value'] .')';
                break;

                case 'MEDIUMINT' :
                    $sql .= ' mediumint('. $options['value'] .')';
                break;

                case 'VARCHAR' :
                    $sql .= ' varchar('. $options['value'] .')';
                break;

                case 'CHAR' :
                    $sql .= ' char('. $options['value'] .')';
                break;

                case 'TEXT' :
                    $sql .= ' text';
                break;

                case 'MEDIUMTEXT' :
                    $sql .= ' mediumtext';
                break;

                case 'LONGTEXT' :
                    $sql .= ' longtext';
                break;

                case 'BLOB' :
                    $sql .= ' blob';
                break;
            }
        }

        // a revoir unsigned
        if ( array_key_exists( 'unsigned', $options ) && $options['unsigned'] == true ) {
            $sql .= ' '. $options['type'];
        }

        if ( array_key_exists( 'null', $options ) ) {
            if ( $options['null'] == true )  {
                $sql .= ' NULL';
            } else {
                $sql .= ' NOT NULL';
            }
        }

        if ( $options['auto-increment'] == true )  {
            $sql .= ' auto_increment';
        }

        if ( $options['default'] != '' ) {
            $sql .= ' '. $options['default'];
        }

        $separator = ',' ."\n";
    }

    if ( is_array( $data['primary-key'] ) ) {
        $sql .= ',' ."\n". 'PRIMARY KEY ('. 
        $separator = '';

        foreach ( $data['primary-key'] as $n => $primary_key )  {
            $sql .= $separator .'`'. $primary_key .'`';
            $separator = ', ';
        }

        $sql .= ')';
    }

    if ( is_array( $data['key'] )  ) {
        foreach ( $data['key'] as $n => $key ) {
            $sql .= ',' . "\n" . 'KEY `'. $key .'` (`'. $key .'`)';
        }
    }

    // TYPE ou ENGINE ?
    $sql .= "\n" . ') TYPE='. $data['MySql-options']['type'];

    nkDB_execute( $sql );
}


/**
 * Alter a table
 * @param string $table : The name of the table
 * @param array $action :  Action to alter table
 * @param array $data :  List of fields to alter
 */
function nkDB_alter_table( $table, $action, $data )
{
    if ( !$GLOBALS['nkDB']['connection'] ) {
        nkDB_connect();
    }

    $action = strtoupper( $action );

    $sql = 'ALTER TABLE `'. $table .'` '. $action .' ';

    if ( $action == 'ADD' ) {
        $separator = '';

        foreach ( $data as $field => $options ) {
            $sql .= $separator .'`'. $field .'`';

            if ( array_key_exists( 'type', $options ) ) {
                switch ( $options['type'] )
                {
                    case 'INT' :
                        $sql .= ' int('. $options['value'] .')';
                    break;

                    case 'MEDIUMINT' :
                        $sql .= ' mediumint('. $options['value'] .')';
                    break;

                    case 'VARCHAR' :
                        $sql .= ' varchar('. $options['value'] .')';
                    break;

                    case 'CHAR' :
                        $sql .= ' char('. $options['value'] .')';
                    break;

                    case 'TEXT' :
                        $sql .= ' text';
                    break;

                    case 'MEDIUMTEXT' :
                        $sql .= ' mediumtext';
                    break;

                    case 'LONGTEXT' :
                        $sql .= ' longtext';
                    break;

                    case 'BLOB' :
                        $sql .= ' blob';
                    break;
                }
            }

            // a revoir unsigned
            if ( array_key_exists( 'unsigned', $options ) && $options['unsigned'] == true ) {
                $sql .= ' '. $options['type'];
            }

            if ( array_key_exists( 'null', $options ) ) {
                if ( $options['null'] == true ) {
                    $sql .= ' NULL';
                } else {
                    $sql .= ' NOT NULL';
                }
            }

            if ( $options['auto-increment'] == true ) {
                $sql .= ' auto_increment';
            }

            if ( $options['default'] != '' ) {
                $sql .= ' '. $options['default'];
            }

            $separator = ',' ."\n";
        }
    }

    nkDB_execute( $sql );
}


// -------------------------------------------------------------------------//
//                         MySql admin function                             //
// -------------------------------------------------------------------------//

/**
 * Show the status of all table.
 * @param string $dbname : The name of the database
 * @return array : Numeric indexed array of rows
 */
function nkDB_show_table_status( $dbname )
{
    $result = array();

    // Execute the query
    $ressource = nkDB_execute( 'SHOW TABLE STATUS FROM `'. $dbname .'`' );

    if ( $ressource ) {
        // Build the numeric indexed array of rows of query data
        while ( $data = mysql_fetch_assoc( $ressource ) ) {
            $result[] = $data;
        }
    }

    return $result;
}


/**
 * Optimize the selected table.
 * @param string $name : The name of the table
 * @return mixed : Array with Table, Op, Msg_type and Msg_text fields result of optimize table, or false if failed
 */
function nkDB_optimize_table( $name )
{
    return nkDB_execute( 'OPTIMIZE TABLE `'. $name .'`' );
}

?>