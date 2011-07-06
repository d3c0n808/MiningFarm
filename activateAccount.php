<?php
// Load Linkage Variables //
	$dir = dirname(__FILE__);
	$req 		= $dir."/req/";
	$functions	= $req."functions.php";

//Load Functions
	include($functions);


//Check if supplied deatils match those in the databse
	$username = $_GET["username"];
	$authPin = $_GET["authNumber"];
	
	if(!isSet($username) || !isSet($authPin)){
		//Reitreve from post
			$username = $_POST["username"];
			$authPin = $_POST["authNumber"];
	}
	
	//Activate account
	if($username && $authPin){
		$activateSuccess = activateAccount($username, $authPin);
		if($activateSuccess > 0){
			$goodMessage = "Your acount is activated, You can login to your account now";
		}else{
			$returnError = "Sorry we couldn't find the account you were looking for";
		}
	}
?>
<html>
	<head>
		<title><?php echo outputPageTitle();?> - Main Page</title>
		<!--This is the main style sheet-->
		<link rel="stylesheet" href="/css/mainstyle.css" type="text/css" /> 
		<script src="/js/login.js"></script>

		<script type="text/javascript" src="/js/swfobject/swfobject.js"></script>
	</head>
	<body>
		<div id="content">
			<?php
			//Include the header & slogan
			include($header);
			////////////////////////////
			?>
			<div id="bodyContent">
				<div id="blogContainer">
						<span class="goodMessage"><?php echo $goodMessage; ?></span><br/>
						<span class="returnError"><?php echo $returnError; ?></span><br/>
						<div id="activateEmail">
							<?php
								$authNumber = "";
								$username = "Username";

							?>
							<form action="activateAccount.php" method="post">
								<input type="hidden" name="act" value="activate"/>
								<span class="activateEmail">Type in your username &amp; authorization number below</span><br/>
								<input type="text" value="<?php echo $authPin ?>" name="authNumber" value="" size="56"><br/>
								<input type="text" value="<?php echo $username; ?>" name="username"><br/>
								<input type="submit" value="Authorise Email">
							</form>
						</div>
				</div>
				<?php
					//Output Footer
					include($footer);
					///////////////
				?>
			</div>
		</div>
	</body>
</html>