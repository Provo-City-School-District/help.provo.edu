// Define the table variable
var table;

// Initialize the data table library on the table with the class data-table
$(document).ready(function () {
  table = $(".data-table").DataTable({
    paging: true, // Enable pagination
    pageLength: 10, // Set the number of rows per page
    stateSave: true, // Enable state saving
    ordering: true, // Enable sorting
    columns: [
      { width: "5%" },
      { width: "25%" },
      { width: "25%" },
      { width: "5%" },
      { width: "5%" },
      { width: "5%" },
      { width: "5%" },
      { width: "5%" },
      { width: "5%" },
      { width: "5%" },
      { width: "5%" },
      { width: "5%" },
    ],
    autoWidth: false, // Disable auto width calculation
  });
});
$(document).ready(function () {
  table = $(".search-data-table").DataTable({
    paging: true, // Enable pagination
    pageLength: 10, // Set the number of rows per page
    stateSave: true, // Enable state saving
    ordering: true, // Enable sorting
    autoWidth: false, // Disable auto width calculation
  });
});

//initialize tinyMCE for for textarea with class tinyMCEtextarea
// var userPref = ''; // Replace this with your actual code to get the user preference

var skin, content_css;

if (userPref === "dark") {
  skin = "oxide-dark";
  content_css = "dark";
} else {
  skin = "oxide";
  content_css = "default";
}

tinymce.init({
  selector: ".tinyMCEtextarea",
  toolbar:
    "undo redo restoredraft | bold italic strikethrough | blockquote | paste pastetext removeformat | numlist bullist | code | link unlink | emoticons",
  menubar: false,
  paste_as_text: true,
  browser_spellcheck: true,
  contextmenu: false,
  plugins: [
    "autosave",
    "lists",
    "code",
    "link",
    "autolink",
    "wordcount",
    "emoticons",
  ],
  skin: skin,
  paste_data_images: false,
  content_css: content_css,
  link_default_target: "_blank",
  text_patterns: false,
  autosave_interval: "10s",
});

// display/hide new note form
var newNoteButtons = document.getElementsByClassName("new-note-button");
var newNoteModalBackground = document.getElementById(
  "new-note-form-background"
);
var newNoteForm = document.getElementById("new-note-form");
var newNoteEditor = document.getElementById("new-note-form");

if (newNoteButtons && newNoteForm && newNoteEditor) {
  for (var i = 0; i < newNoteButtons.length; i++) {
    newNoteButtons[i].addEventListener("click", function () {
      if (newNoteForm.style.display === "none") {
        newNoteForm.style.display = "block";
        newNoteModalBackground.style.display = "block";
        newNoteEditor.focus(); // Set focus to the new note editor
        newNoteEditor.scrollIntoView({ behavior: "smooth" }); // Scroll the view to the new note editor
      } else {
        newNoteForm.style.display = "none";
      }
    });
  }
}

window.onclick = function (event) {
  if (event.target == newNoteModalBackground) {
    newNoteModalBackground.style.display = "none";
    newNoteForm.style.display = "none";
  }
};

let newNoteModalCloseButton = document.getElementById("new-note-form-close");
if (newNoteModalCloseButton) {
  newNoteModalCloseButton.onclick = function (event) {
    if (event.target == newNoteModalCloseButton) {
      newNoteModalBackground.style.display = "none";
      newNoteForm.style.display = "none";
    }
  };
}
// Check if the toggle-file-upload-form and file-upload-form elements exist before adding the event listener
var toggleFileUploadForm = document.getElementById("toggle-file-upload-form");
var fileUploadForm = document.getElementById("file-upload-form");
if (toggleFileUploadForm && fileUploadForm) {
  toggleFileUploadForm.addEventListener("click", function () {
    if (fileUploadForm.style.display === "none") {
      fileUploadForm.style.display = "block";
    } else {
      fileUploadForm.style.display = "none";
    }
  });
}

