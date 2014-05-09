<?php

//start session
ini_set('session.gc_maxlifetime', 600);
session_start();

require('refundFunctions.php');

$db = mysqli_connect('localhost','ptrefund','x22m3y2k','pt_refund'); //connect to database
   if(!$db){die("Can't connect: ". mysqli_connect_error());}

if (array_key_exists('userid', $_SESSION)){	//If user is logged, check for access level 
	if($_SESSION['access']=='S'){
		//check for $_GET['user_id']. If set, show edit page for that user. Otherwise, show list of users
		if($_GET['user_id']){
			if($_GET['action']=='edit'){
				showEditPage();
			} elseif($_GET['action']=='delete') {		
				showDelPage();
			}
	
		}
			
		//once user is autheticated, check to see if this form has been submitted
		if($_POST['_edit_submit']){ //Edit user form has been submitted so it's time to update the database
			//check for errors
			if(validateUserChanges()=='valid_withPass') //if no errors, update user in db and show success message
			{
				//update user in db
				$encrypted_passwd = crypt($_POST['password']);				
				$query = "UPDATE users SET first_name='{$_POST['first_name']}', last_name='{$_POST['last_name']}', access_lvl='{$_POST['access']}', dept_id='{$_POST['department']}', password='{$encrypted_passwd}', username='{$_POST['username']}' WHERE user_id ='{$_POST['user_id']}'";
				$result = mysqli_query($db,$query);
								
				
				//show success message
				print '<h3 align="center"> User '.$_POST['username'].' ('.$_POST['first_name'].' '.$_POST['last_name'].') updated!</h3>';
				print '<h4 align="center"><a href="admin.php">Return to Admin Page</a></h4>';
			
			} elseif(validateUserChanges()=='valid_noPass'){ //no password change so don't update password
				$query = "UPDATE users SET first_name='{$_POST['first_name']}', last_name='{$_POST['last_name']}', access_lvl='{$_POST['access']}',dept_id='{$_POST['department']}', username='{$_POST['username']}' WHERE user_id ='{$_POST['user_id']}'";
				$result = mysqli_query($db,$query);
								
				
				//show success message
				print '<h3 align="center"> User '.$_POST['username'].' ('.$_POST['first_name'].' '.$_POST['last_name'].') updated!</h3>';
				print '<h4 align="center"><a href="admin.php">Return to Admin Page</a></h4>';
			} else {
				showEditPage($_SESSION['username'], $_SESSION['access'],validateUserChanges());
			}
			
			//if errors exist, show page again & fill in values
		
		} elseif(!$_GET['user_id']) { //form has not been submitted
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
function validateUserChanges (){
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
		if((!is_null($_POST['password']) and !is_null($_POST['passwordConfim']))){
			$errors[]='Passwords must be at least 6 characters long';
		}
			
	}

	if($errors) {
		return $errors;
	} elseif(is_null($_POST['password']) and is_null($_POST['passwordConfim'])) {
		return 'valid_noPass';	
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
		
		print "<table class = \"topMenu\"><tr><td><a href=\"index.php\"  class = \"button\" >Home</td><td><a href=\"refunds.php\" class = \"button\">Refunds</a></td><td><a href=\"reports.php\"  class = \"button\">Reports</a></td><td><a href=\"mngaccount.php\"  class = \"button\">My Account</a></td>";
	if ($accessLvl == 'S'){
		print '<td><a href="admin.php" id = "selected">Admin</a></td></tr></table>';	
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

	$query = "SELECT user_id, first_name,last_name,access_lvl, D.name, username FROM users AS U INNER JOIN departments AS D ON U.dept_id = D.dept_id WHERE user_id = '{$_GET['user_id']}'";
	$result = mysqli_query($db,$query); 
	$row = mysqli_fetch_array($result);

	print <<<EDITUSERPAGE
		<a href="{$_SERVER['PHP_SELF']}">Back to Users List</a>
	
		<h2 align="center">Edit User</h2>
		<form method="POST" action="{$_SERVER['PHP_SELF']}" name="add_user">
      <table style="width: 100%" border="1">
        <tbody>
          <tr>
            <td>First Name</td>
            <td><input maxlength="50" name="first_name" type="text" value ="{$row['first_name']}"><br>
            </td>
          </tr>
          <tr>
            <td>Last Name</td>
            <td><input maxlength="50" name="last_name" type="text" value ="{$row['last_name']}"><br>
            </td>
          </tr>
          <tr>
          	<td>User Name</td>
          	<td><input maxlength="50" name="username" type="text" value ="{$row['username']}"><br />
          </tr>
          <tr>
            <td>Department</td>
            <td>
              <select name="department">
EDITUSERPAGE;

	$query = 'SELECT dept_id, name FROM departments';
	$result = mysqli_query($db,$query);
	
	while($row = mysqli_fetch_array($result)){
		print "<option value=\"{$row['dept_id']}\"";
		if ($_GET['dept_id']==$row['dept_id']){
			print ' selected="selected" ';		
		}
		print ">{$row['name']}</option>";	
	
	}
	print <<<EDITUSERPAGE
              </select>
              <br>
            </td>
          </tr>
          <tr>
            <td>Access Level</td>
            <td>
              <select name="access">
EDITUSERPAGE;
	$query = "SELECT access_lvl FROM users WHERE user_id ='{$_GET['user_id']}'";
	$result = mysqli_query($db,$query);
	$row = mysqli_fetch_array($result);
	print '<option value="U"';
		if($row['access_lvl'] == 'U') { 
			print 'selected="selected">User</option>';
		} else {
			print '>User</option>';
		}
	print '<option value="A"';
		if($row['access_lvl'] == 'A') {
			print	'selected="selected">Approver</option>';	
		} else {
			print '>Approver</option>';
		}
	print '<option value="S"';
		if($row['access_lvl'] == 'S') {
			print 'selected="selected" >Superuser</option>';
		} else {
			print '>Superuser</option>';
		}
                
		print <<<EDITUSERPAGE
              </select>
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
      <button formmethod="post" formaction="{$_SERVER['PHP_SELF']}" value="submit" name="Submit">Update User</button></form>
EDITUSERPAGE;
	showFooter();

}

function showPage($username='', $accessLvl = '', $errors = ''){ //page where user will select user to edit
	global $db;
	showHeader($username, $accessLvl);


	if($errors){
		//show errors at top of page
		print '<h2 class = "error"> The following errors were encountered:</h2>';
		print '<ul><li>';
		print implode('</li><li>', $errors);
		print '</li></ul>';
	}

	$query = "SELECT user_id, first_name,last_name,access_lvl, D.name, U.dept_id, username FROM users AS U INNER JOIN departments AS D ON U.dept_id = D.dept_id WHERE delete_ind IS NULL ORDER BY last_name,first_name";
	$result = mysqli_query($db,$query); 
	print '<br /><br /><div align = "center">';
	print '<table border="1" cellpadding = "3"><tr><td><b>First Name</b></td><td><b>Last Name</b></td><td><b>Username</b></td><td><b>Access Level</b></td><td><b>Department</b></td><td><b>Actions</b></td>';	
	while ($row = mysqli_fetch_array($result)){
		print '<tr><td>'.$row['first_name'].'</td><td>'.$row['last_name'].'</td><td>'.$row['username'].'</td>';
		if ($row['access_lvl']=='U'){
			print '<td>User</td>';		
		} elseif($row['access_lvl']=='A') {
			print '<td>Approver</td>';	
		} elseif($row['access_lvl']=='S') {
			print '<td>Super User</td>';	
		} else {
			print '<td><b>BAD DATA!</b></td>';			
		}
		print '<td>'.$row['name'].'</td>';
		print '<td><a href="'.$_SERVER['PHP_SELF'].'?user_id='.$row['user_id'].'&dept_id='.$row['dept_id'].'&action=edit">Edit</a>&nbsp;&nbsp;<a href="'.$_SERVER['PHP_SELF'].'?user_id='.$row['user_id'].'&action=delete">Delete</a></td></tr>';
	}	
	
	print '</table></div>';
	
	showFooter();

}



?>