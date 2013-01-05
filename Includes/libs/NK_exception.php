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
 * Class for exceptions.
 * @name NK_Exception
 * @desc Custom class for errors and exceptions management.
 */
class NK_Exception extends Exception {
    
    /**
     * @var instance
     * @access private
     * @static
     */
    private static $_instance = null;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->activateDisplayErrors();
    }
    
     /**
      * Single instance of class.
      * @param void
      * @return Singleton
      */
    public static function getInstance() {
        if (is_null(self::$_instance)) {
           self::$_instance = new NK_Exception();
        }
        return self::$_instance;
    }

    /**
     * Activation of all errors (use for developement)
     */
    public function activateDisplayErrors() {
        // Reporting fixing
        error_reporting(E_ALL | E_STRICT);

        // Errors message display
        if (ini_get('display_errors') !== true) {
            ini_set('display_errors', true);
        }
    }

    /**
     * Custom displaying error.
     * @return string custom message
     */
    public function showError() {
        // Affichage personnalisé du message d'erreur
        
        $errorMsg = printStyle('Error on line ' . $this->getLine() . '<br/> '.
                            ' in '.$this->getFile() . '<br/> '.
                            ' Message : ' . $this->getMessage());

        return $errorMsg;
        
        
        /*
        // $trace contient le contexte de l'exception
        $trace = $this->getTrace();
        // print_r($trace);
        if (!empty($trace['1']['function'] )) {
            echo 'on function '.$trace['1']['function'];
        }
        
        // $this->context contient le contexte de l'erreur
        echo '<br /><br/>Error context :<br/><pre>';
        // print_r($this->context);
        echo '</pre>';
        
        return false;
        //var_dump($GLOBALS['nk_error']);
         */
    }

    /**
     * @todo
     * Log SQL error for mysql_query.
     */
    public function nkErrorSQL() {
        
    }

}

/**
* Print string with style
* @param string : $str string to custom
*/
function printStyle($str) {
    echo '<pre style="background:white;">' . $str . '</pre>';
}

/**
 * Callback function for throw exception.
 * @param callable $exception 
 */
function nkExceptionHandler($exception) {
    echo $exception;
}

/**
 * Callback function for throw exception (errors).
 * @param type $code
 * @param type $msg
 * @param type $file
 * @param type $line
 * @param type $context 
 */
function nkErrorHandler2($code, $msg, $file, $line, $context) {
    throw new NK_Exception($code, $msg, $file, $line, $context);
}



/**
 * Error management.
 * Sets a user function (error_handler) to handle errors in a script.
 * Save error in a global var.
 * @param int $no : Contains the level of the error raised
 * @param string $str : Contains the error message
 * @param string $file : Contains the filename that the error was raised in
 * @param string $line : Contains the line number the error was raised at
 */
function nkErrorHandler($no, $str, $file, $line, $context) {
    if (!isset($GLOBALS['nk_error'])) {
        $GLOBALS['nk_error'] = array();
    }

    switch ($no) {
        // Fatal error
        case E_USER_ERROR:
            $GLOBALS['nk_error'][] = array(
                'type' => 'Fatal error',
                'msg' => $str
            );
            var_dump($GLOBALS['nk_error']);
            exit;
            break;

        // Warning
        case E_USER_WARNING:
            $GLOBALS['nk_error'][] = array(
                'type' => 'Warning',
                'msg' => $str
            );
            break;

        // Notice
        case E_USER_NOTICE:
            $GLOBALS['nk_error'][] = array(
                'type' => 'Notice',
                'msg' => $str
            );
            break;

        // Generate error by PHP
        default:
            $GLOBALS['nk_error'][] = array(
                'type' => 'Unknown error',
                'msg' => addslashes($str) . '<hr />In file : <strong>' . addslashes($file) . '</strong>, at line <strong>' . $line . '</strong>.'
            );
            break;
    }
    return false;
    //var_dump($GLOBALS['nk_error']);
    //throw new NK_Exception($code, $msg, $file, $line, $context);
}

// Define manager exceptions.
set_exception_handler('nkExceptionHandler');

// Define manager errors.
set_error_handler('nkErrorHandler');

// Test exception
/*
$nkExc = NK_Exception::getInstance();
 */


?>
