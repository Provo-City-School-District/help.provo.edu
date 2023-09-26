function sanitizeInput(input) {
  // Replace potentially dangerous characters with their HTML entities
  return input.replace(/</g, "&lt;").replace(/>/g, "&gt;");
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
// Define the table variable
var table;

// Initialize the data table library on the table with the class data-table
$(document).ready(function () {
  table = $(".data-table").DataTable({
    paging: true, // Enable pagination
    pageLength: 10, // Set the number of rows per page
    ordering: true, // Enable sorting
    order: [[0, "asc"]], // Set the default sort order
  });

  // Change default sorting on /recent_tickets.php
  if (window.location.pathname == "/controllers/tickets/recent_tickets.php") {
    var column = table.column(8); // Get the column object for the 9th column (index 8)
    column.order("desc").draw(); // Set the sorting order to ascending and redraw the table
  }
    var column = table.column(8); // Get the column object for the 9th column (index 8)
    column.order("desc").draw(); // Set the sorting order to ascending and redraw the table
  }
});

//initialize tinyMCE for for textarea with class tinyMCEtextarea
tinymce.init({
  selector: ".tinyMCEtextarea",
  menubar: false,
});

// display/hide new note form
var newNoteButton = document.getElementById("new-note-button");
var newNoteForm = document.getElementById("new-note-form");

newNoteButton.addEventListener("click", function () {
  if (newNoteForm.style.display === "none") {
    newNoteForm.style.display = "block";
  } else {
    newNoteForm.style.display = "none";
  }
});

// Toggle description to make it editable
var descriptionDiv = document.querySelector(".ticket-description");
var editDescriptionButton = document.getElementById("edit-description-button");
var editDescriptionForm = document.getElementById("edit-description-form");

editDescriptionButton.addEventListener("click", function () {
  if (descriptionDiv.style.display === "none") {
    descriptionDiv.style.display = "block";
    editDescriptionForm.style.display = "none";
  } else {
    descriptionDiv.style.display = "none";
    editDescriptionForm.style.display = "block";
  }
});
