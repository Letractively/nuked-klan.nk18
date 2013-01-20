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
    * Function to display the latest comments               
    * @param $idItem        -> id retrieve the content               
    * @param $module        -> Module name to query                
    * @param $nbComment     -> Retrieve a number of comments    
    * @param $splitComment  -> number of words before decoupe        
    **/

    function rating($module, $vid) {
        global $user, $nuked, $visiteur, $language, $nkTpl;
        $level_access = nivo_mod('Vote');
        /* sera mis dans l'administration du module */
        $theme_stars = '1';
        $nb_star     = '5';
        ?>
        <script type="text/javascript" language="javascript" src="media/js/rating.js"></script>
        <?php
        $sql = mysql_query('SELECT vote FROM '.VOTE_TABLE.' WHERE vid = "'.$vid.'" AND module = "'.mysql_real_escape_string(stripslashes($module)).'"');
        $count = mysql_num_rows($sql);
        if ($count > 0) {
            $total = '0';
            while(list($vote) = mysql_fetch_array($sql)) {
                    $total = $total + $vote / $count;
            }
            $note = ceil($total);
        } else { 
            $note = "0";
        }
        ?>
        <script type="text/javascript">
        //<![CDATA[
        function submitRating_<?php echo $vid; ?>(evt) {
            //alert(l'ajax en jquery mais putain ca sux quoi !!!!);
            var xhr = getXhr();
            xhr.onreadystatechange = function(){
                if(xhr.readyState == 4) {
                    if(xhr.status == 200) {
                        leselect = xhr.responseText;
                        document.getElementById('resultat_vote_<?php echo $vid; ?>').innerHTML = leselect;
                        init_rating(<?php echo $vid; ?>,<?php echo $theme_stars; ?>,<?php echo $nb_star; ?>);
                    } else {
                        document.getElementById('resultat_vote_<?php echo $vid; ?>').innerHTML = "Erreur !";
                    }
                } else {
                    document.getElementById('resultat_vote_<?php echo $vid; ?>').innerHTML = "Loading ...";
                }
            }
            document.getElementById('vote_<?php echo $vid; ?>').style.display = 'none';
            var tmp = evt.target.getAttribute('id').substr(5);
            var widgetId = <?php echo $vid; ?>;
            var starNbr = tmp.substr(tmp.indexOf('_')+1);
            var module = '<?php echo addslashes($module); ?>';
            xhr.open("POST","index.php?file=<?php echo $module; ?>&op=sendRating&nuked_nude=index",true);
            xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
            xhr.send("ratingID="+widgetId+"&value="+starNbr+"&moduleId="+module+"");
        }
        function prototypeInit_<?php echo $vid; ?>() {
            var user = '$user';
            init_rating(<?php echo $vid; ?>,<?php echo $theme_stars; ?>,<?php echo $nb_star; ?>);
            $('.globalRating_<?php echo $vid; ?>').bind('click', submitRating_<?php echo $vid; ?>);
        }
        $(document).ready( prototypeInit_<?php echo $vid; ?> );
        //]]>
        </script>

        <div id="vote_<?php echo $vid; ?>" class="globalVoteResult infobulle">
            <div class="globalRating_<?php echo $vid; ?>" id="rating_<?php echo $vid; ?>">
                <?php echo $note; ?>
            </div>
            <?php
            popupCss('#', '<img src="images/rating/vote.png" class="iconeRating" />', NBVOTE.' : '.$count.'&nbsp;-&nbsp;'.RESULTVOTE.' : '.$note .'/'. $nb_star);
            ?>
        </div>
        <div id="resultat_vote_<?php echo $vid; ?>" class="globalResultVote"></div>

    <?php
    }

    function sendRating() {
        global $nuked, $user, $language, $visiteur, $nkTpl;

        $valeur_v = $_POST['value']+1;
        $vid      = $_POST['ratingID'];
        $module   = $_POST['moduleId'];
        $level_access = nivo_mod("Vote");
        /* sera mis dans l'administration du module */
        $theme_stars = '1';
        $nb_star     = '5';
                    

        $sql = mysql_query('SELECT user_id FROM '.VOTE_TABLE.' WHERE vid = "'.$vid.'" AND module = "'.mysql_real_escape_string(stripslashes($module)).'"  AND user_id = "'.$user[0].'"');
        list($user_id) = mysql_fetch_array($sql);

        if ($visiteur >= $level_access && $level_access > -1) {
            if($user[0] != $user_id)  {
                $sql = mysql_query('INSERT INTO '.VOTE_TABLE.' ( `id` , `module` , `vid` , `user_id` , `vote` ) VALUES ( "" , "'.$module.'" , "'.$vid.'" , "'.$user[0].'" , "'.$valeur_v.'" )');

                $sql = mysql_query('SELECT vote FROM '.VOTE_TABLE.' WHERE vid = "'.$vid.'" AND module = "'.mysql_real_escape_string(stripslashes($module)).'"');
                $count = mysql_num_rows($sql);
                if ($count > 0) {
                        $total = '0';
                        while(list($vote) = mysql_fetch_array($sql)) {
                                $total = $total + $vote / $count;
                        }

                        $note = ceil($total);
                } else {
                    $note = "0";
                }
                ?>

                <img src="images/rating/rating_ok.png" alt="" />&nbsp;<?php echo VOTEADD; ?>
            
            <?php
            } else {
            ?>
                <img src="images/rating/rating_error.png" alt="" />&nbsp;<?php echo ALREADYVOTED; ?>
            <?php
            }

        } else if ($level_access == -1) {
            echo $nkTpl->nkModuleOff();
            echo $nkTpl->nkHistoryBack();
        } else if ($level_access >= 1 && $visiteur == 0) {
            echo $nkTpl->nkDisplayError(USERENTRANCE);
        } else {
            echo $nkTpl->nkBadLevel();
            echo $nkTpl->nkHistoryBack();
        }
    }

?>