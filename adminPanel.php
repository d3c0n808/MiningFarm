<?php
//Pre define //
	$show = $_GET['show'];
	$searchUsername = $_POST['searchUsername'];
		

// Load Linkage Variables //
	$dir = dirname(__FILE__);
	$req 		= $dir."/req/";
	$functions	= $req."functions.php";

//Include hashing functions
	include($functions);

//Include bitcoind functions
	include($bitcoind);

//Set user details for userInfo box
	$rawCookie		= "";
	if(isSet($_COOKIE[$cookieName])){
		$rawCookie	= $_COOKIE[$cookieName];
	}
	$getCredientials	= new getCredientials;
	$loginValid		= $getCredientials->checkLogin($rawCookie);

	$isAdmin = $getCredientials->isAdmin;
	if($loginValid && $isAdmin){
		//Check if there was an action
		$act = $_POST["act"];
		$hashedAuthInput = hash("sha256", $_POST["authPin"]);
		if($act &&  $hashedAuthInput == $getCredientials->hashedAuthPin){
			if($act == "websiteSettings"){
				//Update websiteSettings
					$postHeader = mysql_real_escape_string($_POST["header"]);
					$postEmail	= mysql_real_escape_string($_POST["confirmEmail"]);
					$postSlogan = mysql_real_escape_string($_POST["slogan"]);
					$postBrowserTitle = mysql_real_escape_string($_POST["browserTitle"]);
					$postCashOut = mysql_real_escape_string($_POST["cashoutMin"]);
					$postFee = mysql_real_escape_string($_POST["serverFee"]);
					$currencyData = mysql_real_escape_string($_POST["currencyData"]);
					
					mysql_query("UPDATE `websiteSettings` 
							SET `header` = '".$postHeader."',
								`noreplyEmail` = '".$postEmail."',
								`slogan` = '".$postSlogan."',
								`browserTitle` = '".$postBrowserTitle."',
								`cashoutMinimum` = '".$postCashOut."',
								`serverFeePercentage` = '".$postFee."',
								`currencyData` = '".$currencyData."'")or die(mysql_error());
			
			}else if($act == "addBlog"){
				//Add blog to database
					//Mysql injection prevention
						$blogTitle = mysql_real_escape_string($_POST["blogTitle"]);
						$blogContent = mysql_real_escape_string($_POST["blogContentInput"]);
					mysql_query("INSERT INTO `blogPosts` (`timestamp`, `title`, `message`)
									VALUES('".time()."', '".$blogTitle."', '".$blogContent."')");
			}
		}else if($act && $hashAuthInput != $getCredientials->hashedAuthPin){
			$returnError = gettext("Auth pin was not valid!");
		}

		//Check if $show was set, and figure out what to show
		if($show == "updateSearchedUsers"){
			//Go through array of users and update the list of users to disable
				$postUserIds = $_POST["userIdArray"];
				$postUserIds = explode(",", $postUserIds);
				$numIds = count($postUserIds);

			//Update output variables
				$updatedOutput = "";

				for($i=0; $i < $numIds; $i++){
					//Find the selection the admin specified, and set to enable/disabled based on the selction
							$tmpUserId		= $postUserIds[$i];
							$selectedInput		=  $_POST["user".$postUserIds[$i]];
							$selectInput 		= mysql_real_escape_string($selectedInput);
							if($selectedInput == "on"){
								//We want to update it to disabled
									mysql_query("UPDATE `websiteUsers` SET `disabled` = 1 WHERE `id` = '".$tmpUserId."' LIMIT 1")or die(mysql_error());
									$updateOutput .='<span class="goodMessage">'.gettext("Set userId ").$tmpUserId.gettext(' to Disabled').'</span><br/>';
						
							}else if($selectedInput == ""){
								//We want to update it to enabled
									mysql_query("UPDATE `websiteUsers` SET `disabled` = 0 WHERE `id` = '".$tmpUserId."' LIMIT 1")or die(mysql_error());
									$updateOutput .='<span class="goodMessage">'.gettext('Set userId ').$tmpUserId.gettext(' to Enable').'</span><br/>';
							}
				}

			//Simulate search
				$show = "searchUsers";
				$searchUsername = $_GET["searchUsername"];
		}
		
?>
<!--?xml version="1.0" encoding="iso-8859-1"?-->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title><?php echo outputPageTitle();?> - <?php echo gettext("Administration");?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
		<link href="/css/mainstyle.css" rel="stylesheet" type="text/css">
		<script type="text/javascript" src="/js/tooltipFollower.js"></script>
		<script type="text/javascript">
		function updateBrowserTitle(){
			document.title = document.getElementById("browserTitleInput").value;
		}

		function updateHeader(){
			document.getElementById("h1Title").innerHTML = '<a href="/">'+document.getElementById("headerInput").value+'</a>';
		}

		function updateSlogan(){
			document.getElementById("slogan").innerHTML = document.getElementById("sloganInput").value;
		}
		
		function updateBlogTitle(){
			document.getElementById("newBlogTitle").innerHTML = document.getElementById("blogTitleInput").value;
		}
		function updateBlogContent(){
			document.getElementById("newBlogContent").innerHTML = document.getElementById("blogContentInput").value;
		}
		</script>
	</head> 
	<body>
	<br/><br/>
		<div id="content">
			<?php
			//Include the header & slogan
			include($header);
			////////////////////////////
			$getCredientials->getAdminSettings();
			?>
			<span class="goodMessage"><?php echo $goodMessage; ?></span><br/>
			<span class="returnError"><?php echo $returnError; ?></span><br/>
			<div id="tooltip">&nbsp;</div>
			<div id="mainbox">
			
			<!--Start Administration Panel-->
				<table align="center" cellpadding="0" cellspacing="0" class="bigContent">
					<tbody>
						<tr>
							<td class="contTL">
							&nbsp;
							</td>
							<td class="contTC">
								Administration Panel
							</td>
							<td class="contTR">
							&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan="3" class="contContent">
								<?php
									//Decide what we want to display based on what $act says
									////////////////////////////////////////////////////////
									if($show == "" || $show == "editBlog"){
										
								?>
								
									<h2 style="text-decoration:underline;"><?php echo gettext("Website Settings");?></h2>
										<form action="?" method="post">
											<input type="hidden" name="act" value="websiteSettings">
											<?php echo gettext("Browser Title");?>:<input type="text" id="browserTitleInput" name="browserTitle" value="<?php echo $getCredientials->adminBrowserTitle;?>" onKeyPress="updateBrowserTitle();" onKeyUp="updateBrowserTitle();" onKeyDown="updateBrowserTitle();"><br/>
											<?php echo gettext("Header");?>:<input type="text" name="header" value="<?php echo $getCredientials->adminHeader;?>" id="headerInput" onKeyPress="updateHeader();" onKeyUp="updateHeader();" onKeyDown="updateHeader();"><br/>
											<?php echo gettext("Solgan");?>:<input type="text" name="slogan" value="<?php echo $getCredientials->adminSlogan;?>" id="sloganInput" onKeyPress="updateSlogan();" onKeyUp="updateSlogan();" onKeyDown="updateSlogan()"><br/>
											<?php echo gettext("Cashout Minimum");?>:<input type="text" name="cashoutMin" value="<?php echo $getCredientials->adminCashoutMin;?>" size="4" maxlength="10"><br/>
											<?php echo gettext("Confirm Email");?>: <input type="text" name="confirmEmail" value="<?php echo $getCredientials->adminEmail;?>"><br/>
											<?php echo gettext("Server Fee");?>: <input type="text" size="5" name="serverFee" value="<?php echo getAdminFee();?>"/>%<br/>
											<?php echo gettext("Currency Dispaly");?>: <select name="currencyData">
																	<option value="btc">Bitcoins (BTC)</option>
																	<option value="tradehill-USD">United $tates Dollar (USD)</option>
																</select>
											<br/>
											<?php echo gettext("Auth Pin");?>:<input type="password" name="authPin" value="" size="4" maxlength="4"><br/>
											<input type="submit" value="<?php echo gettext("Update Website Settings");?>" />
										</form>
									<hr size="1" width="100%"><br/><br/>
								<?php
									}else if($show == "editUsers"){
								?>
									<h2 style="text-decoration:underline;">Search for a user (% = Wildcard)</h2>
										<form action="?show=searchUsers" method="post">
											By username:<input type="text" name="searchUsername" value=""/><br/>
											<input type="submit" value="Search For user">
										</form>
								<?php
									}else if($show == "searchUsers"){
								?>
									<div class=<?php echo $updateOutput;?>
									<h2 style="text-decoration:underline;">Search for a user (% = Wildcard)</h2>
										<form action="?show=searchUsers" method="post">
											By username:<input type="text" name="searchUsername" value=""/><br/>
											<input type="submit" value="Search For user">
										</form><br/><br/>
								<?php
											$searchUsername = mysql_real_escape_string($searchUsername);
										//Query for a list of users that match this username
											$searchQ = mysql_query("SELECT `disabled`, `email`, `username`, `id`, `loggedIp` FROM `websiteUsers` WHERE `username` LIKE '".$searchUsername."'");
								?>
									<form action="?show=updateSearchedUsers&searchUsername=<?php echo $searchUsername;?>" method="post">
									<h2 style="text-decoration:underline;">Results for <i><?php echo $searchUsername; ?></i></h2>
									<input type="submit" value="Execute Changes"><br/>
										<?php
											$userIdArray = "";
											//List output from $searchQ;
											while($user = mysql_fetch_array($searchQ)){
										?>
											<?php echo $user["username"]." &middot; ".$user["email"];?> 
											<input type="checkbox" name="user<?php echo $user["id"];?>" <?php if($user["disabled"]){ echo "checked";}?> onMouseOver="showTooltip('<span style=\'color:red;\'>Disable</span> this user');" onMouseOut="hideTooltip();"/><br/> 
										<?php
												//Make array of userId's to post
													if($userIdArray != ""){
														$userIdArray .= ",";
													}
													$userIdArray .= $user["id"];
											}
										?>
											<input type="hidden" name="userIdArray" value="<?php echo $userIdArray;?>"/>
								<?php
									}
								?>

							</td>
						</td>
					</tbody>
				</table>
				<br><br>
			</div>
			<?php
			//Include Footer
			////////////////////
			include($footer);
			?>
		</div>
		<br/><Br/>

	</body>
</html>

<?php
}else{
	header("Location: /");
}
?>