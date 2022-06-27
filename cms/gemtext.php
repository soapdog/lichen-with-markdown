<?php

define("IMAGE_EXTENSIONS", ["png", "apng", "avif", "bmp", "ico", "tif", "tiff", "jpg", "jpeg", "gif", "svg", "webp"]);
define("VIDEO_EXTENSIONS", ["mp4", "m4v", "ogv", "mov", "webm", "mkv"]);
define("AUDIO_EXTENSIONS", ["mp3", "aac", "wav", "flac", "ogg"]);

function gemtext_to_html($file, $generate_headline_ids = true) {
	$line_no = 0;
	$preformatted = false;
	$blockquote_open = false;
	$list_open = false;
	$list_index = 1;

	$last_br = -1;

	$title = '';
	$body = '';

	while(!feof($file)) {
		$line_no += 1;
		$line_untrimmed = fgets($file);
		$line = rtrim($line_untrimmed);
		$line_len = strlen($line);
		
		// == PREFORMATTED
		if($line_len >= 3 && substr($line, 0, 3) == "```") {
			$preformatted = !$preformatted;
			if($preformatted) {
				if($line_len > 3) {
					$body .= "<pre alt=\"".htmlspecialchars(substr($line, 3))."\">\n";
				} else {
					$body .= "<pre>\n";
				}
			} else {
				$body .= "</pre>\n";
			}
			continue;
		}

		if($preformatted) {
			$body .= htmlspecialchars($line_untrimmed);
			continue;
		}

		// == LINKS
		if(substr($line,0,2) === "=>") {
			$line = ltrim($line, "=> \t");
			if(strlen($line) == 0) {
				continue;
			}
			$first_space = strpos($line, " ");
			$first_tab = strpos($line, "\t");
			$link_target = "";
			$link_label = "";
			if($first_space === false && $first_tab === false) {
				$link_target = $line;
				$link_label = $line;
			} else {
				if($first_space === false) $first_space = PHP_INT_MAX;
				if($first_tab === false) $first_tab = PHP_INT_MAX;

				$parts = [];
				if($first_space < $first_tab) {
					$parts = explode(" ", $line, 2);
				} else {
					$parts = explode("\t", $line, 2);
				}
				$link_target = $parts[0];
				$link_label = $parts[1];
			}
			$extension = strtolower(pathinfo($link_target, PATHINFO_EXTENSION));
			if(in_array($extension, IMAGE_EXTENSIONS)) {
				$body .= "<img src=\"$link_target\" alt=\"".htmlspecialchars($link_label)."\">\n";
			} elseif(in_array($extension, VIDEO_EXTENSIONS)) {
				$body .= "<video controls preload=\"none\" src=\"$link_target\"></video>";
			} elseif(in_array($extension, AUDIO_EXTENSIONS)) {
				$body .= "<audio controls preload=\"none\" src=\"$link_target\"></audio>";
			} else {
				$body .= "<a href=\"$link_target\" rel=\"noreferrer\">".htmlspecialchars($link_label)."</a>\n";
			}
			continue;
		}

		// == HEADLINES
		$head_level = 0;
		if(substr($line,0,2) === "# ") {
			$head_level = 1;
		} elseif(substr($line,0,3) === "## ") {
			$head_level = 2;
		} elseif(substr($line,0,4) === "### ") {
			$head_level = 3;
		}

		if($head_level > 0) {
			$line = ltrim($line, "# \t");
			if($generate_headline_ids) {
				$body .= "<h".$head_level." id=\"".urlencode($line)."\">".htmlspecialchars($line)."</h".$head_level.">\n";
			} else {
				$body .= "<h".$head_level.">".htmlspecialchars($line)."</h".$head_level.">\n";
			}
			if($title == "") {
				$title = $line;
			}
			$list_index = 1; # reset
			continue;
		}

		// == BLOCKQUOTES
		if(substr($line,0,1) === ">") {
			$body .= "<blockquote>".htmlspecialchars(ltrim($line, "> \t"))."</blockquote>\n";
			continue;
		}


		// == LISTS
		if(substr($line,0,2) === "* ") {
			if(!$list_open) {
				$list_open = true;
				$body .= "<ol start=\"$list_index\">\n";
			}
			$line = ltrim($line, "* ");
			$body .= "<li>".htmlspecialchars($line)."</li>\n";
			$list_index += 1;
			continue;
		} else if($list_open && substr($line,0,1) !== "*") {
			$list_open = false;
			$body .= "</ol>\n";
		}

		// == PARAGRAPHS
		if($line_len > 0) {
			$body .= "<p>".htmlspecialchars($line)."</p>\n";
		} else {
			// == WHITESPACE
			// only insert <br> after 2 newlines
			if($last_br == $line_no - 1) {
				$body .= "<br>\n";
			}
			$last_br = $line_no;
		}
	}

	if($preformatted) {
		$preformatted = false;
		$body .= "</pre>\n";
	}
	if($list_open) {
		$list_open = false;
		$body .= "</ol>\n";
	}
	
	return array("body" => $body, "title" => $title);
}
?>
