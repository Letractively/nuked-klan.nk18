<?php
// -------------------------------------------------------------------------//
// Nuked-KlaN - PHP Portal                                                  //
// http://www.nuked-klan.org                                                //
// -------------------------------------------------------------------------//
// This program is free software. you can redistribute it and/or modify     //
// it under the terms of the GNU General Public License as published by     //
// the Free Software Foundation; either version 2 of the License.           //
// -------------------------------------------------------------------------//
defined('INDEX_CHECK') or die ('You can\'t run this file alone.');

    /**
    * Call function header admin                                                
    * @param $module -> determines the modulus                                            
    * @param $arrayMenu -> Retrieves array() created in the admin module    
    * @param $title -> Specifies the title of the administration modules ex: ADMINDOWNLOAD        
    **/
    function adminHeader($arrayMenu, $title, $module, $selectOptions = null){
        global $language, $nkTpl;
    ?>
            <!-- Start Content Box -->
        <section class="content-box">
            <Header class="content-box-header">
                <div>
                    <h3><?php echo $title; ?></h3>
                </div>
                <div id="help">
                    <a href="help/<?php echo $language; ?>/<?php echo $module; ?>.php" rel="modal"><img style="border: 0;" src="help/help.gif" alt="" title="<?php echo HELP; ?>" /></a>
                </div>
                    <?php
                    /* inclusion du menu */
                    if(isset($selectOptions)){
                        $arrayMenu[$selectOptions][2] = 'selected';
                    }
                    $nkTpl->nkMenu($module, $arrayMenu, '[', ']', '|');
                    ?>
            </header>
    <?php
    }


    /**
    * Validation function links             
    * @param $url -> link / file  to the page  
    **/
    function verifyUrl($url){
        global $nuked;
        
        if (version_compare(PHP_VERSION, '5.2.0', '>')){
            if(filter_var($url, FILTER_VALIDATE_URL)){
                $urlVerify = $url;
            }else{
                $urlVerify = $nuked['url'].'/'. $url;
            }
        }else{
            $regex = "#((http|https|ftp)://(\S*?\.\S*?))(\s|\;|\)|\]|\[|\{|\}|,|\"|'|:|\<|$|\.\s)#ie";   
            if(preg_match($regex, $url)){
                $urlVerify = $url;
            }else{
                $urlVerify = $nuked['url'].'/'. $url;
            }
        }
        return $urlVerify;
    }

    /**
    * Cut a chain keeping HTML formatting
    * @param string $text       -> Text to be cut
    * @param integer $length    -> Length to keep
    * @param string $ending     -> Characters to add at the end
    * @param boolean $exact     -> exact cut
    * @return string
    **/
    function cutText($text, $length, $ending = '...', $exact = false) {
        if(strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
            return $text;
        }
        preg_match_all('/(<.+?>)?([^<>]*)/is', $text, $matches, PREG_SET_ORDER);
        $total_length = 0;
        $arr_elements = array();
        $truncate = '';
        foreach($matches as $element) {
            if(!empty($element[1])) {
                if(preg_match('/^<\s*.+?\/\s*>$/s', $element[1])) {
                } else if(preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $element[1], $element2)) {
                    $pos = array_search($element2[1], $arr_elements);
                    if($pos !== false) {
                        unset($arr_elements[$pos]);
                    }
                } else if(preg_match('/^<\s*([^\s>!]+).*?>$/s', $element[1], $element2)) {
                    array_unshift($arr_elements,
                    strtolower($element2[1]));
                }
                $truncate .= $element[1];
            }
            $content_length = strlen(preg_replace('/(&[a-z]{1,6};|&#[0-9]+;)/i', ' ', $element[2]));
            if($total_length >= $length) {
                break;
            } elseif ($total_length+$content_length > $length) {
                $left = $total_length>$length?$total_length-$length:$length-$total_length;
                $entities_length = 0;
                if(preg_match_all('/&[a-z]{1,6};|&#[0-9]+;/i', $element[2], $element3, PREG_OFFSET_CAPTURE)) {
                    foreach($element3[0] as $entity) {
                        if($entity[1]+1-$entities_length <= $left) {
                            $left--;
                            $entities_length += strlen($entity[0]);
                        } else break;
                    }
                }
                $truncate .= substr($element[2], 0, $left+$entities_length);
                break;
            } else {
                $truncate .= $element[2];
                $total_length += $content_length;
            }
        }
        if(!$exact) {
            $spacepos = strrpos($truncate, ' ');
            if(isset($spacepos)) {
                $truncate = substr($truncate, 0, $spacepos);
            }
        }
        $truncate .= $ending;
        foreach($arr_elements as $element) {
            $truncate .= '</' . $element . '>';
        }
        return $truncate;
    }



    /**
    * creates a tooltip CSS                                                     
    * @param $lien     ->  url link  ex: index.php?file=Download    
    * @param $button   ->  shutter button ex: <img src="" /> ou MONTEXT 
    * @param $content  ->  content displayed in the popup          
    **/
    function popupCss($lien, $button, $content){
    ?>
        <div class="infobulle globalInfobulle">
            <a href="<?php echo $lien; ?>">
                <?php echo $button; ?>
                <p><?php echo $content; ?></p>
            </a>
        </div>
    <?php
    }



    /**
    * MenuSelect function for assigning levels of modules 
    * @param $name      ->  name menuSelect
    * @param $checked   ->  parameter recovery of the variable for the edition
    *    Exemple: levelSelect(‘level’, $level);
    **/
    function levelSelect($name, $checked){

        if($checked != "" && $checked != 0) $check = '<option>'.$checked.'</option>';
    ?>
        <select id="<?php echo $name; ?>" name="<?php echo $name; ?>">
            <?php echo $check; ?>
            <option>0</option>
            <option>1</option>
            <option>2</option>
            <option>3</option>
            <option>4</option>
            <option>5</option>
            <option>6</option>
            <option>7</option>
            <option>8</option>
            <option>9</option>
        </select>
    <?php
    }

?>