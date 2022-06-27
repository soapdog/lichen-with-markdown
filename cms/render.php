<?php
	require "./gemtext.php";
	require "./markdown.php";

	// get the body content
	$_src = null;
	$path = null;
	$ext = null;
	$_content = null;
			
	if($_SERVER['REQUEST_METHOD'] == 'POST') {
		$_src = fopen("php://input", "r");
		$path = $_SERVER['PATH_INFO'];
		$ext = pathinfo($path, PATHINFO_EXTENSION);
	} else if (isset($_SERVER['REDIRECT_URL'])) {
		$path = $_SERVER['REDIRECT_URL'];
		$_src = fopen("..".$path, "r") or die("File not found: ".$path);
		$ext = pathinfo("..".$path, PATHINFO_EXTENSION);
		header("Last-Modified: ".date("r", filemtime("..".$path)));
	} else {
		$path = ".." . $_SERVER['PATH_INFO'];
		$_src = fopen($path, "r") or die("File not found: ".$path);
		$ext = pathinfo($path, PATHINFO_EXTENSION);
		header("Last-Modified: ".date("r", filemtime($path)));
	}
		
	if ($ext == "gmi") {
		$_content = gemtext_to_html($_src);
	} else if ($ext == "md") {
		$_content = markdown_to_html($_src);
	}
	
	// pull out variables for convenience
	$title = $_content['title'];
	$body = $_content['body'];
	
	fclose($_src);
	
	include "../theme/layout.php";
?>
