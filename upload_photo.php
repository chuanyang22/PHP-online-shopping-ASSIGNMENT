<?php
// upload_photo.php
session_start();
require_once 'lib/db.php';
require_once 'lib/helpers.php';

auth('Member'); 

$error_msg = "";

// Process the cropped image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crop_data_base64'])) {
    $cropped_image_base64 = $_POST['crop_data_base64'];

    if (!empty($cropped_image_base64)) {
        // Clean and decode base64
        $image_data_part = substr($cropped_image_base64, strpos($cropped_image_base64, ",") + 1);
        $decoded_image = base64_decode($image_data_part);

        if ($decoded_image) {
            $filename = "profile_" . $_SESSION['user_id'] . "_" . time() . ".jpg";
            $filepath = 'uploads/' . $filename;

            if (!file_exists('uploads')) { mkdir('uploads', 0777, true); }

            if (file_put_contents($filepath, $decoded_image)) {
                // Fetch old photo to delete
                $stmt = $pdo->prepare("SELECT profile_photo FROM member WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();

                if ($user['profile_photo'] && $user['profile_photo'] != 'default_avatar.jpg') {
                    if (file_exists('uploads/' . $user['profile_photo'])) {
                        unlink('uploads/' . $user['profile_photo']);
                    }
                }

                $stmt = $pdo->prepare("UPDATE member SET profile_photo = ? WHERE id = ?");
                $stmt->execute([$filename, $_SESSION['user_id']]);

                header("Location: profile.php?photo_updated=1");
                exit();
            } else {
                $error_msg = "Error: Failed to save the image to the server.";
            }
        } else {
            $error_msg = "Error: Invalid image data received.";
        }
    }
}

// Fetch current photo to display in the circle
$stmt = $pdo->prepare("SELECT profile_photo, username FROM member WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Determine which photo to show
if (!empty($user['profile_photo']) && file_exists('uploads/' . $user['profile_photo'])) {
    $display_photo = 'uploads/' . $user['profile_photo'];
} else {
    // Generates a nice default initial image if they don't have a photo yet
    $display_photo = "https://ui-avatars.com/api/?name=" . urlencode($user['username']) . "&size=200&background=random&color=fff";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Photo Workshop</title>

    <!-- Shared site styles (auth-body background, auth-error-box, etc.) -->
    <link rel="stylesheet" href="css/mainstyle.css">

    <!-- Page-specific styles for the photo workshop -->
    <link rel="stylesheet" href="css/upload_photo.css">

    <!-- Cropper.js -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
</head>
<body class="auth-body">

    <div class="workshop-card">

        <h2 class="workshop-title">Profile Photo Workshop</h2>
        <p class="workshop-subtitle">
            Select a new image below. You will be able to crop it perfectly in the next step.
        </p>

        <?php if (!empty($error_msg)): ?>
            <div class="workshop-error"><?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>

        <!-- Step 1: Shows current profile photo -->
        <div id="initial-view">
            <div class="avatar-preview-wrapper">
                <img src="<?= htmlspecialchars($display_photo) ?>" alt="Current Profile Photo">
            </div>
        </div>

        <!-- Step 2: Hidden until a file is selected; shows the cropper -->
        <div id="cropper-view" class="cropper-view">
            <div class="img-to-crop-container">
                <img id="image_to_crop_element" class="cropper-img" src="" alt="Image to crop">
            </div>
        </div>

        <!-- Buttons -->
        <div class="workshop-btn-group">
            <button type="button" class="workshop-btn btn-upload" id="btn-upload"
                    onclick="document.getElementById('photo_input').click();">
                📷 UPLOAD PHOTO
            </button>
            <button type="button" class="workshop-btn btn-save-workshop" id="save-crop">
                ✅ SAVE PROFILE PHOTO
            </button>
        </div>

        <a href="profile.php" class="workshop-cancel-link">Cancel and go back</a>

        <!-- Hidden: file picker & POST form -->
        <input type="file" id="photo_input" accept="image/*" class="hidden-file-input">
        <form method="POST" action="upload_photo.php" id="final_crop_form" class="hidden-form">
            <input type="hidden" name="crop_data_base64" id="crop_data_base64_field">
        </form>

    </div><!-- /.workshop-card -->

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var photoInput        = document.getElementById('photo_input');
            var initialView       = document.getElementById('initial-view');
            var cropperView       = document.getElementById('cropper-view');
            var imageToCropEl     = document.getElementById('image_to_crop_element');
            var btnUpload         = document.getElementById('btn-upload');
            var saveCropButton    = document.getElementById('save-crop');
            var cropDataField     = document.getElementById('crop_data_base64_field');
            var finalCropForm     = document.getElementById('final_crop_form');
            var cropperInstance   = null;

            // Step 1 — User picks a file
            photoInput.addEventListener('change', function (e) {
                var file = e.target.files[0];
                if (!file) return;

                var reader = new FileReader();

                reader.onload = function (event) {
                    // Swap views
                    initialView.style.display = 'none';
                    cropperView.style.display  = 'block';

                    // Enable the save button
                    btnUpload.innerHTML = '🔄 CHANGE PHOTO';
                    saveCropButton.classList.add('active');

                    // Init cropper once the image has loaded
                    imageToCropEl.onload = function () {
                        try {
                            if (cropperInstance) { cropperInstance.destroy(); }

                            cropperInstance = new Cropper(imageToCropEl, {
                                aspectRatio:  1,       // Perfect square → circle
                                viewMode:     1,       // Crop box stays inside image
                                dragMode:    'move',   // Drag image, not box
                                autoCropArea: 0.9,
                                movable:      true,
                                zoomable:     true,
                                rotatable:    false,
                                scalable:     false
                            });
                        } catch (err) {
                            alert('Cropping tool failed to load. Please ensure you are connected to the internet.');
                        }
                    };

                    imageToCropEl.src = event.target.result;
                };

                reader.onerror = function () {
                    alert('Your browser could not read this file. Please try a different image.');
                };

                reader.readAsDataURL(file);
            });

            // Step 2 — User clicks Save
            saveCropButton.addEventListener('click', function () {
                if (!saveCropButton.classList.contains('active') || !cropperInstance) {
                    return; // Nothing loaded yet
                }

                var canvas = cropperInstance.getCroppedCanvas({ width: 400, height: 400 });
                cropDataField.value = canvas.toDataURL('image/jpeg', 0.9);
                finalCropForm.submit();
            });
        });
    </script>

</body>
</html>