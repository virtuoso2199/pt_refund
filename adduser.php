<?php

//start session
ini_set('session.gc_maxlifetime', 600);
session_start();

require('refundFunctions.php');

$db = mysqli_connect('localhost','ptrefund','x22m3y2k','pt_refund'); //connect to database
   if(!$db){die("Can't connect: ". mysqli_connect_error());}

if (array_key_exists('userid', $_SESSION)){	//If user is logged, check for access level 
	if($_SESSION['access']=='S'){
		//once user is autheticated, check to see if this form has been submitted
		if($_POST['_submit_check']){ //form has been submitted
			//check for errors
			if(validateNewUser()=='valid') //if no errors, create user in db and show success message
			{
				//create user in db
				
				$encrypted_passwd = crypt($_POST['password']);				
				$query = "INSERT INTO users (first_name, last_name, access_lvl, dept_id, password, username) VALUES ('{$_POST['first_name']}','{$_POST['last_name']}','{$_POST['access']}','{$_POST['department']}','{$encrypted_passwd}','{$_POST['username']}')";
				$result = mysqli_query($db,$query);
								
				
				//show success message
				print '<h3 align="center"> User '.$_POST['username'].' ('.$_POST['first_name'].' '.$_POST['last_name'].') created!</h3>';
				print '<h4 align="center"><a href="admin.php">Return to Admin Page</a></h4>';
			
			} else {
				showPage($_SESSION['username'], $_SESSION['access'],validateNewUser());
			}
			
			//if errors exist, show page again & fill in values
		
		} else { //form has not been submitted
			showPage($_SESSION['username'],$_SESSION['access']); //show page only if user is a super user
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
function validateNewUser (){
	global $_POST;
	//check that passwords match
	if ($_POST['password']!=$_POST['passwordConfirm']){
		$errors[]='Passwords must match';	
	}
	
	if (strlen($_POST['username'])<3){
		$errors[]='Usernames must be at least 3 characters long';	
	}

	if (strlen($_POST['first_name'])<2){
		$errors[]='First names must be at least 2 characters long';	
	}
	
	if (strlen($_POST['last_name'])<2){
		$errors[]='Last names must be at least 2 characters long';	
	}

	if (strlen($_POST['password'])<6){
		$errors[]='Passwords must be at least 6 characters long';	
	}

	if($errors) {
		return $errors;
	} else {
		return 'valid';	
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
		
		print "<table class = \"topMenu\"><tr><td><a href=\"index.php\"  class = \"button\" >Home</td><td><a href=\"refunds.php\" class = \"button\">Refunds</a></td><td><a href=\"reports.php\"  class = \"button\">Reports</a></td><td><a href=\"mngaccount.php\"  class = \"button\">My Account</a></td>";
	if ($accessLvl == 'S'){
		print '<td><a href="admin.php" id = "selected">Admin</a></td></tr></table>';	
	}else {
		print '</tr></table>';
	}
		
	}

	


}



function showPage($username='', $accessLvl = '', $errors = ''){

	showHeader($username, $accessLvl);
	global $db;


	if($errors){
		//show errors at top of page
		print '<h2 class = "error"> The following errors were encountered:</h2>';
		print '<ul><li>';
		print implode('</li><li>', $errors);
		print '</li></ul>';
	}

	print <<<ADDUSERPAGE
	
		<h2 align="center">Add a New User</h2>
		<form method="POST" action="{$_SERVER['PHP_SELF']}" name="add_user">
      <table style="width: 100%" border="1">
        <tbody>
          <tr>
            <td>First Name</td>
            <td><input maxlength="50" name="first_name" type="text" value ="{$_POST['first_name']}"><br>
            </td>
          </tr>
          <tr>
            <td>Last Name</td>
            <td><input maxlength="50" name="last_name" type="text" value ="{$_POST['last_name']}"><br>
            </td>
          </tr>
          <tr>
          	<td>User Name</td>
          	<td><input maxlength="50" name="username" type="text" value ="{$_POST['username']}"><br />
          </tr>
          <tr>
            <td>Department</td>
            <td>
              <select name="department">
ADDUSERPAGE;

	$query = 'SELECT dept_id, name FROM departments';
	$result = mysqli_query($db,$query);
	
	while($row = mysqli_fetch_array($result)){
		print "<option value=\"{$row['dept_id']}\"";
		if ($_POST['department']==$row['dept_id']){
			print ' selected="selected" ';		
		}
		print ">{$row['name']}</option>";	
	
	}
	print <<<ADDUSERPAGE
              </select>
              <br>
            </td>
          </tr>
          <tr>
            <td>Access Level</td>
            <td>
              <select name="access">
                <option value="U">User</option>
                <option value="A">Approver</option>
                <option value="S">Superuser</option>
              </select>
            </td>
          </tr>
          <tr>
            <td>Password</td>
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
      <input type="hidden" name="_submit_check" value="1" />
      <button formmethod="post" formaction="{$_SERVER['PHP_SELF']}" value="submit" name="Submit">Add User</button></form>
ADDUSERPAGE;
	showFooter();

}


?>