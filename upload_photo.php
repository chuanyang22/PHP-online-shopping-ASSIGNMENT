<?php
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success_msg = '';

// Handling the file upload
if (isset($_POST['upload_photo'])) {
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['photo']['tmp_name'];
        $file_name = $_FILES['photo']['name'];
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($file_tmp);

        if (in_array($file_type, $allowed_types)) {
            $ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
            
            $destination = 'uploads/' . $new_filename;
            
            if (move_uploaded_file($file_tmp, $destination)) {
                $stmt = $pdo->prepare("UPDATE member SET profile_photo = ? WHERE id = ?");
                $stmt->execute([$new_filename, $user_id]);
                
                $success_msg = "Profile photo updated successfully!";
            } else {
                $errors['photo'] = "Failed to move the uploaded file.";
            }
        } else {
            $errors['photo'] = "Only JPG, PNG, and GIF files are allowed.";
        }
    } else {
        $errors['photo'] = "Please select a valid image file.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Photo</title>
</head>
<body>
    <div style="text-align: center; margin-top: 20px;">
        <h2>Upload New Photo</h2>
        <a href="profile.php">Back to Profile Hub</a>
        <br><br>

        <?php if (!empty($success_msg)): ?>
            <p style='color:green;'><?= $success_msg ?></p>
        <?php endif; ?>

        <form method="POST" action="upload_photo.php" enctype="multipart/form-data">
            
            <div id="photo-preview-area" style="margin-bottom: 20px;">
                <img id="photo-preview" src="https://via.placeholder.com/150" alt="New Photo Preview" width="150" style="border-radius: 50%; display:none;">
            </div>

            <input type="file" name="photo" id="photo-input" accept="image/png, image/jpeg, image/gif">
            <?php display_error($errors, 'photo'); ?>
            <br><br>
            <button type="submit" name="upload_photo">Upload Photo</button>
        </form>
    </div>

    <script>
        document.getElementById('photo-input').onchange = function (evt) {
            const tgt = evt.target || window.event.srcElement,
                  files = tgt.files;
            
            if (FileReader && files && files.length) {
                const fr = new FileReader();
                fr.onload = function () {
                    document.getElementById('photo-preview').src = fr.result;
                    document.getElementById('photo-preview').style.display = 'block'; // Show the image
                    document.getElementById('photo-preview-area').style.display = 'block'; // Make area visible
                }
                fr.readAsDataURL(files[0]);
            } else {
                // Not supported
            }
        }
    </script>

</body>
</html>