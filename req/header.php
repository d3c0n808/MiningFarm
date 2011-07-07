<!--Javascript Area that will be included in all pages-->
	<!--Login Dissapear Script-->
	<script src="/js/login.js"></script>

<!--/////////////////////////////////////////////////-->

<!--High Charts Javascript area-->
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js" type="text/javascript"></script>
<script src="/js/highcharts/highcharts.js" type="text/javascript"></script>
<script type="text/javascript" src="/js/highcharts/themes/greenlikemoney.js"></script>
<!--///////////////////////////-->
<div id="headerbox">	
	<a id="logo" href="/index.php" title="Mining Farm"></a>
	<div id="headerTitle">
		<h1 id="h1Title">
			<a href="/"><?php echo outputHeaderTitle();?></a>
		</h1>
		<span class="slogan" id="slogan"><?php echo outputHeaderSlogan();?></span>
	</div>
<?php
//If a valid login show balance if not logged in show login/register interface
if(!$loginValid){
?>
	<div class="login" align="right">
		<form method="POST" action="/login.php" name="loginForm">
			<input name="username" value="username" id="userForm" onMouseDown="clearUsername();" size="15" type="text"><br>
			<input type="password" name="password" value="password" id="passForm" onMouseDown="clearPassword();" onKeyDown="pressedEnter(event);" onKeyUp="pressedEnter(event);" onKeyPress="pressedEnter(event);" size="15">
			<br>
		</form>
			<input src="/images/register.gif" value="Regsiter" type="image" class="regsiterBtn"  onMouseUp="void(document.location='/register.php');"> &nbsp; <input src="/images/login.gif" value="Login" type="image"  onMouseUp="void(document.forms['loginForm'].submit())">

	</div>
</div>
<?php
}else if($loginValid){
?>
	<div class="userInfo" align="right">
			<?php 
				//get user id stuff....
					$getCredientials->getStats();
				
				//Prevariables
					$totalBalance = "0.00";
				
				//Get Tradehill worth
						$tradeHillQ = mysql_query("SELECT `tradeHillWorth` FROM `websiteSettings`");
						$tradeHillWorth = mysql_fetch_object($tradeHillQ);
						
				//Get default currency settings
					$dbcurrencySetting = outputCurrency();
					
				//Output the users funds according to admin currency settings
					if($dbcurrencySetting == "tradehill-USD"){
					
						//Set total balance in USD with trade hill data	
							$totalBalance = "$".($tradeHillWorth->tradeHillWorth*$getCredientials->accountBalance);
					
					}
					
					if($dbcurrencySetting == "btc"){
						//Set estimated balance
					
						//Set total balance in BTC
							$totalBalance = $getCredientials->accountBalance.' BTC';
					}
					
				//Admin fee
					$adminFee = getAdminFee();
			?>
			<span class="userText">Bitcoin Value: <a href="http://www.tradehill.com/TradeData?r=TH-R13231" target="tradeHillPage"><span class="estimated">$<?php echo $tradeHillWorth->tradeHillWorth;?></span></a></span><br/>
			<span class="userText">Estimated: <span class="estimated"><?php echo round((50-$getCredientials->estimatedReward)-(50*($adminFee*.01)), 2);?> BTC</span></span><br/>
			<span class="userText">Total Balance: <span class="confirmedBalance"><?php echo $totalBalance;?></span></span><br/>
			<a href="/logout.php">Logout</a>
	</div>
</div>
<?php
}
?>
<div id="nav">
	<ul id="list-nav">
		<li><a href="/stats.php"><?php echo gettext("Statistics");?></a></li>
<?php
		if($loginValid){
?>
		<li><a href="/accountDetails.php"><?php echo gettext("Account Details");?></a></li>
		<li><a href="/workers.php"><?php echo gettext("Manage Workers");?></a></li>
<?php
		}
		if($getCredientials->isAdmin){
?>
		<li><a href="/adminPanel.php"><?php echo gettext("(Administration)");?></a></li>
		<li><a href="/adminPanel.php?show=editUsers"><?php echo gettext("(User Management)");?></a></li>
<?php
		}
?>
	</ul>

</div>
