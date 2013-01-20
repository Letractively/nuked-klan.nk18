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
    * Fonction pour afficher le dernier commentaire
    * @param $idItem        -> id du contenu a récuperer
    * @param $module        -> Nom du module à interroger
    * @param $nbComment     -> Nombre de commentaires a récuperer
    * @param $splitComment  -> nombre de mot avant decoupe
    **/

    function viewComment($module, $idItem, $nbComment = null, $splitComment = null, $entente= null) {
        global $nuked, $language, $user, $visiteur;

        $level_access = nivo_mod('Comment');
        $req = 'SELECT active FROM '.COMMENT_MOD_TABLE.' WHERE module = "'.$module.'"';
        $sql = mysql_query($req);
        list($active) = mysql_fetch_array($sql);

        if(!is_null($nbComment)){
            $nbComment = 'LIMIT '.$nbComment;
        }
        
        if($active == 1  && $visiteur >= $level_access && $level_access > -1) {
            $req = 'SELECT titre, comment, autor, autor_id, date FROM '.COMMENT_TABLE.' WHERE im_id = "'.$idItem.'" AND module = "'.$module.'" '.$nbComment;
            $sqlComment = mysql_query($req);
            $count = mysql_num_rows($sqlComment);
            ?>
            <div class="contentSectionComment">

                <?php
                if(!is_null($entente)){
                ?>
                    <h4>
                        <?php echo LASTFILECOMMENT; ?>
                    </h4>
                <?php
                }
                while(list($titre, $comment, $autor, $autor_id, $date) = mysql_fetch_array($sqlComment)) {
                   
                    $titre = printSecuTags($titre);
                    $titre = nk_CSS($titre);
                    $comment = nk_CSS($comment);
                    $autor = nk_CSS($autor);

                    if(!empty($autor_id)){
                        $req_member = 'SELECT pseudo FROM '.USER_TABLE.' WHERE id = "'.$autor_id.'"';
                        $sql_member = mysql_query($req_member);
                        $test = mysql_num_rows($sql_member);
                    }

                    if(!empty($autor_id) && $test > 0){
                        list($pseudo) = mysql_fetch_array($sql_member);
                        $autor = '<a href="index.php?file=Members&amp;op=detail&amp;autor='.$autor.'" title="">'.$pseudo.'</a>';
                    }else{
                        $autor = $autor;
                    }
                         
                    if(!is_null($splitComment))
                    {        
                        $comment =  cutText($comment, $splitComment);
                    }
                        
                    ?>
                    <div class="bgCommentAltern">
                        <?php echo $comment; ?>
                        <p>
                            <?php echo COMMENTBY; ?>:&nbsp;<?php echo $autor; ?>&nbsp;<?php echo COMMENTTHE; ?>&nbsp;<small><?php echo nkDate($date); ?></small>
                        </p>
                    </div>
                <?php
                }

                if($count >= 1) {

                    if(is_null($nbComment)){
                        $link = '<a class="nkPopupBox buttonLink buttonLink'.$module.'" href="index.php?file='.$module.'&amp;nuked_nude=index&amp;op=post_com&amp;idItem='.$idItem.'&amp;module='.$module.'">'.ADDCOMMENT.'</a>';
                    }else{
                        $link = '<a class="nkPopupBox buttonLink buttonLink'.$module.'" href="index.php?file='.$module.'&amp;nuked_nude=index&amp;op=viewComment&amp;idItem='.$idItem.'&amp;module='.$module.'">'.SEEALLCOMMENT.'</a>';
                    }
                }else{

                    $link = '<a class="nkPopupBox buttonLink buttonLink'.$module.'" href="index.php?file='.$module.'&amp;nuked_nude=index&amp;op=post_com&amp;idItem='.$idItem.'&amp;module='.$module.'">'.NEWCOMMENT.'</a>';
                
                ?>
                    <p id="centerInformation">
                        <?php echo NOCOMMENTDB; ?>
                    </p>
                <?php
                }
                ?>
            </div>
            <?php 
            echo $link;
        }
    }

    function post_com($module, $idItem){        
        global $user, $nuked, $language, $theme, $visiteur;

        include_once('Includes/nkCaptcha.php');
        if (NKCAPTCHA == "off"){
            $captcha = 0;
        }else if ((NKCAPTCHA == 'auto' OR NKCAPTCHA == 'on') && $user[1] > 0){
            $captcha = 0;
        }else{
            $captcha = 1;
        }

        define('EDITOR_CHECK', 1);

        $level_access = nivo_mod('Comment');
        $req = 'SELECT active FROM '.COMMENT_MOD_TABLE.' WHERE module = "'.$module.'"';
        $sql = mysql_query($req);
        list($active) = mysql_fetch_array($sql);

        if($active == 1  && $visiteur >= $level_access && $level_access > -1) {
        ?>
        <section id="commentPostGlobal">
            <form method="post" action="index.php?file=<?php echo $module; ?>&amp;nuked_nude=index&amp;op=post_comment"> 
                <div>       
                    <label for="title"><?php echo TITLE; ?> : </label>
                        <input id="title" type="text" name="title" size="40" maxlength="40" />
                    <label for="com_pseudo"><?php echo PSEUDO; ?> : 
                    <?php
                    if ($user){
                    ?>
                        &nbsp;&nbsp;<?php echo $user[2]; ?></label>
                        <input id="com_pseudo" type="hidden" name="nick" value="<?php echo $user[2]; ?>" />
                    <?php
                    }
                    else{
                    ?>
                        </label>
                        <input id="com_pseudo" type="text" size="30" name="nick" maxlength="30" />
                    <?php
                    }
                    ?>
                </div>
                    <label for="e_basic"><?php echo MESSAGE; ?> : </label>
                        <textarea id="e_basic" name="text"></textarea>
                        <p>
                        <?php
                        if ($captcha == 1) create_captcha(1);
                        ?>
                        </p>
                        <input type="hidden" name="idItem" value="<?php echo $idItem; ?>" />
                        <input type="hidden" name="module" value="<?php echo $module; ?>" />
                        <input type="submit" value="<?php echo SEND; ?>" class="nkPopupBoxForm" />
            </form>
        </section>
    <?php
        }
    }

    function post_comment($module, $idItem, $title, $text, $nick){
        global $user, $nuked, $theme, $visiteur, $level_admin;

        include_once('Includes/nkCaptcha.php');
        if (NKCAPTCHA == "off"){
            $captcha = 0;
        }else if ((NKCAPTCHA == 'auto' OR NKCAPTCHA == 'on') && $user[1] > 0){
            $captcha = 0;
        }else{
            $captcha = 1;
        }

        $level_access = nivo_mod('Comment');

        if ($captcha == 1 && !ValidCaptchaCode($_REQUEST['code_confirm'])){

        ?>
            <label id="centerInformation"><?php echo BADCODECONFIRM; ?></label>
        <?php
        }else{

            $req = 'SELECT active FROM '.COMMENT_MOD_TABLE.' WHERE module = "'.$module.'"';
            $sql = mysql_query($req);
            list($active) = mysql_fetch_array($sql);

            if($active == 1  && $visiteur >= $level_access && $level_access > -1) {
                if ($visiteur > 0){
                    $autor = $user[2];
                    $autor_id = $user[0];
                }else{
                    $nick = printSecuTags($nick);
                    $nick = verif_pseudo($nick);
                }

                if ($nick == "error1"){
                ?>
                    <p id="centerInformation"><?php echo NONICK; ?></p>
                <?php
                }else if ($nick == "error2"){
                ?>
                   <p id="centerInformation"><?php echo RESERVNICK; ?></p>
                <?php
                }
                else if ($nick == "error3"){
                ?>
                    <p id="centerInformation"><?php echo BANNEDNICK; ?></p>
                <?php
                }else if($title == ""){
                ?>
                    <p id="centerInformation"><?php echo NOTITLE; ?></p>
                <?php
                }else if($text == ""){
                ?>
                    <p id="centerInformation"><?php echo NOTEXT; ?></p>
                <?php
                }else{

                    if($module != "" && $idItem != ""){

                        $autor = $nick;
                        $autor_id = "";
                        $req_flood = 'SELECT date FROM '.COMMENT_TABLE.' WHERE autor = "'.$autor.'" OR autor_ip = "'.$user_ip.'" ORDER BY date DESC LIMIT 0, 1';
                        $flood = mysql_query($req_flood);
                        list($active) = mysql_fetch_array($sql);
                        list($flood_date) = mysql_fetch_row($flood);
                        $anti_flood = $flood_date + $nuked['post_flood'];
                        $date = time();

                        if ($date < $anti_flood && $user[1] < admin_mod("Comment")){
                        ?>
                            <p id="centerInformation"><?php echo NOFLOOD; ?></p>
                            <?php
                            redirect('index.php?file='.$module.'&nuked_nude=index&op=viewComment&idItem='.$idItem.'&module='.$module, 2);
                            echo historyBack();
                            closetable();
                            footer();
                            exit();

                        }else{
                        
                            $title = printSecuTags($title);
                            $text = secu_html(html_entity_decode($text));
                            $text = stripslashes($text);
                            $module = mysql_real_escape_string(stripslashes($module));
                            if (strlen($title) > 40){
                                 $title = substr($title, 0, 40) . "...";
                            }
                            $add = 'INSERT INTO '.COMMENT_TABLE.' ( `id` , `module` , `im_id` , `autor` , `autor_id` , `titre` , `comment` , `date` , `autor_ip` ) VALUES ( "", "'.$module.'" , "'.$idItem.'" , "'.$autor0.'" , "'.$autor_id.'", "'.$title.'" , "'.mysql_real_escape_string($text).'" , "'.$date.'" , "'.$user_ip.'")';
                            $add = mysql_query($add);
                            ?>
                            
                            <p id="centerInformation"><?php echo COMMENTADD; ?></p>
                        <?php
                        }
                    }else{
                    ?>
                        <p id="centerInformation"><?php echo COMMENTNOTADD; ?></p>
                    <?php
                    }
                }
            }
        }
    }

    function del_comment($cid){
        global $nuked, $user, $theme, $nuked_nude, $visiteur;

        $level_admin = admin_mod($module);

        if ($visiteur >= $level_admin){
            $req = 'SELECT module, im_id FROM '.COMMENT_TABLE.' WHERE id = "'.$cid.'"';
            $sql = mysql_query($req);
            list($module, $idItem) = mysql_fetch_array($sql);

            $del = 'DELETE FROM '.COMMENT_TABLE.' WHERE id = "'.$cid.'"';
            $del = mysql_query($del);

            ?>
            <p id="centerInformation"><?php echo COMMENTDEL; ?></p>
            <?php
            redirect('index.php?file='.$module.'&nuked_nude=index&op=viewComment&idItem='.$idItem.'&module='.$module, 2);
        }else{
            echo badLevel();
            redirect('index.php?file='.$module.'&nuked_nude=index&op=viewComment&idItem='.$idItem.'&module='.$module, 5);
        }
    }

    function modif_comment($cid, $titre, $texte, $module, $idItem){
        global $nuked, $user, $theme, $visiteur;

        $level_admin = admin_mod($module);
        $texte = secu_html(html_entity_decode($texte));

        if ($visiteur >= $level_admin){

            $upd = 'UPDATE '.COMMENT_TABLE.' SET titre = "'.$titre.'", comment = "'.$texte.'" WHERE id = "'.$cid.'"';
            $upd = mysql_query($upd);
            ?>
            <p id="centerInformation"><?php echo COMMENTMODIF; ?></p>

            <?php
            redirect('index.php?file='.$module.'&nuked_nude=index&op=viewComment&idItem='.$idItem.'&module='.$module, 2);
        }
    }

    function edit_comment($cid){
        global $user, $nuked, $bgcolor2, $theme, $visiteur;

        define('EDITOR_CHECK', 1);

        $level_admin = admin_mod($module);
        if ($visiteur >= $level_admin){

            $req = 'SELECT autor, autor_id, titre, comment, autor_ip, module, im_id FROM '.COMMENT_TABLE.' WHERE id = "'.$cid.'"';
            $sql = mysql_query($req);
            list($auteur, $autor_id, $titre, $texte, $ip, $module, $idItem) = mysql_fetch_array($sql);

            $titre = printSecuTags($titre);

            if($autor_id != ""){

                $req = 'SELECT pseudo FROM '.USER_TABLE.' WHERE id = "'.$autor_id.'"';
                $sql_member = mysql_query($req);
                list($autor) = mysql_fetch_array($sql_member);
            }else{
                $autor = $auteur;
            }
            ?>
                <form method="post" action="index.php?file=<?php echo $module; ?>&amp;nuked_nude=index&amp;op=modif_comment" >
                    <label for="titre"><?php echo TITLE; ?>&nbsp;:&nbsp;</label>
                        <input id="titre" type="text" name="titre" size="40" maxlength="40" value="<?php echo $titre; ?>" />
                    <label for="e_basic"><?php echo MESSAGE; ?>&nbsp;:&nbsp;</label>
                        <textarea id="e_basic" name="texte"><?php echo $texte; ?></textarea>
                    <p><?php echo NICK; ?>&nbsp;:&nbsp;<?php echo $autor.'&nbsp;('.$ip; ?>)</p>
                        <input type="hidden" name="cid" value="<?php echo $cid; ?>" />
                        <input type="hidden" name="idItem" value="<?php echo $idItem; ?>" />
                        <input type="hidden" name="module" value="<?php echo $module; ?>" />
                    <input type="submit" value="<?php echo SEND; ?>" />
                </form>
        <?php
        }else{        
            echo badLevel();
            redirect('index.php?file='.$module.'&nuked_nude=index&op=viewComment&idItem='.$idItem.'&module='.$module, 5);
        }
    }

?>