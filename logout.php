<?php
session_start();
unset($_SESSION['userid']);

print <<<LOGOUT

<HTML>
<HEAD>
	<TITLE>User Logged Out</title>
</head>
<body>
	<h1 align="center">You have been logged out</h1>
	<h3 align="center"><a href="index.php">Click here to return to the homepage</a></h3>
</body>
</html>
LOGOUT;

?>