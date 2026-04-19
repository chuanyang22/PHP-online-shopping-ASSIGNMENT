$(document).ready(function() {
    $('form[action="login.php"]').on('submit', function(event) {
        let isValid = true;
        let email = $('input[name="email"]').val().trim();
        let password = $('input[name="password"]').val();

        $('.js-error').remove();

        if (email === '') {
            $('input[name="email"]').after('<span class="js-error">Please enter your email.</span>');
            isValid = false;
        }

        if (password === '') {
            $('input[name="password"]').after('<span class="js-error">Please enter your password.</span>');
            isValid = false;
        }

        if (!isValid) {
            event.preventDefault();
        }
    });
});