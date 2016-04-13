<?php

$salt = "In 1972, a crack commando unit was sent to prison by a military court for a crime they didn't commit.";
$salt = str_replace(" ","",$salt);

$daysChange = 90; // This is the number of days before someone is forced to change their password. Set for 90 to start.

$tbl_name = "usernames";

include("includes/dbconnect.php");

$myusername = $_POST['username'];
$mypassword = $_POST['pwd'];

$myusername = stripslashes($myusername);
$mypassword = stripslashes($mypassword);
$myusername = mysql_real_escape_string($myusername);
$mypassword = mysql_real_escape_string($mypassword);
$encrypt = crypt($mypassword,$salt);

$sql = "SELECT * FROM " . $tbl_name . " WHERE Name='" . $myusername . "' and Password='" . $encrypt . "'";
$result = mysql_query($sql);

$count=mysql_num_rows($result);



// $_SESSION['passwordCheck'] = 0; Commented out the password Check variable. Will bring this back in and count to 3 before lockdown.

if($count==1) {
	session_register("myusername");
	session_register("mypassword");
   while ($row = mysql_fetch_assoc($result)) {
	   $_SESSION['permissions'] = $row['Permissions'];
	   $tempPerm = $row['Permissions'];
	   $permSQL = "SELECT * FROM permissions WHERE Level='" . $tempPerm . "'";
	   $_SESSION['permNum'] = $row['Permission'];
	   $_SESSION['country_prot'] = $row['CountryID'];
	   $_SESSION['userName'] = $myusername;
	   $_SESSION['userID'] = $row['UserID'];
	   
	   
	   $loginTime = date("Y/m/d");
	   
	   	$loginSQL = "UPDATE usernames SET LastLogin = '" . $loginTime . "' WHERE `UserID` = '" . $_SESSION['userID'] ."' LIMIT 1";
		$loginConfirm = mysql_query($loginSQL);

	   $passDate = $row['PasswordChanged'];
	   $forceChange = $row['NewPassword'];
	   $lockCheck = $row['Locked'];
		$rights = $_SESSION['permNum'];
		switch ($rights) {
			case "Distributor":
				$rightsNum = 1;
				break;
			case "TM":
				$rightsNum = 2;
				break;
			case "Admin":
				$rightsNum = 3;
				break;
		}
		$_SESSION['rightsNum'] = $rightsNum;
	   $_SESSION['authenticated'] = "BigDamnHeroes";
	    $y = substr($passDate,0,4);
		$m = substr($passDate,5,2);
		$d = substr($passDate,8,2);
		$passForward = mktime(0, 0, 0, $m  , $d + $daysChange , $y);
		$jump = "location:index.php";
		if (time() > $passForward) {
			$_SESSION['changeMessage'] = "It has been " . $daysChange . " days since your password was changed. Please create a new password.";
			$passForceSQL = "UPDATE `usernames` SET `NewPassword` = '1' WHERE `UserID` = '" . $_SESSION['userID'] ."' LIMIT 1";
			$passForceResult = mysql_query($passForceSQL);
			$jump = "location:changepassword.php";
		}
		elseif ($forceChange == 1) {
			$_SESSION['changeMessage'] = "Please change your current password.";
			$jump = "location:changepassword.php";
		} else {
			if (isset($_SESSION['destinationPage'])) {
				$jump = "location:" . $_SESSION['destinationPage'];
				unset($_SESSION['destinationPage']);
			} else {
				$jump = "location:index.php";
			}
//		   $_SESSION['changeMessage'] = "The current time is " . time() . " and the deadline for changing the password is ". $passForward;
		}
   }
   unset($_SESSION['myusername']);
   unset($_SESSION['mypassword']);
	header($jump);
}
else {
	if (isset($_SESSION['passwordCheck'])) {
		$t = $_SESSION['passwordCheck'];
		$t = $t + 1;
		$_SESSION['passwordCheck'] = $t;
		if ($t >= 3) {
			$_SESSION['loginError'] = "This is the third attempt at trying to enter this password. This account has been locked up. An email has been sent to you with instructions on how to reset your password.";
			$_SESSION['passwordCheck'] = 0;
			$sql = "UPDATE `usernames` SET `Locked` = '1' WHERE `Name` = '" . $myusername . "' LIMIT 1;";
			mysql_query($sql);
			$_SESSION['blockedID'] = $myusername;
			// Put in the code (when written) for the password reset and the account freeze.
			header("location:forcelock.php");
		} else {
			$_SESSION['loginError'] = "The password you have tried is incorrect. Please try again";
			header("location:login.php");
		}
	} else {
		$_SESSION['passwordCheck'] = 1;
		$_SESSION['loginError'] = "The password you have tried is incorrect. Please try again";
		header("location:login.php");
	}
}
echo "Okay, I'm not sure why you aren't going anywhere. You really should be gone by now.";
?>