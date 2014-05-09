<html><head><title>Patient Refund Users</title></head><body></body>


<?php

$dbc = mysqli_connect('localhost','script_access','x22m3y2k','pt_refund') or die('Error connecting to MySQL server.');
$query = "SELECT user_id,first_name,last_name, D.name AS 'department', access_lvl FROM users AS U INNER JOIN departments AS D ON U.dept_id = D.dept_id";
$result = mysqli_query($dbc,$query) or die('Error querying database');

echo '<table><tr><td>User ID</td><td>First Name</td><td>Last Name</td><td>Department</td><td>Access Level</td></tr>';

while ($row = mysqli_fetch_array($result)){
	echo '<tr><td>'.$row['user_id'].'</td><td>'.$row['first_name'].'</td><td>'.$row['last_name'].'</td><td>'.$row['department'].'</td><td>'.$row['access_lvl'].'</td><td>';



}


?>