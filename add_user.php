<?php

function print_post(){
	$postString = 'POST variables are:  ';
	foreach($_POST as $key=> $value){
		$postString .= $key ."\t". $value ."\n";
	}
	return $postString;
}

print <<<HTML
<html><head><title>POST variable</title></head>
<body><p>
HTML;

print "\n".print_post()."\n";

print <<<HTML
</p></body></html>
HTML;

?>
