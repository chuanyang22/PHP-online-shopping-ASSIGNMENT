$(document).ready(function() {
    // Listen for the register form submission
    $('form[action="register.php"]').on('submit', function(event) {
        let isValid = true;
        
        // Grab the values from the input boxes
        let username = $('input[name="username"]').val().trim();
        let email = $('input[name="email"]').val().trim();
        let password = $('input[name="password"]').val();
        let confirmPassword = $('input[name="confirm_password"]').val();

        // Clear any old Javascript error messages
        $('.js-error').remove(); 

        // Check Username
        if (username.length < 3 || username.length > 20) {
            $('input[name="username"]').after('<span class="js-error" style="color:red; font-size:14px; margin-left:10px;">Must be 3-20 characters.</span>');
            isValid = false;
        }

        // Check Email
        if (email === '') {
            $('input[name="email"]').after('<span class="js-error" style="color:red; font-size:14px; margin-left:10px;">Email is required.</span>');
            isValid = false;
        }

        // Check Password Length
        if (password.length < 8) {
            $('input[name="password"]').after('<span class="js-error" style="color:red; font-size:14px; margin-left:10px;">Must be at least 8 characters.</span>');
            isValid = false;
        }

        // Check Confirm Password Match
        if (password !== confirmPassword || confirmPassword === '') {
            $('input[name="confirm_password"]').after('<span class="js-error" style="color:red; font-size:14px; margin-left:10px;">Passwords do not match.</span>');
            isValid = false;
        }

        // If anything is invalid, STOP the form from going to PHP
        if (!isValid) {
            event.preventDefault(); 
        }
    });
});