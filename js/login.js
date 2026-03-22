$(document).ready(function() {
    // Listen for the login form submission
    $('form[action="login.php"]').on('submit', function(event) {
        let isValid = true;
        
        // Grab the values from the input boxes
        let email = $('input[name="email"]').val().trim();
        let password = $('input[name="password"]').val();

        // Clear any old Javascript error messages
        $('.js-error').remove();

        // Check Email
        if (email === '') {
            $('input[name="email"]').after('<span class="js-error" style="color:red; font-size:14px; margin-left:10px;">Please enter your email.</span>');
            isValid = false;
        }

        // Check Password
        if (password === '') {
            $('input[name="password"]').after('<span class="js-error" style="color:red; font-size:14px; margin-left:10px;">Please enter your password.</span>');
            isValid = false;
        }

        // If anything is invalid, STOP the form from going to PHP
        if (!isValid) {
            event.preventDefault();
        }
    });
});