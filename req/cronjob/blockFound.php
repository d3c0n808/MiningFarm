<?php
//Comment the following line when debuging this page.
//error_reporting(0);
echo "Starting.....<br/>";
// Load Linkage Variables //
	$dir = dirname(__FILE__);
	$dir		= str_replace("/req/cronjob", "", $dir);
	$req 		= $dir."/req/";
	$functions	= $req."functions.php";

//Load Functions
	include($functions);

//Load bitcoind function
	include($bitcoind);

//Connect to database
	connectToDb();

//Set adminFee
$serverFee = getAdminFee();

//Open a bitcoind connection
	$bitcoinController = new BitcoinClient($rpcType, $rpcUsername, $rpcPassword, $rpcHost);

//Get some variables
	$transactions = $bitcoinController->query("listtransactions");
print_r($transactions);
//Go through all the transactions check if there is 50BTC inside
	$numAccounts = count($transactions);
	for($i = 0; $i < $numAccounts; $i++){
		if($transactions[$i]["category"] == "generate" || $transactions[$i]["category"] == "immature"){
			//At this point we may or may not have found a block,
			//Check to see if this account addres is already added to `networkBlocks`
				$accountExistsQ = mysql_query("SELECT `id` FROM `networkBlocks` WHERE `txid` = '".$transactions[$i]["txid"]."' ORDER BY `blockNumber` DESC LIMIT 0,1")or die(mysql_error());
				$accountExists = mysql_num_rows($accountExistsQ);
	
				//If the account dosen't exist that means we found a block, now add it to the database so we can track the confirms
					if(!$accountExists){
						//Get last empty block so we can input it the address for confirm tracking
							$lastEmptyBlockQ = mysql_query("SELECT `id`, `blockNumber` FROM `networkBlocks` WHERE `txid` = '' ORDER BY `blockNumber` DESC LIMIT 0,1");
							$lastEmptyBlockObj = mysql_fetch_object($lastEmptyBlockQ);
							$lastEmptyBlock = $lastEmptyBlockObj->id;
							$lastEmptyBlockNumber = $lastEmptyBlockObj->blockNumber;

							$insertBlockSuccess = mysql_query("UPDATE `networkBlocks` SET `txid` = '".$transactions[$i]["txid"]."' WHERE `id` = '$lastEmptyBlock'")or die(mysql_error());
							if($insertBlockSuccess){
								//Move all `shares` into `shares_history`
									//Get list of `shares`
										$listOfSharesQ = mysql_query("SELECT `id`, `time`, `rem_host`, `username`, `our_result`, `upstream_result`, `reason`, `solution` FROM `shares` ORDER BY `id` DESC");
										
										$insertQuery = "INSERT INTO `shares_history` (`time`, `blockNumber`, `rem_host`, `username`, `our_result`, `upstream_result`, `reason`, `solution`) VALUES ";
										$i = 0;
										while($shares = mysql_fetch_array($listOfSharesQ)){
											$i++;
											if($i==1){
												$deleteId = $shares["id"];
											}else if($i > 1){
												$insertQuery .=",";
											}

											//Split the wierd timestamp set by MySql
												$splitInputTimeDate = explode(" ", $shares["time"]);
												$splitInputDate = explode("-", $splitInputTimeDate[0]);
												$splitInputTime = explode(":", $splitInputTimeDate[1]);
				
											//Make wierd timestamp into a regular Unixtimestamp
												$unixTime = mktime($splitInputTime[0], $splitInputTime[1], $splitInputTime[2], $splitInputDate[1], $splitInputDate[2], $splitInputDate[0]);
										
											$insertQuery .= "('$unixTime', '".$lastEmptyBlockNumber."', '".$shares["rem_host"]."','".$shares["username"]."','".$shares["our_result"]."','".$shares["upstream_result"]."', '".$shares["reason"]."','".$shares["solution"]."')";
										}

										//Commence the $insertQuery
											mysql_query($insertQuery)or die(mysql_error());

							}
					}
			}
		
	}



