<?php

require_once "vendor/php-markdown-lib-9.1/Michelf/Markdown.inc.php";

use Michelf\Markdown;

function markdown_to_html($file, $generate_headline_ids = true) {

	$title = '';
	$body = '';
	$headings = null;
	$source = "";
	
	while(!feof($file)) {
		$source = $source . fgets($file);
	}
	
	$body = Markdown::defaultTransform($source);
	preg_match_all( '|<h[^>]+>(.*)</h[^>]+>|iU', $body, $headings );

	return array("body" => $body, "title" => $headings[0]);
}
?>
