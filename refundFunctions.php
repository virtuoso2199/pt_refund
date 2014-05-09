<?php
//Common functions for all CHCB Patient Refund Pages

function showFooter(){

print <<<FOOTER
	<br><br>
	</div> 
	<div class="footer">Created by Jonathan Bowley<br /> &copy; Community Health Centers of Burlington, Inc. 2014</div>
	</body>
	</html>
FOOTER;

}

function showLogin($error=''){
	
	showHeader();

	if($error){
		print $error;	
	}	
	
	print <<<LOGINFORM
		<form action="{$_SERVER['PHP_SELF']}" method="post">
		<table>
			<tr><td><b>Username:</b></td><td><input type="text" name="username" /></td></tr>
			<tr><td><b>Password:</b></td><td><input type="password" name="password" /></td></tr>
			<tr><td colspan="2"><button name="Login" value="Login" type="submit">Login</button></td></tr>
		</table>
		</form>
LOGINFORM;
	
	showFooter();

}

function validateLogin($username ='', $password=''){
	global $_SESSION;	
	global $db;
	global $_POST;	

	//check password is correct for supplied username
	$query = 	"SELECT user_id, password, first_name,last_name,access_lvl FROM users WHERE username = '{$_POST['username']}'";
	$result = mysqli_query($db,$query);
	$row = mysqli_fetch_array($result);
	$dbPassword = $row['password'];
	
	if($dbPassword==crypt($password,$dbPassword)){
		$_SESSION['userid'] = $row['user_id'];
		$_SESSION['username'] = $row['first_name'].' '.$row['last_name'];
		$_SESSION['access'] = $row['access_lvl'];
		return true;	
	} else {
		return false;	
	}

}


?>