//Go through all the transctions from bitcoind and update their confirms associated with their `networkBlock`
	for($i = 0; $i < $numAccounts; $i++){
		//Check to see if this address was one of the winning addresses from `networkBlocks`
			$txId = $transactions[$i]["txid"];
			$winningAccountQ = mysql_query("SELECT `id` FROM `networkBlocks` WHERE `txid` = '$txId' LIMIT 0,1");
			$winningAccount = mysql_num_rows($winningAccountQ);
			
			if($winningAccount > 0){
				//This is a winning account
					$winningAccountObj	= mysql_fetch_object($winningAccountQ);
					$winningId		= $winningAccountObj->id;
					$confirms		= $transactions[$i]["confirmations"];
					$orphan			= $transactions[$i]["category"];
					
					if($orphan == "orphan"){
						$orphan = 1;
					}else if($orphan != "orphan"){
						$orphan = 0;
					}

					//Update X amount of confirms
						mysql_query("UPDATE `networkBlocks` SET `confirms` = '$confirms', `orphan` = '$orphan' WHERE `id` = '$winningId'");
			}
	}


//Go through all of `shares_history` that are uncounted shares; Check if there are enough confirmed blocks to award user their BTC
	//Get uncounted shares
		$shareHolderUsernameList = mysql_query("SELECT DISTINCT `username` FROM `shares_history` WHERE `shareCounted` = '0' ORDER BY `blockNumber` ASC ");
		
		//Go through all the usernames that are awaiting to be rewarded
			while($waitingUsername = mysql_fetch_array($shareHolderUsernameList)){
				//Get list of blocks that this user has shares in
					$blocksQ = mysql_query("SELECT DISTINCT `blockNumber` FROM `shares_history` WHERE `shareCounted` = '0' AND `username` = '".$waitingUsername["username"]."'");
					while($block = mysql_fetch_array($blocksQ)){
					
						//Check if the selected block has enough confirms//
							$enoughConfirmsQ = mysql_query("SELECT `confirms` FROM `networkBlocks` WHERE `blockNumber` = '".$block["blockNumber"]."'");
							$enoughConfirmsObj = mysql_fetch_object($enoughConfirmsQ);
							$enoughConfirms = $enoughConfirmsObj->confirms;
			
							if($enoughConfirms >= 120){
							
								//Count all the shares this username has, reward them in there account balance, then mark all the shares as counted
									$numTotalUsersSharesQ = mysql_query("UPDATE `shares_history` SET `shareCounted` = '1' WHERE `shareCounted` = '0' AND `blockNumber` = '".$block["blockNumber"]."' AND `username` = '".$waitingUsername["username"]."' AND `our_result` = 'Y'")or die(mysql_error());
									$numTotalUsersShares = mysql_affected_rows();
						
								//Get the total amount of valid shares subbmited this round
									$numTotalPoolSharesQ = mysql_query("SELECT `id` FROM `shares_history` WHERE `blockNumber` = '".$block["blockNumber"]."' AND `our_result` = 'Y'")or die(mysql_error());
									$numTotalPoolShares = mysql_num_rows($numTotalPoolSharesQ);
									
									
								//Calculate total reward for this round
									$numTotalUsersShares--;
									//E for Effort
										$E = $numTotalUsersShares/$numTotalPoolShares;
										
									//P for Pretotal
										$P = $E*50;
										
									//A for admin fee
										$A = $P-($P*($serverFee*0.1));
										
									//total reward
										$totalReward = $A;
										
								//update the owner of this workers account balance
									//get owner userId
										$ownerIdQ = mysql_query("SELECT `associatedUserId` FROM `pool_worker` WHERE `username` = '".$waitingUsername["username"]."' LIMIT 0,1");
										$ownerIdObj = mysql_fetch_object($ownerIdQ);
										$ownerId = $ownerIdObj->associatedUserId;

									//Update balance
										mysql_query("UPDATE `accountBalance` SET `balance` = `balance`+$totalReward WHERE `userId` = '".$ownerId."'");
							}
					}
			}

?>
