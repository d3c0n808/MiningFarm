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
	$loginValid	= $getCredientials->checkLogin($rawCookie);
?>
<!--?xml version="1.0" encoding="iso-8859-1"?-->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title><?php echo outputPageTitle();?> - <?php echo gettext("Main Page");?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
		<link href="/css/mainstyle.css" rel="stylesheet" type="text/css">
	</head> 
	<body>
	<br/><br/>
		<div id="content">
			
				<?php
				//Include the header & slogan
				include($header);
				////////////////////////////
				
				?>
				<div id="mainbox">
					<?php
						//Get credentials
							$getCredientials->getStats();
						
							$getCredientials->getAdminSettings();	
							
						//generate graph information
							//Get this individuals mhash
								$fifteenMinutesAgo = time();
								$fifteenMinutesAgo -= 60*15;
								$userHashHistoryQ = mysql_query("SELECT DISTINCT `timestamp` FROM `stats_userMHashHistory` WHERE `username` LIKE '".$getCredientials->username.".%' AND `timestamp` >= '$fifteenMinutesAgo' AND `mhashes` > 0 ORDER BY `timestamp` ASC");
								$numRows = mysql_num_rows($userHashHistoryQ);
								
								//Go through every time stamp and average out all the workers per timestamp
									$userHashArray = "";
									$timeHashArray = "";
									$poolHashArray = "";
									$poolTotalHashArray = "";
								
								//Show this graph if logged in
								if($numRows > 0){
									$i=0;
									while($time = mysql_fetch_array($userHashHistoryQ)){
										
										$tmpHashAverage = 0;
										$tmpTotalHash = 0;
										//Get all mhash results with this timestamp and average them up
											$getAllWorkerHash = mysql_query("SELECT `mhashes` FROM `stats_userMHashHistory` WHERE `username` LIKE '".$getCredientials->username.".%' AND `timestamp` = '".$time["timestamp"]."' AND `mhashes` > 0");
											$numWorkersThisTime = mysql_num_rows($getAllWorkerHash);
											while($workerHash = mysql_fetch_array($getAllWorkerHash)){
												$tmpHashAverage += $workerHash["mhashes"];
												$tmpTotalHash += $workerHash["mhashes"];
											}
											$tmpHashAverage = $tmpHashAverage/$numWorkersThisTime;
										//Get pool average results
											$getPoolAverageResult = mysql_query("SELECT `averageMhash`, `totalMhash` FROM `stats_poolMHashHistory` WHERE `timestamp` = '".$time["timestamp"]."' LIMIT 0,1");
									
												$poolAverageQ = mysql_fetch_object($getPoolAverageResult);
												$poolAverage = $poolAverageQ->averageMhash;
												$tmpTotalHash = $poolAverageQ->totalMhash;
												//Pool average comes up null sometimes this will prevent a break in the graph
													if(!isSet($poolAverage)){
														$poolAverage = 0;
													}
											
										//Add points to graph
											if($i > 0){
												$userHashArray .= ",";
												$timeHashArray .= ",";
												$poolHashArray .= ",";
												$poolTotalHashArray .= ",";
											}
											$i++;
											$timeHashArray .= "'".date("G:i:s", $time["timestamp"])."'";
											$userHashArray .= $tmpHashAverage;
											$poolHashArray .= $poolAverage;
											$poolTotalHashArray .= $tmpTotalHash;
									}
									
								}else if($numRows == 0){
									//Show this graph when not logged in
									$i=0;
									//Go through the pool history and display that
									$poolHistory = mysql_query("SELECT `averageMhash`, `totalMhash`, `timestamp` FROM `stats_poolMHashHistory` WHERE `timestamp` >= '".$fifteenMinutesAgo."' ORDER BY `timestamp` ASC");
										while($poolHash = mysql_fetch_array($poolHistory)){
											if($i > 0){
												$poolHashArray .=",";
												$timeHashArray .=",";
												$poolTotalHashArray .=",";
											}
											$i++;
											$poolHashArray .= $poolHash["averageMhash"];
											$timeHashArray .= "'".date("G:i:s", $poolHash["timestamp"])."'";
											$poolTotalHashArray .= $poolHash["totalMhash"];
										}
								}
								
							//If theres no data to be displayed even after the above display filler data
								if($poolHashArray == "" && $timeHashArray  == "" && $poolTotalHashArray  == ""){
									$timeHashArray = "'".date("G:i:s", time())."'";
									$poolHashArray = "0";
									$poolTotalHashArray = "0";
								}
							
							if($getCredientials->statsShowAllUsers == 1){
								//
							}
					?>
					<script type="text/javascript">
						var chart1; // globally available
						$(document).ready(function() {
							chart1 = new Highcharts.Chart({
								chart: {
									renderTo: 'graph',
									defaultSeriesType: 'spline',
									width:750,
									height:250
								},
								title: {
									text: 'Overall Network Status'
								},
								xAxis: {
									categories: [<?php echo $timeHashArray;?>]
								},
								yAxis: {
									title: {
										text: 'Mega-Hashes'
									}
								},
								series: [{
									name: 'Pool Average',
									data: [<?php echo $poolHashArray; ?>]
									},
									{
									name: 'Pool Total',
									data: [<?php echo $poolTotalHashArray;?>]
									}
									<?php
										if($userHashArray != ""){
									?>, 
									{
									name: 'Your Average',
									data: [<?php echo $userHashArray?>]
									}
									<?php
										}
									?>]
							});
						});
					</script>
					<div id="graph" align="center">
						<img src="/images/placeholder.jpg" alt="placeholder">
					</div><br/><br/>
					<?php
					//Display the all users graph?
						if($getCredientials->statsShowAllUsers == 1){
					?>
					<div id="graphAllusers" align="center">
						<img src="/images/placeholder.jpg" alt="placeholder">
					</div><br/><br/>
					<?php
						}
					?>
					<br/>
					<!-- quick bloging -->
					<table cellpadding="0" cellspacing="0" border="0" align="center" width="100%">
						<tbody>
							<tr>
								<td>	
									<!-- start blogging -->
									<?php
										//retireve blogs
										$blogs = mysql_query("SELECT `title`, `timestamp`, `message` FROM `blogPosts` ORDER BY `timestamp` DESC");
										while($blog = mysql_fetch_array($blogs)){
									?>
									<table align="center" cellpadding="0" cellspacing="0" class="bigContent" width="100%">
										<tbody>
											<tr>
												<td class="contTL">
												&nbsp;
												</td>
												<td class="contTC">
													Blog Heading
												</td>
												<td class="contTR">
												&nbsp;
												</td>
											</tr>
											<tr>
												<td colspan="3" class="contContent">
													BLOGGING CONTENT
												</td>
											</tr>
										</tbody>
									</table>
									<?php
										}
									?>
								</td>
								<td valign="top">
													<!-- Quick worker stats -->
									<table align="center" cellpadding="0" cellspacing="0" class="bigContent" width="100%">
										<tbody>
											<tr>
												<td class="contTL">
												&nbsp;
												</td>
												<td class="contTC">
													Quick Server Status
												</td>
												<td class="contTR">
												&nbsp;
												</td>
											</tr>
											<tr>
												<td colspan="3" class="contContent">
													<?php
														try{
														//Open a bitcoind connection
															$bitcoinController = new BitcoinClient($rpcType, $rpcUsername, $rpcPassword, $rpcHost);
														}catch(Exception $e){
															echo "Connecting to bitcoin wallet failed | Please contact admin";
														}
														
														if($bitcoinController != false){
															//Show some quick stats
															
																//Get total workers working
																	$fifteenMinutesAgo = time();
																	$fifteenMinutesAgo -= 60*15;
																	$totalWorkersQ = mysql_query("SELECT `id` FROM `stats_userMHashHistory` WHERE `timestamp` < $fifteenMinutesAgo");
																	$totalWorkers = mysql_num_rows($totalWorkersQ);
													?>
													<b>Current Difficulty:</b> <?php echo $bitcoinController->getDifficulty();?><br/>
													<b>Total Workers:</b> <?php echo $totalWorkers;?><br/>
													<?php
														}
													?>
												</td>
											</tr>
										</tbody>
									</table>
								</td>
							</tr>
						</tbody>
					</table><br />

			<?php
			//Include Footer
			////////////////////
			include($footer);
			?>
		</div>
		<br/><Br/>

	</body>
</html>
