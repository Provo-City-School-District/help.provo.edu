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

  // Change default sorting on /search_tickets.php
  if (window.location.pathname == "/controllers/tickets/search_tickets.php") {
    console.log("executing search_tickets.php");
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
if (newNoteButton && newNoteForm) {
  newNoteButton.addEventListener("click", function () {
    if (newNoteForm.style.display === "none") {
      newNoteForm.style.display = "block";
    } else {
      newNoteForm.style.display = "none";
    }
  });
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



    // Chart for all techs open tickets. 
    window.onload = function() {

      var allTechsChart = new CanvasJS.Chart("techOpenTicket", {
          animationEnabled: true,
          title: {
              text: "All Tech's Open Tickets"
          },
          axisY: {
              title: "Ticket Count",
              includeZero: true,
          },
          data: [{
              type: "bar",
              yValueFormatString: "#,##",
              indexLabel: "{y}",
              indexLabelPlacement: "inside",
              indexLabelFontWeight: "bolder",
              indexLabelFontColor: "white",
              dataPoints: allTechs
          }]
      });
      allTechsChart.render();

  }