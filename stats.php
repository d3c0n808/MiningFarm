<?php
// Load Linkage Variables //
$dir = dirname(__FILE__);
$req 		= $dir."/req/";
$functions	= $req."functions.php";

//Include hashing functions
include($functions);

//Include bitcoind functions
include($bitcoind);


//Open a bitcoind connection
	$bitcoinController = new BitcoinClient($rpcType, $rpcUsername, $rpcPassword, $rpcHost);


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
			<br/><br/>
			<div id="mainbox">
				<!--List of unconfirmed blocks-->
				<table align="center" cellpadding="0" cellspacing="0" class="bigContent">
					<tbody>
						<tr>
							<td class="contTL">
							&nbsp;
							</td>
							<td class="contTC">
								Blocks awaiting confirmation
							</td>
							<td class="contTR">
							&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan="3" class="contContent">
								<table width="99%" cellpadding="0" cellspacing="0">
									<tdbody>
										<tr>
											<td>
												<b>Block #</b>
											</td>
											<td>
												<b>Total Confirmations</b>
											</td>
											<td>
												<b>Time found</b>
											</td>
											<td>
												<b>Total Amount of your reward</b>
											</td>
										</tr>
											<?php 
												$getCredientials->getStats();
												$currentAdminFee = getAdminFee();
												//Retireve all blocks awaiting confirmation
													$getBlocksQ = mysql_query("SELECT `blockNumber`, `timestamp`, `confirms` FROM `networkBlocks` WHERE `txid` != '' ORDER BY `timestamp` DESC");
													$numBlocksFound = mysql_num_rows($getBlocksQ);
													
													//If their are blocks found, display the Blocks found/ETA next block graph
														if($numBlocksFound > 0){
															//List blocks information
																while($block = mysql_fetch_array($getBlocksQ)){
																	if($loginValid){
																	//Calculate the amount of the reward this user will get this block/round if logged in
																		//Get pool round total
																			$roundTotalQ  = mysql_query("SELECT `id` FROM `shares_history` WHERE `blockNumber` = ".$block["blockNumber"]." AND `our_result` = 'Y'")or die(mysql_error());
																			$roundTotal = mysql_num_rows($roundTotalQ);
																			
																		//Get user round total
																			$roundUserTotalQ  = mysql_query("SELECT `id` FROM `shares_history` WHERE `blockNumber` = ".$block["blockNumber"]." AND `our_result` = 'Y' AND `username` LIKE '".$getCredientials->username.".%'")or die(mysql_error());
																			$roundUserTotal = mysql_num_rows($roundUserTotalQ);
																			
																		//Get percentage of reward
																			$reward = $roundUserTotal/$roundTotal;
																			
																		//Subtract Admin percentage fee
																			$reward = $reward*(50-(50*($currentAdminFee*0.01)));
																			
																	}else{
																			$reward = "Not logged in";
																	}
																
											?>
											<!--Start block information-->
										<tr>
											<td>
												<?php echo $block["blockNumber"];?>
											</td>
											<td>
												<?php echo $block["confirms"];?>
											</td>
											<td>
												<?php echo date("m/d g:i a", $block["timestamp"]);?>
											</td>
											<td>
												<?php echo $reward;?>
											</td>
										</tr>
											<!--End Block Information-->
											<?php
													
																}
														}
													
													if($numBlocksFound == 0){
											?>
										<tr>
											<td>
												N/A
											</td>
											<td>
												Zero
											</td>
											<td>
												0.00
											</td>
											<td>
												Start Your Miners!
											</td>
										</tr>
											<?php
													}
											?>
									</tdbody>
								</table>
							</td>
						</td>
					</tbody>
				</table><br/><br/>
				<!--Blocks found table-->
				<table align="center" cellpadding="0" cellspacing="0" class="bigContent">
					<tbody>
						<tr>
							<td class="contTL">
							&nbsp;
							</td>
							<td class="contTC">
								Blocks Found
							</td>
							<td class="contTR">
							&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan="3" class="contContent">
								<img src="/images/graphIcons/blockFound.png"/> = Block Found<br/>
								<img src="/images/graphIcons/currentTime.png"/> = Current network block<br/>
								<img src="/images/graphIcons/estimatedBlockFound.png"/> = ETA till next block is found
								<br/><br/>
								<?php
									//Open a bitcoind connection
										//Attempted connection
										try{
											$bitcoinController = new BitcoinClient($rpcType, $rpcUsername, $rpcPassword, $rpcHost);
										}catch(Exception $bitcoinFailed){
											echo '<span class="returnError">'.$bitcoindFailed.'</span>';
										}
									
										//If the bitcoin connection is open? Display graph
											if(!isSet($bitcoinFailed)){
												//Get current block number
													$currentBlockNumber = $bitcoinController->getblocknumber();
													
												//Get blocks found
													$blocksFoundQ = mysql_query("SELECT `blockNumber`, `timestamp` FROM `networkBlocks` WHERE `txid` != '' ORDER BY `blockNumber` ASC");
													$numBlocksFound = mysql_num_rows($blocksFoundQ);
													
													//If any blocks have been found
														if($numBlocksFound > 0){
															//Display blocks that have been found with the corrisponding timestamp
																$blockArray = "";
																$timeArray = "";
																$i=0;
																
																//Tmp vars
																$totalDifference = 0;
																$lastDifference = 0;
																$lastBlockNumber = 0;
																while($block = mysql_fetch_array($blocksFoundQ)){
																	//Loop through every block found and add it to the array of data to output on the stats graph
																		if($i > 0){
																			$blockArray .=",";
																			$timeArray .=",";
																		}
																		$i++;
																		$blockArray .="{y: ".$block["blockNumber"].",
																					marker: {
																						symbol: 'url(/images/graphIcons/blockFound.png)'
																					}
																				}";
																		$timeArray .= "'".date("m/d/Y g:i:s a", $block["timestamp"])."'";
																		
																		if(!$lastDifference){
																			$lastDifference = $block["timestamp"];
																		}
																		
																		if($lastDifference){
																			$totalDifference += ($block["timestamp"]-$lastDifference);
																			$lastDifference = $block["timestamp"];
																		}
																		
																		$lastBlockNumber = $block["blockNumber"];
																}
																
																//Prevent division by zero && calculate eta till next block
																	if($i > 0 && $totalDifference > 0){
																		$averageDifference = ($totalDifference/$i);
																		$estimatedFindTimestamp = $lastDifference+$averageDifference;
																		//Get esimated block number by
																			//averaging out the amount of time between blocks found, and then adding the estimated time till that block will be found
																		$estimatedBlockNumber = round($lastBlockNumber+(($averageDifference/60)/10));
						
																	}
																
																//Add current time here if its before estimated block finding
																	if($estimatedFindTimestamp > time()){
																		//Add currentime time
																			$timeArray .=",'".date("m/d/y g:i:s a", time())."'";
																			$blockArray .=",{
																						y: ".$currentBlockNumber.",
																						marker: {
																						symbol: 'url(/images/graphIcons/currentTime.png)'
																						}
																					}";
																	}
																
																
																//Add expected block found ( only if there is an expected block calculation )
																	if($estimatedBlockNumber > 0){
																	$timeArray .= ",'".date("m/d/Y g:i:s a", $estimatedFindTimestamp)."'";
																	$blockArray .=",{
																				y: ".$estimatedBlockNumber.",
																				marker: {
																				symbol: 'url(/images/graphIcons/estimatedBlockFound.png)'
																				}
																			}";
																	}
																			
																//Add current time here if it is after the estimated block finding
																	if($estimatedFindTimestamp < time() ){
																		//Add currentime time
																			$timeArray .=",'".date("m/d/y g:i:s a", time())."'";
																			$blockArray .=",{
																						y: ".$currentBlockNumber.",
																						marker: {
																						symbol: 'url(/images/graphIcons/currentTime.png)'
																						}
																					}";
																	}
														}else{
															//No blocks have been found yet, show this in the graph
																$blocksFoundTitle = '(No Blocks have been found yet)';
																
																$timeArray = "'".date("m/d g:i", time())."'";
																$blockArray = "{
																			y: ".$currentBlockNumber.",
																			marker: {
																				symbol: 'url(/images/graphIcons/currentTime.png)'
																			}
																		}";
														
														}
											}
											
										
		
								?>
								<script type="text/javascript">
									var chart1; // globally available
									$(document).ready(function() {
										chart1 = new Highcharts.Chart({
											chart: {
												renderTo: 'blocksFound',
												defaultSeriesType: 'spline',
												width:750,
												height:250
											},
											title: {
												text: '<?php if(!isSet($blocksFoundTitle)){
															echo 'Overall Blocks Found';
														}else if(isSet($blocksFoundTitle)){
															echo $blocksFoundTitle;
														}
													?>'
											},
											xAxis: {
												categories: [<?php echo $timeArray;?>]
											},
											yAxis: {
												title: {
													text: 'Block Number'
												}
											},
											series: [{
												name: 'Longest Block Chain',
												data: [
													<?php echo $blockArray; ?>
														
													]
												}]
										});
									});
								</script>
								<div id="blocksFound" align="center">
									<h3>No blocks found yet</h3>
								</div><br/><br/>
							</td>
						</td>
					</tbody>
				</table>
				</div><br/><br/>
			<?php
			//Include Footer
			////////////////////
			include($footer);
			?>
</div>

</body></html>