// Toggle description to make it editable
var descriptionDiv = document.querySelector(".ticket-description");
var editDescriptionButton = document.getElementById("edit-description-button");
var editDescriptionForm = document.getElementById("edit-description-form");
if (descriptionDiv && editDescriptionButton && editDescriptionForm) {
  editDescriptionButton.addEventListener("click", function () {
    if (descriptionDiv.style.display === "none") {
      descriptionDiv.style.display = "block";
      editDescriptionForm.style.display = "none";
    } else {
      descriptionDiv.style.display = "none";
      editDescriptionForm.style.display = "block";
    }
  });
}

// reset search form
var resetBtn = document.getElementById("resetBtn");
if (resetBtn) {
  resetBtn.addEventListener("click", function () {
    document.getElementById("searchForm").reset();
    window.location.href = "search_tickets.php";
  });
}

//================================= Ticket - Client Search =================================
// Show the search-for-client div when the currentClient link is clicked
var currentClient = document.querySelector(".currentClient");
if (currentClient) {
  currentClient.addEventListener("click", function (event) {
    event.preventDefault(); // Prevent the default action
    document.querySelector("#search-for-client").style.display = "grid"; // Show the div
  });
}
//sends info to client_search.php then returns results
var searchForm = document.getElementById("search-form");
if (searchForm) {
  searchForm.addEventListener("submit", function (event) {
    event.preventDefault(); // Prevent the form from being submitted normally

    var firstname = document.getElementById("firstname").value;
    var lastname = document.getElementById("lastname").value;

    var xhr = new XMLHttpRequest();
    xhr.open("POST", "../../includes/client_search.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onload = function () {
      if (this.status == 200) {
        // The request was successful
        var results = JSON.parse(this.responseText);
        var resultsHTML = "<h3>Search Results</h3>";
        for (var i = 0; i < results.length; i++) {
          resultsHTML +=
            '<p><a href="#" class="username-link" data-username="' +
            results[i].username +
            '">' +
            results[i].firstname +
            " " +
            results[i].lastname +
            " (" +
            results[i].username +
            ")</a></p>";
        }
        document.getElementById("search-results").innerHTML = resultsHTML;
      } else {
        // There was an error
        console.error("An error occurred: " + this.status);
      }
    };
    xhr.send(
      "firstname=" +
        encodeURIComponent(firstname) +
        "&lastname=" +
        encodeURIComponent(lastname)
    );
  });
}
//update the value based on selection
var searchResults = document.getElementById("search-results");
if (searchResults) {
  searchResults.addEventListener("click", function (event) {
    // console.log('Clicked inside search results'); // Debug line
    if (event.target.classList.contains("username-link")) {
      event.preventDefault(); // Prevent the link from being followed

      var username = event.target.getAttribute("data-username");
      // console.log('Clicked username: ' + username); // Debug line
      document.getElementById("client").value = username;
      document.getElementById("client-display").textContent =
        "Changing Client to: " + username + " on next save";
    }
  });
}

var updateTicketForm = document.querySelector("#updateTicketForm");
if (updateTicketForm) {
  updateTicketForm.addEventListener("submit", function (e) {
    var statusField = document.querySelector("#status");
    var employeeField = document.querySelector("#employee");

    if (
      (statusField.value === "resolved" || statusField.value === "closed") &&
      (employeeField.value === "" || employeeField.value === "unassigned")
    ) {
      e.preventDefault();
      alert(
        "You cannot resolve/close a ticket if ticket is not assigned to an employee. Please assign the ticket to an employee first."
      );
    }
  });
}

//================================= Inactive User Modal =================================
function setupInactivityModal() {
  var inactivityTime = 30 * 60 * 1000; // 30 minutes in milliseconds
  var timeoutModal = document.getElementById("timeoutModal");

  var timeoutId = setTimeout(showTimeoutModal, inactivityTime);

  function showTimeoutModal() {
    timeoutModal.style.display = "block";
  }
}

// Call the function when the page loads
window.addEventListener("load", setupInactivityModal);
