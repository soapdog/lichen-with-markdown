<?php

require_once "vendor/php-markdown-lib-9.1/Michelf/MarkdownExtra.inc.php";

use Michelf\MarkdownExtra;

function markdown_to_html($file, $generate_headline_ids = true) {

	$title = '';
	$body = '';
	$headings = null;
	$source = "";
	
	while(!feof($file)) {
		$source = $source . fgets($file);
	}
	
	$body = MarkdownExtra::defaultTransform($source);
	preg_match_all( '|<h[^>]+>(.*)</h[^>]+>|iU', $body, $headings );

	return array("body" => $body, "title" => $headings[0]);
}
?>
