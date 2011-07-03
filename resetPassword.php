<?php
//Define the undefined
	$returnError = "";
	$tmpUsername = "";
	$tmpPassword = "";
	$tmpPassword2	= "";
	$tmpAuthPin	= "";
	$hashedAuthInput = "";
	$currentAuthPin = "";

// Load Linkage Variables //
	$dir = dirname(__FILE__);
	$req 		= $dir."/req/";
	$functions	= $req."functions.php";

//Load Functions
	include($functions);

//Connect to Db
	connectToDb();

//Perform register if the user isn't already logged in
	$act =  $_POST["act"];
	
	if($act == "reset"){
			//Check inputted details make sure they are okay
				$validCredentials = 1;
				if($_POST["password"] != $_POST["password2"]){
					$validCredentials = 0;
				}

				if(strlen($_POST["authPin"]) < 4){
					$validCredentials = 0;
				}

				$hashedAuthInput = hash("sha256", $_POST["authPin"]);
				
				//Get auth pin for username inputted
					$username = mysql_real_escape_string($_POST["username"]);
					$currentAuthPin = mysql_query("SELECT `authPin` FROM `websiteUsers` WHERE `username` = '".$username."'");
					$currentAuthPin = mysql_fetch_array($currentAuthPin);
					$currentAuthPin = $currentAuthPin["authPin"];
					if($hashedAuthInput != $currentAuthPin ){
						$validCredentials = 0;
					}
				
				//If $validCredientials is still true, then reset password
					if($validCredentials == 1){
						//Mysql Injection Protection Agency
							$password = mysql_real_escape_string($_POST["password"]);
							$authPin = mysql_real_escape_string($_POST["authPin"]);

						//Make sure the username is already in the database
							$usernameExistsQ = mysql_query("SELECT `id` FROM `websiteUsers` WHERE `username` = '".$username."'");
							$usernameExists = mysql_num_rows($usernameExistsQ);

							if($usernameExists == 1){
									//Hash password
										
										$hashedPassword = hash("sha256", $password);
										
									//Insert password into the `websiteUsers` database
										$insertSuccess = mysql_query("UPDATE `websiteUsers` SET `password` = '$hashedPassword' WHERE `username` = '".$username."'") or die(mysql_error());
									
									$goodMessage = "Password successfully changed!";
								}
					}else if($validCredentials == 0){
						$returnError = gettext("Please check that you passwords match as well as your Auth Pin");
					}
	}
?>
<!--?xml version="1.0" encoding="iso-8859-1"?-->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title><?php echo outputPageTitle();?> - <?php echo gettext("Reset Password");?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
		<link href="/css/mainstyle.css" rel="stylesheet" type="text/css">
		<script type="text/javascript" src="/js/tooltipFollower.js"></script>
	</head> 
	<body>
		<div id="content">
			<?php
			//Include the header & slogan
			include($header);
			////////////////////////////
			?><br/>
			<div id="tooltip">&nbsp;</div>
			<div id="mainbox">
				<table align="center" cellpadding="0" cellspacing="0" class="bigContent">
					<tbody>
						<tr>
							<td class="contTL">
							&nbsp;
							</td>
							<td class="contTC">
								Reset Password
							</td>
							<td class="contTR">
							&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan="3" class="contContent">
							<span class="goodMessage"><?php echo $goodMessage;?></span><br/>
							<span class="returnError"><?php echo $returnError; ?></span><br/><br/>
										<h2 id="registerHeader"><?php echo gettext("We just need a few details");?></h2><br/>
										
										<form action="/resetPassword.php" method="post">
											<input type="hidden" name="act" value="reset"/>
											<?php
												if(isSet($_POST["username"])){
													$tmpUsername	= $_POST["username"];
												}else{
													$tmpUsername 	= "";
												}
												if(isSet($_POST["password"])){
													$tmpPassword	= $_POST["password"];
												}else{
													$tmpPassword	= NULL;
												}
												if(isSet($_POST["password2"])){
													$tmpPassword2	= $_POST["password2"];
												}else{
													$tmpPassword2	= "";
												}
												if(isSet($_POST["authPin"])){
													$tmpAuthPin	= $_POST["authPin"];
												}else{
													$tmpAuthPin	= "";
												}
											?>
											<?php echo gettext("Username");?>:<input type="text" name="username" value="<?php echo $tmpUsername; ?>" maxlength="20" size="10"/><br/>
											<?php echo gettext("Password");?>:<input type="password" name="password" value="<?php echo $tmpPassword;?>" maxlength="45" size="10"/><br/>
											<?php echo gettext("Retype Password");?>:<input type="password" name="password2" value="<?php echo $tmpPassword2;?>" maxlength="45" size="10"/><br/>
											<hr size="1" width="100%"><br/>
											<?php echo gettext("Authorization Pin");?>: <input type="password" name="authPin" value="<?php echo $tmpAuthPin;?>" size="4" maxlength="4"><?php echo gettext("(Enter your 4 digit pin #)");?><br/>
											<input type="submit" value="<?php echo gettext("Reset Password");?>" />
										</form>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<?php
				//Output Footer
				include($footer);
				///////////////
			?>
		</div>
	</body>
</html>
