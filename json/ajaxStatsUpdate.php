<?php
//This page will output the latest updates for stats

$act = $_GET["act"];
$timestamp = $_GET["timestamp"];
if($act == "overviewOfPool"){
	//Last time stamp
	$lastTimestamprequested = $timestamp; //This will give a thresh-hold to look through all the stats that are past this timestamp so we can update properly
	
	//Get
}