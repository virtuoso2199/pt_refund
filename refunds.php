<?php

//start session
ini_set('session.gc_maxlifetime', 600);
session_start();

require('refundFunctions.php');
require_once "Mail.php";

$db = mysqli_connect('localhost','ptrefund','x22m3y2k','pt_refund'); //connect to database
   if(!$db){die("Can't connect: ". mysqli_connect_error());}

if (array_key_exists('userid', $_SESSION)){	//If user is logged, check for access level 
	if($_SESSION['access']=='S' OR $_SESSION['access']=='A' OR $_SESSION['access']=='U'){
		//check for $_GET['refund_id']. If set, show edit page for that user. Otherwise, show list of users
		if($_GET['refund_id']){
			if($_GET['action']=='edit'){
				showEditPage();
			} elseif($_GET['action']=='delete') {		
				showDelPage();
			} elseif($_GET['action']=='approve') {		
				showApprovePage();
			}
	
		}
			
		//once user is autheticated, check to see if this form has been submitted
		if($_POST['_edit_submit']){ //Edit user form has been submitted so it's time to update the database
			//check for errors
			if(validateRefundChanges()=='valid'){ //if no errors, update user in db and show success message
				//update user in db
				$now = date("Y-m-d H:i:s");				
				$query = "UPDATE refund SET NG_enc_id = '{$_POST['enc_nbr']}', dt_required = '{$_POST['dt_required']}', amount = '{$_POST['amount']}', payable='{$_POST['payable']}', addr_ln_1 ='{$_POST['addr_ln_1']}', addr_ln_2='{$_POST['addr_ln_2']}', city ='{$_POST['city']}', state='{$_POST['state']}', zip='{$_POST['zip']}', purpose='{$_POST['purpose']}', status='UPDATED', modfied_by={$_SESSION['userid']}, modified_dt='{$now}',comments ='{$_POST['comments']}' WHERE refund_id = {$_POST['refund_id']} ";
				$result = mysqli_query($db,$query);
				if (mysqli_error($result)){
					print mysqli_error($result);
				}
				
				//send notification that a new refund has been created
				 
 
 				$from = "Patient Refund <noreply@chcb.org>";
 				$to = "Jonathan Bowley <virtuoso2199@gmail.com>";
 				$subject = "Updated Patient Refund Request";
 				$body = "Hello,\n\n patient refund request # {$_POST['refund_id']} has been updated. Please login to the Patient Refund web application to review.";
 
 				$host = "ssl://smtpout.secureserver.net";
 				$port = "465";
 				$username = "jonathan@jonathanbowley.com";
 				$password = "paw52beh";
 
 				$headers = array ('From' => $from,
  				 'To' => $to,
   			 'Subject' => $subject);
			 	$smtp = Mail::factory('smtp',
   				array ('host' => $host,
     				'port' => $port,
     				'auth' => true,
     				'username' => $username,
     				'password' => $password));
 
 				$mail = $smtp->send($to, $headers, $body);
 
 				if (PEAR::isError($mail)) {
  				 echo("<p>" . $mail->getMessage() . "</p>");
 			 	} 				
				
				//show success message
				print '<h3 align="center"> Refund for  '.$_POST['payable'].' updated!</h3>';
				print '<h4 align="center"><a href="refunds.php">Return to Refunds Page</a></h4>';
			
			} else { //if submitted with errors and not approved/deleted
				showEditPage($_SESSION['username'], $_SESSION['access'],validateRefundChanges());
			}
			
			//if errors exist, show page again & fill in values
		
		} elseif(!$_GET['refund_id']) { //form has not been submitted
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
function validateRefundChanges(){


	if (strlen($_POST['dt_required'])<8){
		$errors[]='Please Enter a Valid Date'; //add better date validation logic	
	}

	if (strlen($_POST['amount'])<1){
		$errors[]='Amount cannot be blank';	
	}

	if (!is_numeric($_POST['amount'])){
		$errors[]='Amount field value must be numeric';	
	}
	
	if (strlen($_POST['payable'])<2){
		$errors[]='Payable names must be at least 2 characters long';	
	}

	if (strlen($_POST['addr_ln_1'])<1){
		$errors[]='Address Line 1 cannot be blank';	
	}

	if (strlen($_POST['city'])<1){
		$errors[]='City cannot be blank';	
	}

	if (strlen($_POST['state'])!=2){
		$errors[]='State must be exactly 2 characters long';	
	}

	if (strlen($_POST['zip'])<5){
		$errors[]='Zip code must be at least 5 characters long';	
	}

	if (strlen($_POST['enc_nbr'])<3){
		$errors[]='Encounter numbers must be at least 3 characters long';	
	}

	if (strlen($_POST['purpose'])<3){
		$errors[]='Purpose cannot be blank';	
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
		
		print "<table class = \"topMenu\"><tr><td><a href=\"index.php\"  class = \"button\" >Home</td><td><a href=\"refunds.php\" class = \"button\" id = \"selected\">Refunds</a></td><td><a href=\"reports.php\"  class = \"button\">Reports</a></td><td><a href=\"mngaccount.php\"  class = \"button\">My Account</a></td>";
	if ($accessLvl == 'S'){
		print '<td><a href="admin.php" class = "button" >Admin</a></td></tr></table>';	
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

	$query = "SELECT NG_enc_id, U.first_name, U.last_name, dt_request, dt_required, payable, addr_ln_1, addr_ln_2, city, state, zip, purpose, amount, status, comments FROM refund AS R INNER JOIN users AS U ON R.created_by= U.user_id WHERE refund_id = '{$_GET['refund_id']}'";
	$result = mysqli_query($db,$query); 
	$row = mysqli_fetch_array($result);

	print <<<EDITUSERPAGE
<h2 align="center">Edit Refund</h2>
<a href="refunds.php">Back to Refunds</a>
		<form method="POST" action="{$_SERVER['PHP_SELF']}" name="update_refund">
      <table style="width: 100%" border="1">
        <tbody>
          <tr>
            <td>Date Required</td>
            <td><input name="dt_required" type="text" value ="{$row['dt_required']}"><br>
            </td>
          </tr>
          <tr>
          	<td>Amount</td>
          	<td><input maxlength="50" name="amount" type="text" value ="{$row['amount']}"><br />
          </tr>
          <tr>
            <td>Check Payable To:</td>
            <td><input name="payable" type="text" value="{$row['payable']}">
            </td>
          </tr>
          <tr>
            <td>Address Line 1</td>
            <td><input name="addr_ln_1" type="text" value="{$row['addr_ln_1']}">
            </td>
          </tr>
          <tr>
            <td>Address Line 2</td>
            <td><input name="addr_ln_2" type="text" value="{$row['addr_ln_2']}">
            </td>
          </tr>
          <tr>
            <td>City</td>
            <td><input  name="city" type="text" value="{$row['city']}">
            </td>
          <tr>
            <td>State</td>
            <td><input maxlength="2" name="state" type="text" value="{$row['state']}">
            </td>
          </tr>
          <tr>
            <td>Zip</td>
            <td><input  maxlength="10" name="zip" type="text" value="{$row['zip']}">
            </td>
          </tr>
          <tr>
            <td>Encounter Number</td>
            <td><input name="enc_nbr" type="text" value="{$row['NG_enc_id']}">
            </td>
          </tr>
          <tr>
            <td>Purpose</td>
            <td><input name="purpose" type="text" value="{$row['purpose']}">
            </td>
          </tr>
          <tr>
            <td>Comments</td>
            <td><input name="comments" type="text" value="{$row['comments']}">
            </td>
          </tr>
        </tbody>
      </table>
      <input type="hidden" name="_edit_submit" value="1" />
      <input type="hidden" name="refund_id" value = "{$_GET['refund_id']}">
      <button formmethod="post" formaction="{$_SERVER['PHP_SELF']}" value="submit" name="Submit">Update Refund</button></form>
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

	$query = "SELECT NG_enc_id, U.first_name, U.last_name, dt_request,amount, status,refund_id, payable FROM refund AS R INNER JOIN users AS U ON R.created_by = U.user_id WHERE status !='deleted' ORDER BY dt_request,U.last_name,status";
	$result = mysqli_query($db,$query); 
	print '<br /><br /><div align = "center">';
	print '<table border="1" cellpadding = "3"><tr><td><b>Encounter Number</b></td><td><b>Date Requested</b></td><td><b>Requested By</b></td><td><b>Payable To</b></td><td><b>Amount</b></td><td><b>Status</b></td><td><b>Actions</b></td>';	
	while ($row = mysqli_fetch_array($result)){
		print '<tr><td>'.$row['NG_enc_id'].'</td><td>'.$row['dt_request'].'</td><td>'.$row['first_name'].' '.$row['last_name'].'</td><td>'.$row['payable'].'</td>';
		print '<td>'.$row['amount'].'</td><td>'.$row['status'].'</td>';
		print '<td><a href="'.$_SERVER['PHP_SELF'].'?refund_id='.$row['refund_id'].'&action=edit">Edit</a>&nbsp;&nbsp;<a href="'.$_SERVER['PHP_SELF'].'?refund_id='.$row['refund_id'].'&action=delete">Void</a>';
		if($accessLvl == 'S' OR $accessLvl == 'A'){
			print '&nbsp;&nbsp<a href="'.$_SERVER['PHP_SELF'].'?refund_id='.$row['refund_id'].'&action=approve">Approve</a></td></tr>';
		} else {
			print	'</td></tr>';
		}
		
	}	
	
	print '</table></div>';
	print '<h3 align="center"><a href="addrefund.php">New Refund Request</a></h3>';
	
	showFooter();

}



?>