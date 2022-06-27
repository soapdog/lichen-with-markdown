<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title><?php echo $title ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="/assets/stylesheet.css">
</head>
<body>
<header>
	<?php if($path !== '/index.gmi'): ?>
	<a href="/" class="back-link">
		<span>&larr; &mdash; Back</span>
	</a>
	<?php endif; ?>
</header>
<main>
<?php echo $body ?>
</main>
<footer>
	<p>Low consumption website &bull; No trackers</p>
	<a class="lichen-plug" href="https://lichen.sensorstation.co/">
		<img src="/assets/lichen-small.svg" width="16" height="16" alt="Lichen" style="margin-bottom: 0.25rem;">
		<p><small>Built with Lichen</small></p>
	</a>
</footer>
</body>
</html>
