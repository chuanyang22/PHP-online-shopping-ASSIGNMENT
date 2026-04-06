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
    <link rel="stylesheet" href="css/mainstyle.css?v=<?php echo time(); ?>">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Photo Workshop - Online Accessory Store</title>
</head>
<body class="auth-body">
    <div class="workshop-card">
        <h2 style="color: #333; margin-top: 0; margin-bottom: 10px;">Profile Photo Workshop</h2>
        <p style="color: #666; font-size: 14px; margin-bottom: 25px;">
            Select a new image below. You will be able to crop it perfectly in the next step.
        </p>

        <?php if (!empty($error_msg)): ?><div class="auth-error" style="color: red; margin-bottom: 15px;"><?= $error_msg ?></div><?php endif; ?>

        <div id="initial-view">
            <div class="avatar-preview-wrapper">
                <img src="<?= htmlspecialchars($display_photo) ?>" alt="Current Profile Photo">
            </div>
        </div>

        <div id="cropper-view" style="display: none;">
            <div class="img-to-crop-container">
                <img id="image_to_crop_element" src="" style="max-width: 100%;">
            </div>
        </div>

        <div class="workshop-btn-group">
            <button type="button" class="workshop-btn btn-upload" id="btn-upload" onclick="document.getElementById('photo_input').click();">
                📷 UPLOAD PHOTO
            </button>
            <button type="button" class="workshop-btn btn-save" id="save-crop">
                SAVE PROFILE PHOTO
            </button>
        </div>

        <a href="profile.php" style="color: #3300ff; text-decoration: underline; font-size: 14px; display: inline-block; margin-top: 5px;">Cancel and go back</a>

        <input type="file" id="photo_input" accept="image/*" style="display: none;">
        <form method="POST" action="upload_photo.php" id="final_crop_form" style="display: none;">
            <input type="hidden" name="crop_data_base64" id="crop_data_base64_field">
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var photoInput = document.getElementById('photo_input');
            var initialView = document.getElementById('initial-view');
            var cropperView = document.getElementById('cropper-view');
            var imageToCropElement = document.getElementById('image_to_crop_element');
            
            var btnUpload = document.getElementById('btn-upload');
            var saveCropButton = document.getElementById('save-crop');
            
            var cropDataBase64Field = document.getElementById('crop_data_base64_field');
            var finalCropForm = document.getElementById('final_crop_form');
            var cropperInstance = null;

            // 1. User picks a file
            photoInput.addEventListener('change', function(e) {
                var file = e.target.files[0];
                if (!file) return;

                var reader = new FileReader();
                
                reader.onload = function(event) {
                    // Switch views
                    initialView.style.display = 'none';
                    cropperView.style.display = 'block';
                    
                    // Update buttons
                    btnUpload.innerHTML = '🔄 CHANGE PHOTO';
                    saveCropButton.classList.add('active'); // Turn the save button fully green

                    imageToCropElement.onload = function() {
                        try {
                            if (cropperInstance) { cropperInstance.destroy(); }
                            
                            cropperInstance = new Cropper(imageToCropElement, {
                                aspectRatio: 1,      // Forces perfect square/circle
                                viewMode: 1,         // Restricts box inside image
                                dragMode: 'move',    // Drag the image
                                autoCropArea: 0.9,   // Starts big
                                movable: true,
                                zoomable: true,
                                rotatable: false,
                                scalable: false
                            });
                        } catch (err) {
                            alert("Cropping tool failed to load. Please ensure you are connected to the internet.");
                        }
                    };
                    
                    imageToCropElement.src = event.target.result; 
                };

                reader.onerror = function() {
                    alert("Your browser could not read this file. Please try a different image.");
                };

                reader.readAsDataURL(file);
            });

            // 2. User clicks Save
            saveCropButton.addEventListener('click', function() {
                // If they haven't uploaded an image yet, do nothing
                if (!saveCropButton.classList.contains('active') || !cropperInstance) {
                    return; 
                }

                var canvas = cropperInstance.getCroppedCanvas({
                    width: 400,
                    height: 400
                });
                
                var base64data = canvas.toDataURL('image/jpeg', 0.9);
                cropDataBase64Field.value = base64data;
                finalCropForm.submit();
            });
        });
    </script>
</body>
</html>