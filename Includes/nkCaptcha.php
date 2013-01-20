<?php
// -------------------------------------------------------------------------//
// Nuked-KlaN - PHP Portal                                                  //
// http://www.nuked-klan.org                                                //
// -------------------------------------------------------------------------//
// This program is free software. you can redistribute it and/or modify     //
// it under the terms of the GNU General Public License as published by     //
// the Free Software Foundation; either version 2 of the License.           //
// -------------------------------------------------------------------------//

if (!defined("INDEX_CHECK")) exit('You can\'t run this file alone.');

//réglage captcha (auto | on | off)
define("NKCAPTCHA","auto");
if (isset($_SESSION['captcha'])){
	$GLOBALS['nkCaptchaCache'] = $_SESSION['captcha'];
}else{
	$GLOBALS['nkCaptchaCache'] = uniqid();
}

require_once (dirname(__FILE__) . '/hash.php');

/**
* Create a Captcha code
* @return string Generated code
**/
function Captcha_Generator(){
	global $cookie_captcha;
	static $code = null;
	if ($code == null)
		$code = substr(md5(uniqid()), rand(0, 25), 5);
	$_SESSION['captcha'] = $code;
	return $code;
}

/**
* Check if the code is the good captcha code
* @param string $code
* @return bool
**/
function ValidCaptchaCode($code){
	global $user;
	return NKCAPTCHA == 'off' || ($user != null && $user[1] > 0) || strtolower($GLOBALS['nkCaptchaCache']) == strtolower($code);
}

function create_captcha($style){
    $random_code = Captcha_Generator();

    if ($style == 1){
    ?>

		<p>&nbsp;<?php echo SECURITYCODE; ?>&nbsp;: 

		<?php
		if (@extension_loaded('gd')){
		?>		
			&nbsp;<img src="captcha.php" alt="" title="<?php echo SECURITYCODE; ?>" /></p>
		<?php
		}else {
		?>
			&nbsp;<big><i><?php echo $random_code; ?></i></big></p>
		<?php
		}
		?>

		<label for="code"><?php echo TYPESECCODE; ?></label>&nbsp;:&nbsp;
			<input type="text" id="code" name="code_confirm" size="7" maxlength="5" />

	<?php	
    }
    else if ($style == 2){
    ?>
		<p>&nbsp;<?php echo SECURITYCODE; ?>&nbsp;:</p>

		<?php
		if (@extension_loaded('gd')){
		?>

			<img src="captcha.php" alt="" title="' , SECURITYCODE , '" />

		<?php
		}else{
		?>
		 	<?php echo $random_code; ?>

			<label for="code"><?php echo TYPESECCODE; ?></label>&nbsp;:&nbsp;
				<input type="text" id="code" name="code_confirm" size="6" maxlength="5" />
	<?php	
		}
    }else{

		if (@extension_loaded('gd')){
		?>

			<img src="captcha.php" alt="" title="<?php echo SECURITYCODE; ?>" />

		<?php
		}else{
		?>
			<?php echo $random_code; ?>
			<label for="code"><?php echo TYPESECCODE; ?></label>&nbsp;:&nbsp;
				<input type="text" name="code_confirm" id="code" size="7" maxlength="5" />
		<?php
    	}
    }
	return($random_code);
}

function crypt_captcha($code){
    $temp_code = hexdec(md5($_SERVER['REMOTE_ADDR'] . $code));
    $confirm_code = substr($temp_code, 2, 5);
    return($confirm_code);
}
?>
