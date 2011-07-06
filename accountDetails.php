<?php
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

if($loginValid){
	//Check which action this user is trying to commence
		$act = $_POST["act"];

		if($act != ""){
			//Open a bitcoind connection
				$bitcoinController = new BitcoinClient($rpcType, $rpcUsername, $rpcPassword, $rpcHost);

			//Check to see if there authorisation pin matches the one in database
				$hashedInputPin = hash("sha256", $_POST["authPin"]);
				$hashedDbPin = $getCredientials->hashedAuthPin;
				if($hashedDbPin == $hashedInputPin){
					if($act == "editIdentity"){
						//Update identity information
							$payoutAddress = mysql_real_escape_string($_POST["payoutAddress"]);
							$threshHold = mysql_real_escape_string($_POST["payoutThreashHold"]);
							
							//Validate $payoutAddress is valid;
								$openBitcoinClient	= new Bitcoin();
								
								$updateSuccess = mysql_query("UPDATE `accountBalance` SET `payoutAddress` = '".$payoutAddress."', `threshhold` = '".$threshHold."' WHERE `userId` = '".$getCredientials->userId."'")or die(mysql_error());
								if($updateSuccess){
									$goodMessage = gettext("Information was successfully updated!");
								}else if(!$updateSuccess){
									$returnError = gettext("Database Error | Contact the admin");
								}
					}

					if($act == "manualCashout"){
						//Manually cashing out
					
							//Does this accountbalance meet the `cashoutMinimum`
								$accountBalance = $getCredientials->accountBalance;
								$cashOutAddress = $getCredientials->sendAddress;
								$userId		= $getCredientials->userId;
								$cashOutMin	= getCashoutMin();
								if($accountBalance >= $cashOutMin){
									//Subtract $accountBalance by 0.01 for the hardwired transaction fee
										$accountBalance -= 0.01;

										$successSend = $bitcoinController->sendtoaddress($cashOutAddress, $accountBalance);

									//Reset account balance to zero
										if($successSend){
											mysql_query("UPDATE `accountBalance` SET `balance` = '0' WHERE `userId` = '".$userId."'");
											$goodMessage = gettext("Successfully sent the amount of ").$accountBalance.gettext(" minus the 0.01 transaction fee to the bitcoin address of ").$cashOutAddress;
										}else{
											$returnError = gettext("Bitcoind Query Error | Contact admin!");
										}

								}else if($accountBalance < $cashOutMin){
									//No enough funds
									$returnError =  gettext("The operator thinks it is best to have atleaset <b>".$cashOutMin."BTC</b> to cashout.");
								}
					}
				}else{
					$returnError = gettext("Authorisation Pin number you entered didn't match our records");
				}

				
		}
		
		$adminFee = getAdminFee();
?>
<!--?xml version="1.0" encoding="iso-8859-1"?-->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title><?php echo outputPageTitle();?> - Account Details</title>
		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
		<!--This is the main style sheet-->
		<link rel="stylesheet" href="/css/mainstyle.css" type="text/css" />
		<script type="text/javascript" src="/js/tooltipFollower.js"/>
		<?php
			//If user isn't logged in load the login.js
			if(!$loginSuccess){
		?>
			<script src="/js/login.js"></script>
		<?php
			}
		?>
		<script type="text/javascript" src="/js/swfobject/swfobject.js"></script>
	</head>
	<body>
		<div id="content">
			<?php
			//Include the header & slogan
			include($header);
			////////////////////////////
			?>	
			<div id="tooltip">&nbsp;</div>
			<?php
				//Only show if there is something to show
					if(isSet($goodMessage) || isSet($returnError)){
			?>
			<span class="goodMessage"><?php echo $goodMessage; ?></span><br/>
			<span class="returnError"><?php echo $returnError; ?></span><br/>
			<?php
					}
			?>
			<br/><br/>
			<div id="mainbox">
				<table align="center" cellpadding="0" cellspacing="0" class="bigContent">
					<tbody>
						<tr>
							<td class="contTL">
							&nbsp;
							</td>
							<td class="contTC">
								Identity Details
							</td>
							<td class="contTR">
							&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan="3" class="contContent">
								<!--Identity Details Begin Content-->
								<?php echo gettext("Username");?>: <?php echo $getCredientials->username;?><br/>
								<?php echo gettext("Confirmed Email");?>: <?php echo $getCredientials->email;?><br/>
								<hr size="1" width="100%"/>
								<?php echo gettext("Confirmed Balance");?>: <?php setlocale(LC_MONETARY, 'en_US'); echo $getCredientials->accountBalance;?> BTC<br/>
								<?php echo gettext("Unconfirmed Balance");?>: <?php echo $getCredientials->pendingBalance;?> BTC<br/>
								<?php echo gettext("Estimated Reward this Round");?>: <?php echo $getCredientials->estimatedReward*(50-(50*($adminFee*.01)));;?> BTC
								<!--Identity Details End Content-->
								
								<!--JSON Data-->
								<h3 class="accountHeader"><?php echo gettext("JSON Mining &amp; Worker Data");?></h3>
								<input type="text" name="nothing" value="<?php echo $getCredientials->apiToken;?>" size="40" onMouseOver="showTooltip('<?php echo gettext("API token to give you <i>private</i> access to your worker status");?>');" onMouseOut="hideTooltip();"/><br/>
								<a class="accountLinks" href="/json/workerstatus.php?apiToken=<?php echo $getCredientials->apiToken;?>"><?php echo gettext("Worker Status");?></a><br/>
							</td>
						</td>
					</tbody>
				</table>
				<br/><br/>
				<table align="center" cellpadding="0" cellspacing="0" class="bigContent">
					<tbody>
						<tr>
							<td class="contTL">
							&nbsp;
							</td>
							<td class="contTC">
								<?php echo gettext("Edit your payout");?>
							</td>
							<td class="contTR">
							&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan="3" class="contContent">
								<!--Identity Details Begin Content-->
								<form action="accountDetails.php" method="post">
								<input type="hidden" name="act" value="editIdentity">
								<?php echo gettext("Payout Address");?>:<input type="text" size="32" name="payoutAddress" value="<?php echo $getCredientials->sendAddress;?>"><br/>
								<?php echo gettext("Automatic Payout at");?>:<input type="text" size="5" name="payoutThreashHold" value="<?php if(!isSet($getCredientials->threashhold)){ echo "0.5";}else{ echo $getCredientials->threashhold;}?>"><b>BTC</b> (0 = <?php echo gettext("Disabled");?>)<br/>
								<i><?php echo gettext("Authorisation Pin");?>:</i> <input type="password" name="authPin" value="" size="4" maxlength="4"><br/>
								<hr size="1" width="100%">
								<input type="submit" value="<?php echo gettext("Update Payout Address");?>">
								</form>
								<!--Identity Details End Content-->
							</td>
						</td>
					</tbody>
				</table>
				<br/><br/>
				<table align="center" cellpadding="0" cellspacing="0" class="bigContent">
					<tbody>
						<tr>
							<td class="contTL">
							&nbsp;
							</td>
							<td class="contTC">
								<?php echo gettext("Manual Instant Payout");?>
							</td>
							<td class="contTR">
							&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan="3" class="contContent">
								<!--Manual Payout Begin Content-->
									<form action="accountDetails.php" method="post">
										<input type="hidden" name="act" value="manualCashout">
										<i>Authorisation Pin:</i> <input type="password" name="authPin" value="" size="4" maxlength="4"><br/>
										You will be sending the amount of <b><?php echo $getCredientials->accountBalance;?>BTC</b>
										<br/>to the bitcoin address of <?php
												if(isSet($getCredientials->sendAddress)){
													echo $getCredientials->sendAddress;
												}else{
													echo "<b>".gettext("None")."</b>";
												}?><br/><hr size="1" width="100%">
										<input type="submit" value="<?php echo gettext("Execute Payout");?>">
									</form>
								<!--Manual Payout End Content-->
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
	</body>
</html>

<?php
}else{
	header("Location: /");
}
?>