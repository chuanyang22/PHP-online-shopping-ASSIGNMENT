<?php
// upload_photo.php (Cropping Update)
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error_msg = "";
$success_msg = "";

// Process the cropped image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crop_data_base64'])) {
    $cropped_image_base64 = $_POST['crop_data_base64'];

    if (!empty($cropped_image_base64)) {
        // 1. Clean and decode the base64 string
        $image_data_part = substr($cropped_image_base64, strpos($cropped_image_base64, ",") + 1);
        $decoded_image = base64_decode($image_data_part);

        if ($decoded_image) {
            // 2. Create a unique filename 
            $filename = "profile_" . $_SESSION['user_id'] . "_" . time() . ".jpg";
            $filepath = 'uploads/' . $filename;

            // 3. Save the decoded image
            if (file_put_contents($filepath, $decoded_image)) {
                
                // 4. Fetch the old profile photo to delete it
                $stmt = $pdo->prepare("SELECT profile_photo FROM member WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();

                // 5. Delete the old photo if it isn't the default one
                if ($user['profile_photo'] && $user['profile_photo'] != 'default_avatar.jpg') {
                    if (file_exists('uploads/' . $user['profile_photo'])) {
                        unlink('uploads/' . $user['profile_photo']);
                    }
                }

                // 6. Update the database with the new filename
                $stmt = $pdo->prepare("UPDATE member SET profile_photo = ? WHERE id = ?");
                $stmt->execute([$filename, $_SESSION['user_id']]);

                // 7. Success! Redirect back to profile
                header("Location: profile.php?photo_updated=1");
                exit();
            } else {
                $error_msg = "Server error: Failed to save the image file.";
            }
        } else {
            $error_msg = "Error: Invalid image data received.";
        }
    } else {
        $error_msg = "Error: Please upload and crop an image first.";
    }
}

// Fetch current profile photo for display
$stmt = $pdo->prepare("SELECT profile_photo FROM member WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$profile_photo = $user['profile_photo'] ? $user['profile_photo'] : 'default_avatar.jpg';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/mainstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile Photo - Online Accessory Store</title>
    <style>
        .preview-container { text-align: center; margin-bottom: 20px; }
        .avatar { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #ee4d2d; }
        .cropper-container { text-align: center; margin-top: 20px; display: none; }
        .img-to-crop { max-width: 100%; max-height: 400px; display: block; margin: 0 auto; }
        #save-crop { display: none; margin-top: 15px; }
    </style>
</head>
<body class="auth-body">
    <div class="auth-card">
        <div class="auth-title">Update Profile Photo</div>
        <p style="text-align: center; color: #555; margin-bottom: 20px; font-size: 14px;">
            Please select an image to upload and crop. This will be visible on your profile page.
        </p>

        <?php if (!empty($error_msg)): ?><div class="auth-error"><?= $error_msg ?></div><?php endif; ?>

        <div class="preview-container">
            <img src="uploads/<?= htmlspecialchars($profile_photo) ?>" class="avatar" alt="Current Profile Photo">
        </div>

        <input type="file" name="raw_photo" id="photo_input" accept="image/*" class="auth-input" style="background: white;">

        <div class="cropper-container">
            <img id="image_to_crop_element" class="img-to-crop">
            <button type="button" class="auth-btn" id="save-crop">SAVE CROP</button>
        </div>

        <form method="POST" action="upload_photo.php" id="final_crop_form">
            <input type="hidden" name="crop_data_base64" id="crop_data_base64_field">
        </form>

        <div class="auth-footer"><a href="profile.php">Back to Profile</a></div>
    </div>

    <script>
        const photoInput = document.getElementById('photo_input');
        const cropperContainer = document.querySelector('.cropper-container');
        const imageToCropElement = document.getElementById('image_to_crop_element');
        const saveCropButton = document.getElementById('save-crop');
        const cropDataBase64Field = document.getElementById('crop_data_base64_field');
        const finalCropForm = document.getElementById('final_crop_form');
        let cropperInstance;

        photoInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                // Check if it's an image
                if (!file.type.startsWith('image/')) {
                    alert('Please upload a valid image file (JPG, PNG, GIF).');
                    photoInput.value = ''; 
                    return;
                }

                // Load the image into the cropper
                const reader = new FileReader();
                reader.onload = (event) => {
                    
                    // NEW/FIXED: Update the LITTLE AVATAR at the top too!
                    const mainAvatar = document.querySelector('.preview-container .avatar');
                    if (mainAvatar) mainAvatar.src = event.target.result;
                    
                    // Update the large image that gets cropped
                    imageToCropElement.src = event.target.result;
                    cropperContainer.style.display = 'block';
                    saveCropButton.style.display = 'inline-block';

                    // Destroy old cropper instance if it exists
                    if (cropperInstance) {
                        cropperInstance.destroy();
                    }

                    // Initialize new cropper with FIXED SETTINGS
                    cropperInstance = new Cropper(imageToCropElement, {
                        aspectRatio: 1,      // Forces a square crop
                        viewMode: 1,         // Keeps crop box inside boundaries
                        dragMode: 'move',    // Enables image dragging within the box
                        autoCropArea: 0.8,   // FIXED: Initial crop box is 80% (not 100%), so you can see it!
                        cropBoxMovable: true,
                        cropBoxResizable: true
                    });
                };
                reader.readAsDataURL(file);
            }
        });

        // Handle the Save Crop button click
        saveCropButton.addEventListener('click', () => {
            if (cropperInstance) {
                // Get the cropped canvas data at a decent size
                const canvas = cropperInstance.getCroppedCanvas({
                    width: 300,
                    height: 300
                });
                
                // Convert to base64 string
                const base64data = canvas.toDataURL('image/jpeg');
                
                // Put data in the hidden form field and submit
                cropDataBase64Field.value = base64data;
                finalCropForm.submit();
            }
        });
    </script>
</body>
</html>