<?php
    /****************************************************************/
    /* Coupe une chaine en gardant le formatage HTML                */
    /* @param string $text Texte à couper                           */
    /* @param integer $length Longueur à garder                     */
    /* @param string $ending Caractères à ajouter à la fin          */
    /* @param boolean $exact Coupure exacte                         */
    /* @return string                                               */
    /*                                                              */
    /****************************************************************/
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
?>