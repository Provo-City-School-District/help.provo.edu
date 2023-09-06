function sanitizeInput(input) {
    // Replace potentially dangerous characters with their HTML entities
    return input.replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

//login form validation
function validateForm() {
    var username = document.forms["loginForm"]["username"].value;
    var password = document.forms["loginForm"]["password"].value;

    // Sanitize the username and password
    var sanitizedUsername = sanitizeInput(username);
    var sanitizedPassword = sanitizeInput(password);

    // Update the form fields with the sanitized values
    document.forms["loginForm"]["username"].value = sanitizedUsername;
    document.forms["loginForm"]["password"].value = sanitizedPassword;

    // Validate the username
    if (username === "") {
        alert("Username must be filled out");
        return false;
    }

    // Validate the password
    if (password === "") {
        alert("Password must be filled out");
        return false;
    }
    return true; // Form is valid
}