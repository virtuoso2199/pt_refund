<?php

//start session
ini_set('session.gc_maxlifetime', 600);
session_start();

require('refundFunctions.php');

$db = mysqli_connect('localhost','ptrefund','x22m3y2k','pt_refund'); //connect to database
   if(!$db){die("Can't connect: ". mysqli_connect_error());}

if (array_key_exists('userid', $_SESSION)){	//If user is logged, check for access level 
	if($_SESSION['access']=='S' OR $_SESSION['access']=='U' OR $_SESSION['access']=='A'){
		
			
		//once user is autheticated, check to see if this form has been submitted
		if($_POST['_edit_submit']){ //Edit user form has been submitted so it's time to update the database
			//check for errors
			if(validateUserChanges()=='valid_withPass') //if no errors, update user in db and show success message
			{
				//update user in db
				$encrypted_passwd = crypt($_POST['password']);				
				$query = "UPDATE users SET password='{$encrypted_passwd}' WHERE user_id ='{$_SESSION['userid']}'";
				$result = mysqli_query($db,$query);
								
				
				//show success message
				print '<h3 align="center"> User '.$_POST['username'].' ('.$_POST['first_name'].' '.$_POST['last_name'].') updated!</h3>';
				print '<h4 align="center"><a href="index.php">Return to Homepage</a></h4>';
			
			} else {
				showEditPage($_SESSION['username'], $_SESSION['access'],validateUserChanges());
			}
			
			//if errors exist, show page again & fill in values
		
		} elseif(!$_GET['user_id']) { //form has not been submitted
			showEditPage($_SESSION['username'],$_SESSION['access']); 
		}
		
	} else {
			showLogin('The current user is not authorized to view this page.');	//all other users types OWNED!!
	}
	
} elseif($_POST['username']) { //if user has attempted to login, validate login
   
   
	if(validateLogin($_POST['username'],$_POST['password'])){
		showPage($_SESSION['username'], $_SESSION['access']);	//valid user! Show page!
	} else {
		showLogin('Login invalid. Please try again');	
	}

} else { 		//Else show login screen (no user is logged in and no login attempt has been made)
	showLogin();
}
	

//Check that new user data submitted is valid that returns array of errors
function validateUserChanges (){
	global $_POST;
	//check that passwords match
	if ($_POST['password']!=$_POST['passwordConfirm']){
		$errors[]='Passwords must match';	
	}
	

	if (strlen($_POST['password'])<6){
		if((!is_null($_POST['password']) and !is_null($_POST['passwordConfim']))){
			$errors[]='Passwords must be at least 6 characters long';
		}
			
	}

	if($errors) {
		return $errors;
	} else {
		return 'valid_withPass';	
	}

} 	


//Page Header (sometimes different depending on whether page has restricted access or not)
function showHeader($username='',$accessLvl=''){
	print <<<HEADER
<HTML>
	<HEAD>
		<link rel="stylesheet" type="text/css" href="refundStyle.css">
		<TITLE>CHCB Patient Refund Manager</TITLE>
	</head>
	<body>
		<table id="head"><tr><td><img src = "logo.png" class="logo" /><td><td><h1 class="title">Patient Refund Manager</h1></td></tr></table>
HEADER;

	if($username){
		print '<div class="greeting">';		
		print "<h4>Hi, {$username}!</h4>";
		print "<h4> Your access level is: ";
			if($accessLvl == 'S'){
				print "Superuser </h4>";			
			} elseif($accessLvl == 'U'){
				print "Standard User </h4>";			
			} elseif($accessLvl =='A'){
				print "Approver </h4>";			
			} else {
				print "Unrecognized! </h4>";
			}
		
		
		print "<a href =\"logout.php\">Logout</a></div>";
		
		print "<table class = \"topMenu\"><tr><td><a href=\"index.php\"  class = \"button\" >Home</td><td><a href=\"refunds.php\" class = \"button\">Refunds</a></td><td><a href=\"reports.php\"  class = \"button\">Reports</a></td><td><a href=\"mngaccount.php\" id = \"selected\">My Account</a></td>";
	if ($accessLvl == 'S'){
		print '<td><a href="admin.php" class = "button">Admin</a></td></tr></table>';	
	}else {
		print '</tr></table>';
	}
		
	}

	


}



function showEditPage($username='', $accessLvl = '', $errors = ''){ //page where user will actually edit user information

	showHeader($username, $accessLvl);
	global $db;


	if($errors){
		//show errors at top of page
		print '<h2 class = "error"> The following errors were encountered:</h2>';
		print '<ul><li>';
		print implode('</li><li>', $errors);
		print '</li></ul>';
	}

	$query = "SELECT user_id, first_name,last_name,access_lvl, D.name, username FROM users AS U INNER JOIN departments AS D ON U.dept_id = D.dept_id WHERE user_id = '{$_SESSION['userid']}'";
	$result = mysqli_query($db,$query); 
	$row = mysqli_fetch_array($result);

	print <<<EDITUSERPAGE
		<a href="index.php">Back to Homepage</a>
	
		<h2 align="center">Edit User</h2>
		<form method="POST" action="{$_SERVER['PHP_SELF']}" name="add_user">
      <table style="width: 100%" border="1">
        <tbody>
          <tr>
            <td>First Name</td>
            <td><input maxlength="50" name="first_name" type="text" value ="{$row['first_name']}" readonly><br>
            </td>
          </tr>
          <tr>
            <td>Last Name</td>
            <td><input maxlength="50" name="last_name" type="text" value ="{$row['last_name']}" readonly><br>
            </td>
          </tr>
          <tr>
            <td>New Password</td>
            <td><input maxlength="10" name="password" type="password"><br>
            </td>
          </tr>
          <tr>
            <td>Confirm Password</td>
            <td><input maxlength="10" name="passwordConfirm" type="password"><br>
            </td>
          </tr>
        </tbody>
      </table>
      <input type="hidden" name="_edit_submit" value="1" /><input type="hidden" name = "user_id" value="{$_GET['user_id']}" />
      <button formmethod="post" formaction="{$_SERVER['PHP_SELF']}" value="update" name="Submit">Update User</button></form>
EDITUSERPAGE;
	showFooter();

}





?>