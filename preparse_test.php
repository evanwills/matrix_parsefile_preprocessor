<?php
$preparse = file_get_contents('parse-files/public.xml');
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<title>JS Preparse regex test</title>
	</head>
	<body>
		<h1>JS Preparse Regex Test</h1>
		<textarea id="preparse"><?php echo $preparse; ?></textarea>
		<script src="preparseTest.js" type="text/javascript" charset="utf-8"></script>
	</body>
</html>