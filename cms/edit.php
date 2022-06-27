<?php
	function exception_handler($e) {
		http_response_code(500);
		echo $e->getMessage();
		exit(1);
	}
	set_exception_handler('exception_handler');

	define("EDITABLE_EXTENSIONS", ["gmi", "md"]);

	// patch up PATH_INFO in case it is unset
	if(!array_key_exists('PATH_INFO', $_SERVER) || $_SERVER['PATH_INFO'] == "") {
		$_SERVER['PATH_INFO'] = "/";
	}
	if(substr($_SERVER['PATH_INFO'],-1,1) === "/") {
		$_SERVER['PATH_INFO'] .= "index.md";
	}
?>
<?php function render_file($filename, $base) { ?>
	<li class="file">
		<?php $extension = strtolower(substr(strrchr($filename, '.'), 1)); ?>
		<?php if(in_array($extension, EDITABLE_EXTENSIONS)): ?>
			<a href="<?php echo "/cms/edit.php".$base.$filename ?>" class="link--editable"><?php echo $filename ?></a>
		<?php else: ?>
			<a href="<?php echo $base.$filename ?>" target="_blank" class="link--unknown"><?php echo $filename ?></a>
		<?php endif; ?>
		<div class="actions">
			<button title="Insert Link" data-path="<?php echo $base.$filename ?>" onclick="
				const el = document.getElementById('content');
				let path = this.dataset.path;
				if(path.endsWith('.gmi')) path = path.substring(0, path.lastIndexOf('.gmi'));
				if(path.endsWith('.md')) path = path.substring(0, path.lastIndexOf('.md'));
				const [start, end] = [el.selectionStart, el.selectionEnd];
					el.setRangeText(`\n=> ${path} `, start, end, 'end');
				document.getElementById('panel_files').classList.toggle('hidden');
				document.getElementById('panel_editor').classList.toggle('hidden');
				el.focus();
				el.dispatchEvent(new Event('input'));
			">🔗</button>
			<button title="Delete" data-path="<?php echo $base.$filename ?>" onclick="if(confirm('Are you sure you want to delete this file?')) { deleteFile(this.dataset.path).then(() => {this.parentElement.parentElement.remove(); }); }" style="color: red;">❌</button>
		</div>
	</li>
<?php } ?>
<?php function render_directory($filename, $base, $depth, $children) { ?>
	<?php $_key = $depth == 0 ? '' : $filename."/"; ?>
	<li class="directory">
		<details data-path="<?php echo $base.$_key ?>" <?php echo $depth == 0 ? 'open' : '' ?>>
			<summary>
				<span><?php echo $filename ?></span><div class="actions">
				<button title="New Page" data-path="<?php echo $base.$_key ?>" onclick="newFile(this.dataset.path);">+ 📄</button>
				<button title="New Folder" data-path="<?php echo $base.$_key ?>" onclick="newDir(this.dataset.path).then((el) => { this.parentElement.parentElement.parentElement.querySelector('ol').appendChild(el); });">+ 📁</button>
				<button title="Upload" data-path="<?php echo $base.$_key ?>" onclick="uploadContextPath = this.dataset.path; document.getElementById('upload').click();">Upload</button>
				<?php if($depth > 0): ?>
					<button title="Delete" data-path="<?php echo $base.$filename ?>" onclick="if(confirm('Are you sure you want to delete this directory?')) { deleteFile(this.dataset.path).then(() => {this.parentElement.parentElement.parentElement.remove(); }); }" style="color: red;">❌</button>
				<?php endif; ?>
			</div></summary>
			<?php render_filetree($children, $base.$_key, $depth + 1) ?>
		</details>
	</li>
<?php } ?>
<?php function render_filetree($tree, $base, $depth) { ?>
	<?php asort($tree); ?>
	<ol>
	<?php foreach ($tree as $key => $value): ?>
		<?php if(is_array($value)): ?>
			<?php render_directory($key, $base, $depth, $value); ?>
		<?php else: ?>
			<?php render_file($value, $base); ?>
		<?php endif; ?>
	<?php endforeach; ?>
	</ol>
<?php } ?>
<?php
	function delTree($dir) {
		$files = array_diff(scandir($dir), array('.','..'));
		foreach ($files as $file) {
			(is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
		}
		return rmdir($dir);
	}

	if($_SERVER['REQUEST_METHOD'] === 'PUT') {
		$_dest = fopen("..".$_SERVER['PATH_INFO'], "w");
		if(!$_dest) {
			http_response_code(500);
			header('Content-Type: text/plain');
			echo 'Could not write file. Check file permissions.';
			exit(1);
		}
		$_src = fopen("php://input", "r");
		$written = stream_copy_to_stream($_src, $_dest);
		fclose($_dest);
		fclose($_src);
		http_response_code(204);
		exit(0);

	} elseif($_SERVER['REQUEST_METHOD'] === 'POST') {

		define("RESIZABLE_IMAGE_EXTENSIONS", ["png", "avif", "bmp", "jpg", "jpeg", "gif", "webp"]);

		$public_path = null;
		$new_basename = str_replace(" ", "_", basename($_SERVER['PATH_INFO']));
		$extension = strtolower(pathinfo($_SERVER['PATH_INFO'], PATHINFO_EXTENSION));
		if(getenv('IMG_RESIZE_ENABLE') && in_array($extension, RESIZABLE_IMAGE_EXTENSIONS)) {
			$max_width = 800;
			if(getenv('IMG__RESIZE_MAX_WIDTH')) {
				$max_width = intval(getenv('IMG_RESIZE_MAX_WIDTH'));
			}
			$dest_format = getenv('IMG_RESIZE_FORMAT') ?: "source";
			if($dest_format == "source") {
				$dest_format = $extension;
			}
			$img = imagecreatefromstring(file_get_contents("php://input"));

			if(imagesx($img) > $max_width) {
				$img = imagescale($img, $max_width);
			}

			// replace extension with new format
			$public_path = dirname($_SERVER['PATH_INFO'])."/".basename($new_basename, ".".$extension).".".$dest_format;

			$_dest = fopen("..".$public_path, "x");
			if(!$_dest) {
				http_response_code(400);
				header('Content-Type: text/plain');
				echo 'File already exists in this directory. Delete it to overwrite.';
				exit(1);
			}
			switch($dest_format) {
				case "png":
					imagepng($img, $_dest, intval(getenv('IMG_RESIZE_PNG_COMPRESSION') ?: -1));
					break;
				case "jpg":
				case "jpeg":
					imagejpeg($img, $_dest, intval(getenv('IMG_RESIZE_JPEG_QUALITY') ?: -1));
					break;
				case "webp":
					imagewebp($img, $_dest, intval(getenv('IMG_RESIZE_WEBP_QUALITY') ?: -1));
					break;
				case "avif":
					imageavif($img, $_dest, intval(getenv('IMG_RESIZE_AVIF_QUALITY') ?: -1), intval(getenv('IMG_RESIZE_AVIF_SPEED') ?: -1));
					break;
				case "gif":
					imagegif($img, $_dest);
					break;
				case "bmp":
					imagewbmp($img, $_dest);
					break;
				default:
					http_response_code(400);
					header('Content-Type: text/plain');
					echo "Unknown image format: ".$dest_format;
					exit(1);
			}
			fclose($_dest);
		} else {
			$public_path = dirname($_SERVER['PATH_INFO'])."/".$new_basename;
			$_dest = fopen("..".$public_path, "x");
			if(!$_dest) {
				http_response_code(400);
				header('Content-Type: text/plain');
				echo 'File already exists in this directory. Delete it to overwrite.';
				exit(1);
			}
			$_src = fopen("php://input", "r");
			stream_copy_to_stream($_src, $_dest);
			fclose($_dest);
			fclose($_src);
		}

		$base = dirname($public_path);
		if(substr($base,-1,1) !== "/") {
			$base .= "/";
		}

		http_response_code(200);
		header('Content-Type: text/html');
		render_file(basename($public_path), $base);
		exit(0);

	} elseif($_SERVER['REQUEST_METHOD'] === 'DELETE') {
		if($_SERVER['PATH_INFO'] === "" || $_SERVER['PATH_INFO'] === "." || $_SERVER['PATH_INFO'] === "/") {
			http_response_code(400);
			exit(1);
		}

		if(is_dir("..".$_SERVER['PATH_INFO'])) {
			delTree("..".$_SERVER['PATH_INFO']);
		} else {
			unlink("..".$_SERVER['PATH_INFO']);
		}
		http_response_code(204);
		exit(0);

	} elseif($_SERVER['REQUEST_METHOD'] === 'PATCH') {
		mkdir("..".$_SERVER['PATH_INFO']);
		http_response_code(200);
		header('Content-Type: text/html');
		$dir = dirname($_SERVER['PATH_INFO']);
		render_directory(basename($_SERVER['PATH_INFO']), $dir, count(explode("/", $dir)), array());
		exit(0);
	}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>Lichen</title>
	<style>
		* {
			box-sizing: border-box;
		}
		body {
			height: 100vh;
			margin: 0;
			padding: 0;
			font-family: sans-serif;
		}
		.container {
			height: 100%;
			display: flex;
			flex-direction: row;
		}
		.panel {
			display: flex;
			flex-direction: column;
			border-right: 1px solid gray;
			min-width: 60ch;
			position: relative;
		}
		.panel .controls {
			border-top: 1px solid gray;
			padding: 0.5rem;
			display: flex;
			gap: 0.5rem;
			flex-direction: row;
		}
		textarea {
			min-width: 100%;
			max-width: 100%;
			resize: horizontal;
			margin: 0;
			padding: 1rem;
			outline: none;
			border: none;
			font-size: 1rem;
			font-family: monospace;
		}
		iframe {
			flex-grow: 1;
			border: none;
			outline: none;
			height: 100%;
		}

		#save {
			background: #77d763;
			color: white;
			text-shadow: 0 1px 0 #2fa62f;
			border-radius: 3px;
			outline: none;
			border: 1px solid #2fa62f;
		}
		#save:disabled {
			opacity: 0.5;
		}

		#panel_files {
			width: 60ch;
		}
		
		#help {
			font-size: 0.8rem;
			font-family: monospace;
			border-top: 1px solid gray;
			display: flex;
			flex-direction: row;
			flex-wrap: wrap;
			gap: 1ch 2ch;
			padding: 1ch;
		}
		#files {
			overflow-y: auto;
		}
		#files ol {
			list-style: none;
			border-left: 1px solid lightgray;
			padding-left: 1rem;
			font-family: monospace;
			user-select: none;
		}
		#files ol:first-child {
			border: none;
			padding-right: 1rem;
		}
		#files a, #files summary > span {
			flex-grow: 1;
			display: inline-block;
			padding: 0.25rem 0.5rem;
			padding-left: 0;

			overflow: hidden;
			white-space: nowrap;
			text-overflow: ellipsis;
		}
		#files details {
			width: 100%;
		}
		#files li.file:hover, #files li.directory summary:hover {
			background: #f0f0f0;
		}
		#files a {
			display: list-item;
			text-decoration: none;
			color: inherit;
		}
		#files li {
			border-bottom: 1px solid white;
		}
		#files li.file, #files li.directory summary {
			display: flex;
			flex-direction: row;

		}
		#files li .actions {
			display: flex;
			visibility: hidden;
		}
		#files li.file:hover .actions, #files li.directory summary:hover .actions {
			visibility: visible;
		}
		#files li button {
			background: transparent;
			border: none;
			outline: none;
			padding: 0.25rem 0.50rem;
			cursor: pointer;
			border-left: 1px solid white;
		}

		#files summary {
			cursor: pointer;
		}
		#files summary::marker {
			display: none;
		}
		#files details > summary::before {
			content: '📁 ';
			padding: 0.25rem 0.50rem 0.25rem 0;
		}
		#files details[open] > summary::before {
			content:  '📂 ';
		}
		.link--editable::before {
			content: '📄 ';
		}
		.link--unknown::before {
			content: '📦 ';
		}

		.panel .overlay {
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: #00000080;
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			padding: 1rem;
			gap: 0.5rem;
			color: white;
		}

		@keyframes throb {
			0%, 100% { opacity: 0; }
			50% { opacity: 1; }
		}
		.throb {
			animation: throb 1s infinite;
		}

		code {
			background: lightgray;
			padding: 2px;
			border-radius: 2px;
		}
		.hidden {
			display: none !important;
		}

	</style>
	<script>
		'use strict';
		
		const REQ_PATH = '<?php echo $_SERVER['PATH_INFO']; ?>';
		
		function debounce(callback, delay) {
			let id = null;
			return (...args) => {
				window.clearTimeout(id);
				id = window.setTimeout(() => {
					callback.apply(null, args);
				}, delay);
			 };
		}
		
		let contentModified = false;
		let scrollX = 0;
		let scrollY = 0;
		async function applyContent() {
			const input = document.getElementById('content');
			const preview = document.getElementById('preview');
			const save = document.getElementById('save');
			
			let contentType = "text/gemini"
			
			save.disabled = !contentModified;
			if(contentModified) {
				save.innerText = 'Save';
			}
			try {
				scrollY = preview.contentWindow.scrollY;
				scrollX = preview.contentWindow.scrollX;
			} catch(e) {
				// cross-origin content doesn't allow scroll access
				// (user may have clicked a link)
			}
			
			if (REQ_PATH.indexOf(".md") !== -1) {
				contentType == "text/markdown"
			}
			
			const res = await fetch('/cms/render.php' + REQ_PATH, {
				method: 'POST',
				headers: {
					'Content-Type': contentType,
				},
				body: input.value,
			});
			if(res.status != 200) {
				alert('There was an error rendering the page.');
				const body = await res.text();
				throw new Error(body);
			}

			preview.addEventListener('load', function() {
				preview.contentWindow.scrollTo({top: scrollY, left: scrollX, behavior: 'instant'});
			}, {once: true});
			preview.srcdoc = await res.text();
		}
		
		window.addEventListener('DOMContentLoaded', applyContent, {once: true});
		window.addEventListener('DOMContentLoaded', function() {
			const onContentInput = debounce(applyContent, 1000);
			const input = document.getElementById('content');
			input.focus();
			input.addEventListener('input', onContentInput);
			input.addEventListener('input', function() {
				contentModified = true;
			});
			
			window.addEventListener('beforeunload', function(event) {
				if(!contentModified) return;
				event.preventDefault();
				return 'Unsaved changes will be lost.';
			});
			
			const preview = document.getElementById('preview');
			preview.addEventListener('load', function(event) {
				// TODO: handle navigation
				// console.log(event, event.target.contentWindow.location);
			});
		}, {once: true});
		

		// --- API --- //

		async function saveContent() {
			const input = document.getElementById('content');
			const button = document.getElementById('save');
			let contentType = "text/gemini";
			button.disabled = true;
			button.innerText = 'Saving...';
			
			if (REQ_PATH.indexOf(".md") !== -1) {
				contentType == "text/markdown"
			}

			const res = await fetch('/cms/edit.php' + REQ_PATH, {
				method: 'PUT',
				headers: {
					'Content-Type': contentType,
				},
				body: input.value,
			});
			if(res.status != 204) {
				button.disabled = false;
				button.innerText = 'Save';
				const body = await res.text();
				alert('There was an error that prevented the file from being saved.');
				throw new Error(body);
			} else {
				button.disabled = true;
				button.innerText = 'Saved';
				contentModified = false;
			}
		}

		async function deleteFile(path) {
			const res = await fetch('/cms/edit.php' + path, {
				method: 'DELETE',
			});
			if(res.status != 204) {
				const body = await res.text();
				alert('There was an error deleting the file.');
				throw new Error(body);
			}
		}

		function newFile(path) {
			let filename = prompt('New page filename:');
			if(!filename) return;
			if(!filename.endsWith('.gmi') && !filename.endsWith('.md')) filename += '.md';
			window.location = '/cms/edit.php' + path + filename;
		}

		async function newDir(path) {
			let filename = prompt('New folder name:');
			if(!filename) return;
			const res = await fetch('/cms/edit.php' + path + filename, {
				method: 'PATCH',
			});
			if(res.status != 200) {
				const body = await res.text();
				alert('There was an error creating the folder.');
				throw new Error(body);
			}
			const newSrc = await res.text();
			const newNode = new DOMParser().parseFromString(newSrc, 'text/html').body.firstElementChild;
			return newNode;
		}

		let uploadContextPath;
		async function handleUpload(event) {
			const overlay = document.getElementById('upload-overlay');
			overlay.classList.remove('hidden');
			try {
				const res = await fetch('/cms/edit.php' + uploadContextPath + event.target.files[0].name, {
					method: 'POST',
					body: event.target.files[0],
				});

				if(res.status != 200) {
					const body = await res.text();
					alert('There was an error uploading the file.');
					throw new Error(body);
				}
				const newSrc = await res.text();
				const newNode = new DOMParser().parseFromString(newSrc, 'text/html').body.firstElementChild;
				document.querySelector(`details[data-path="${uploadContextPath}"] > ol`).appendChild(newNode).scrollIntoView();
			} catch(e) {

			} finally {
				overlay.classList.add('hidden');
			}
		}
	</script>
