<?php
/**
 * @name NkException
 * @desc Custom class for errors and exceptions management.
 */
class NkException extends Exception {
    
    /**
     * Constructor.
     */
    public function __construct() {
        $this->activateDisplayErrors();
    }
    
    /**
     * Activation of all errors (use for developpement)
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
        $message  = '<pre>Error : "<strong>';
        $message .= $this->message;
        $message .= '"</strong>, line ';
        $message .= $this->line;
        $message .= ' - Severity : ';
        $message .= $this->severity;
        $message .= ', in the file <strong>';
        $message .= $this->file;
        $message .= '</strong></pre>';
        return  $message;
    }
    
    /**
     * @todo
     * Log SQL error for mysql_query.
     */
    public function nkErrorSQL() {
        
    }

}

/**
 * Exceptions management.
 * @param callable $exception 
 */
function nkExceptionHandler($exception) {
    echo $exception;
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
function nkErrorHandler($no, $str, $file, $line)
{
    if ( !isset( $GLOBALS['nk_error'] ) ) {
        $GLOBALS['nk_error'] = array();
    }

    switch ( $no )
    {
            // Fatal error
            case E_USER_ERROR:
                $GLOBALS['nk_error'][] = array(
                    'type' => 'Fatal error',
                    'msg' => $str
                );
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
                    'msg' =>  addslashes( $str ) .'<hr />In file : <b>'. addslashes( $file ) .'</b>, at line <b>'. $line .'</b>.'
                );
            break;
    }

    return false;
}

// Define manager exceptions.
set_exception_handler('nkExceptionHandler');

// Define manager errors.
set_error_handler('nkErrorHandler');

$nkException = new NkException();

?>
