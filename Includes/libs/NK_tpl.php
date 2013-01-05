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
     *                      nkContentTag('div', 'my text', 'error-nk center-nk', 'idTest')
     *             ==> <div class="error-nk center-nk">my text</div>
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
    public function nkDisplayError($content, $classes = 'error-nk', $center = true, $id = false) {
        if ($center) {
            $classes .= ' center-nk';  
        }
        return $this->nkContentTag('div', $content, $classes, $id);
    }
    
}

// Test generator
/*
$nkTpl = NK_tpl::getInstance();
echo $nkTpl->nkContentTag('div', 'my text', 'error-nk center-nk', 'idTest');
echo $nkTpl->nkDisplayError('my text');
 */
 

?>