</head>
<body>
	<div class="container">
		<div class="panel" id="panel_editor">
			<textarea id="content" autocomplete="off" spellcheck="false" style="flex-grow: 1;"><?php
				if(file_exists("..".$_SERVER['PATH_INFO'])) {
					echo file_get_contents("..".$_SERVER['PATH_INFO']);
				}
			?></textarea>
			<div id="help" class="hidden">
				<code># heading<br>## subhead<br>### sub-subhead</code>
				<code>* bulleted<br>* list<br>* items</code>
				<code>=> https://abc.xyz external link<br>=> /page.gmi internal link<br>=> /image.jpg image alt</code>
				<code>```<br>preformatted</br>```</code><br>
				<div>
					<code>> blockquote</code>
					<div>&nbsp;</div>
					<a href="https://gemini.circumlunar.space/docs/cheatsheet.gmi" target="_blank">Cheatsheet</a>
				</div>
			</div>

			<div class="controls">
				<button id="toggle-files" onclick="document.getElementById('panel_files').classList.toggle('hidden'); document.getElementById('panel_editor').classList.toggle('hidden');">Files</button>
				<button id="toggle-cheatsheet" onclick="document.getElementById('help').classList.toggle('hidden');">Help</button>
				<span style="flex-grow: 1;"></span>
				<button id="save" disabled onclick="saveContent();">Save</button>
			</div>
		</div>

		<div class="panel hidden" id="panel_files">
			<nav id="files" style="flex-grow: 1;">
				<?php
					$directory = new RecursiveDirectoryIterator('../');
					$filter = new RecursiveCallbackFilterIterator($directory, function ($current, $key, $iterator) {
						if ($current->getFilename()[0] === '.') { // skip hidden files and directories.
							return FALSE;
						} elseif ($key === '../cms') { // skip `cms` directory
							return FALSE;
						} elseif ($key === '../theme') { // skip `theme` directory
							return FALSE;
						}
						return TRUE;
					});

					$iterator = new RecursiveIteratorIterator($filter, RecursiveIteratorIterator::SELF_FIRST);

					$fileTree = array();
					foreach ($iterator as $fileInfo) {
						$path = $fileInfo->isDir()
							? array($fileInfo->getFilename() => array())
							: array($fileInfo->getFilename());

						for ($depth = $iterator->getDepth() - 1; $depth >= 0; $depth--) {
							$path = array($iterator->getSubIterator($depth)->current()->getFilename() => $path);
						}
						$fileTree = array_merge_recursive($fileTree, $path);
					}
					$fileTree = array(basename(realpath('../')) => $fileTree); // top-level dir
				?>
				<?php render_filetree($fileTree, "/", 0) ?>
			</nav>

			<div class="controls">
				<button id="toggle-editor" onclick="document.getElementById('panel_files').classList.toggle('hidden'); document.getElementById('panel_editor').classList.toggle('hidden');">Editor</button>
				<span style="flex-grow: 1;"></span>
			</div>

			<div class="overlay hidden" id="upload-overlay">
				<p>Uploading<span class="throb">…</span></p>
			</div>
		</div>
		
		<iframe id="preview"></iframe>
	</div>
	<input type="file" id="upload" class="hidden" onchange="handleUpload(event);">
</body>
</html>
