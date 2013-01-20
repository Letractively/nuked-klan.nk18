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
 * Light template library.
 * @name NK_tpl
 * @desc Custom class for include template
 */
class NK_tpl {
    
    
    /**
     * @var instance
     * @access private
     * @static
     */
    private static $_instance = null;
    
    /**
     * Constructor.
     */
    private function __construct() {
    }
    
     /**
      * Single instance of class.
      * @param void
      * @return Singleton
      */
    public static function getInstance() {
        if (is_null(self::$_instance)) {
           self::$_instance = new NK_tpl();
        }
        return self::$_instance;
    }
    
    /**
     * Generator content with tags (open / close style).
     * @param string $tag : tag to generate ('div', 'span', ....)
     * @param string $content content to display inside div
     * @param mixed $classes list of classes to display, false if not class
     * @param mixed $id : id to display, false if not id
     * @example
     *                      nkContentTag('div', 'my text', 'nkError nkCenter', 'idTest')
     *             ==> <div class="nkError nkCenter">my text</div>
     */
    public function nkContentTag($tag, $content, $classes = false, $id = false) {
        // Tag which will be generated
        $tagDisplay = '';
        // Attributes which will be generated
        $attrStr = '';
        if ($classes != false) {
            $attrStr .= ' class="'. $classes .'"';
        }
        if ($id != false) {
            $attrStr .= ' id="'. $id .'"';
        }

        $tagDisplay = '<' . $tag . $attrStr . '>' . $content . '</' . $tag . '>';
        return $tagDisplay;
    }
    
    /**
     * Display error on a tag.
     * @param string $content : text to display 
     * @param string $classes : class used for text
     * @param boolean $center if true, center text, else not
     * @param mixed $id : id to display, false if not id
     * @ return string to display
     */
    public function nkDisplayError($content, $classes = 'nkError', $center = true, $id = false) {
        if ($center) {
            $classes .= ' nkCenter';  
        }
        return $this->nkContentTag('div', $content, $classes, $id);
    }

    /**
     * Display success on a tag.
     * @param string $content : text to display 
     * @param string $classes : class used for text
     * @param boolean $center if true, center text, else not
     * @param mixed $id : id to display, false if not id
     * @ return string to display
     */
    public function nkDisplaySuccess($content, $classes = 'nkSuccess', $center = true, $id = false) {
        if ($center) {
            $classes .= ' nkCenter';  
        }
        return $this->nkContentTag('div', $content, $classes, $id);
    }


    /**
     * Exit after a display error on a tag.
     * @param string $content : text to display 
     * @param string $url : url for back button
     * @ return string to display, back button and exit function
     */
    public function nkExitAfterError($content, $url = null){
        $return = $this->nkDisplayError($content, 'nkError', true);
        $return .= $this->nkHistoryBack($url);
        $return .= adminfoot();
        exit($return);
    }

    /**
     * level display if access denied.
     * @param string $url : url for back button
     * @ return informed that the member does not have the required level
     */
    public function nkBadLevel($url) {
        $return = $this->nkDisplayError(NOENTRANCE, 'nkError', true);
        $return .= $this->nkHistoryBack($url);
        return($return);
    }


    /**
     * Display if disabled module.
     * @param string $url : url for back button
     * @ return informs that the module is disabled
     */
    public function nkModuleOff($url) {
        $return = $this->nkDisplayError(MODULEOFF, 'nkError', true);
        $return .= $this->nkHistoryBack($url);
        return($return);
    }


    /**
     * Return to previous page.
     * @param string $url : url for back button
     * @ return back button
     */
    public function nkHistoryBack($url=null){ 
        $referer = !isset($url) ? $_SERVER['HTTP_REFERER'] : $url;
        return('<a href="'.$referer.'">'.BACK.'</a>');    
    }


    /**
     * Return identification required.
     * @param string $pipe : display a selector
     * @ returns a link to the identification or recording
     */
    public function nkNoLogged($pipe = null) {
        global $user;
        $visiteur = $user ? $user[1] : 0;

        if ($visiteur == 0) {             
            return($this->nkDisplayError('<h1>'.USERENTRANCE.'</h1><a href="index.php?file=User&amp;op=login_screen">'.TOLOG.'</a>&nbsp;'.$pipe.'&nbsp;<a href="index.php?file=User&amp;op=reg_screen">'.REGISTERUSER, 'error_nk', true));
        }
    }


    /**
     * Fonction pour afficher le menu                               
     * @param $arrayMenu    -> Liens du menu                        
     * @param $module       -> Nom du module à interroger           
     * @param $separator1   -> séparateur ouvrant ex: [  
     * @param $separator2   -> séparateur fermant ex: ]   
     * @param $pipe         -> séparateur entre lien ex: |  
     * @ return a menu for module administration
    **/
    public function nkMenu($module, $arrayMenu, $separator1 = null, $separator2 = null, $pipe = null) {

        echo'<nav id="'.$module.'_menuNav" class="globalMainNav">
                <ul>'.$separator1.'&nbsp;';

                $i = 0;
                foreach($arrayMenu as $key => $arrayLink) {
                    if($i>0) echo '&nbsp;'.$pipe.'&nbsp;';
                    echo '<li>';
                    if($arrayLink[2] == 'selected') {
                        echo $arrayLink[0];
                    }
                    else{
                        echo '<a href="'.$arrayLink[1].'" >'.$arrayLink[0].'</a>';
                    }            
                    echo '</li>';
                    $i++;
                }
            
        echo'   &nbsp;'.$separator2.'
                </ul>
            </nav>';
    }

    
}

// Test generator
/*
$nkTpl = NK_tpl::getInstance();
echo $nkTpl->nkContentTag('div', 'my text', 'nkError nkCenter', 'idTest');
echo $nkTpl->nkDisplayError('my text');
 */
 

?>
