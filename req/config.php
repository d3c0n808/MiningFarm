<?php
//Fit to your need variables // These should be the only variables you need to edit
$mysqlUsername	= "root";
$mysqlPassword	= "13linux13";
$mysqlDatabase	= "miningfarm";
$mysqlHost	= "localhost";

//Linkage
$header		= $req."header.php";
$menu		= $req."menu.php";
$footer		= $req."footer.php";
$bitcoind	= $req."/bitcoinWallet/bitcoin.inc.php";

//Cookies!
$cookieName = "miningfarm#2";
$cookiePath = "/";
$cookieDomain = "";

//Email
$fromAddress = "Localhost@Localhost.com";

//Bitcoind RPC information
$rpcType	= "http";
$rpcUsername	= "bitcoins";
$rpcPassword 	= "lolsalad";
$rpcHost	= "127.0.0.1";
?>