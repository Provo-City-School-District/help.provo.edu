// Define the table variable
var table;

// Initialize the data table library on the table with the class data-table
$(document).ready(function () {
  table = $(".data-table").DataTable({
    paging: true, // Enable pagination
    pageLength: 10, // Set the number of rows per page
    stateSave: true, // Enable state saving
    ordering: true, // Enable sorting
    order: [[0, "asc"]], // Set the default sort order
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
    "undo redo | bold italic strikethrough | blockquote | paste pastetext removeformat | numlist bullist | code | link unlink",
  menubar: false,
  paste_as_text: true,
  browser_spellcheck: true,
  contextmenu: false,
  plugins: ["lists", "code", "link", "autolink", "wordcount"],
  skin: skin,
  content_css: content_css,
  link_default_target: "_blank",
  text_patterns: false,
});

// display/hide new note form
var newNoteButtons = document.getElementsByClassName("new-note-button");
var newNoteModalBackground = document.getElementById(
  "new-note-form-background"
);
var newNoteForm = document.getElementById("new-note-form");
var newNoteEditor = document.getElementById("new-note-form");
var newNoteModalCloseButton = document.getElementById("new-note-form-close");

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

//================================= Charts =================================
// Chart for all techs open tickets.
window.onload = function () {
  // tech department
  var allTechsChart = new CanvasJS.Chart("techOpenTicket", {
    height: 1000,
    animationEnabled: true,
    title: {
      text: "All Technicians Open Tickets",
    },
    axisY: {
      title: "Ticket Count",
      includeZero: true,
    },
    axisX: {
      interval: 1, // Set the interval of the x-axis labels to 1
    },
    data: [
      {
        type: "bar",
        yValueFormatString: "#,##",
        indexLabel: "{y}",
        indexLabelPlacement: "outside",
        indexLabelFontWeight: "bolder",
        indexLabelFontColor: "white",
        indexLabelFontSize: 4,
        dataPoints: allTechs,
      },
    ],
  });
  allTechsChart.render();
  // by location
  var byLocationChart = new CanvasJS.Chart("byLocation", {
    height: 1000,
    animationEnabled: true,
    title: {
      text: "All Open Tickets By Location",
    },
    axisY: {
      title: "Ticket Count",
      includeZero: true,
    },
    axisX: {
      interval: 1, // Set the interval of the x-axis labels to 1
    },
    data: [
      {
        type: "bar",
        yValueFormatString: "#,##",
        indexLabel: "{y}",
        indexLabelPlacement: "outside",
        indexLabelFontWeight: "bolder",
        indexLabelFontColor: "white",
        indexLabelFontSize: 4,
        dataPoints: byLocation,
      },
    ],
  });
  byLocationChart.render();
  //field techs open tickets
  var fieldTechOpenChart = new CanvasJS.Chart("fieldTechOpen", {
    height: 1000,
    animationEnabled: true,
    title: {
      text: "Field Tech's Open Tickets",
    },
    axisY: {
      title: "Ticket Count",
      includeZero: true,
    },
    axisX: {
      interval: 1, // Set the interval of the x-axis labels to 1
    },
    data: [
      {
        type: "bar",
        yValueFormatString: "#,##",
        indexLabel: "{y}",
        indexLabelPlacement: "outside",
        indexLabelFontWeight: "bolder",
        indexLabelFontColor: "white",
        indexLabelFontSize: 4,
        dataPoints: fieldTechOpen,
      },
    ],
  });
  fieldTechOpenChart.render();
};

//================================= Ticket - Client Search =================================
document
  .querySelector(".currentClient")
  .addEventListener("click", function (event) {
    event.preventDefault(); // Prevent the default action
    document.querySelector("#search-for-client").style.display = "grid"; // Show the div
  });

//sends info to client_search.php then returns results
document
  .getElementById("search-form")
  .addEventListener("submit", function (event) {
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
//update the value based on selection
document
  .getElementById("search-results")
  .addEventListener("click", function (event) {
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

document
  .querySelector("#updateTicketForm")
  .addEventListener("submit", function (e) {
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
