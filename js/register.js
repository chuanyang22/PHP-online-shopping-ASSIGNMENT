$(document).ready(function() {
    $('form[action="register.php"]').on('submit', function(event) {
        let isValid = true;
        let username = $('input[name="username"]').val().trim();
        let email = $('input[name="email"]').val().trim();
        let password = $('input[name="password"]').val();
        let confirmPassword = $('input[name="confirm_password"]').val();

        $('.js-error').remove(); 

        if (username.length < 3 || username.length > 20) {
            $('input[name="username"]').after('<span class="js-error">Must be 3-20 characters.</span>');
            isValid = false;
        }

        if (email === '') {
            $('input[name="email"]').after('<span class="js-error">Email is required.</span>');
            isValid = false;
        }

        if (password.length < 8) {
            $('input[name="password"]').after('<span class="js-error">Must be at least 8 characters.</span>');
            isValid = false;
        }

        if (password !== confirmPassword || confirmPassword === '') {
            $('input[name="confirm_password"]').after('<span class="js-error">Passwords do not match.</span>');
            isValid = false;
        }

        if (!isValid) {
            event.preventDefault(); 
        }
    });
});