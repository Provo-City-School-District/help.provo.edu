function split(val) {
  return val.split(/,\s*/);
}

function extractLast(term) {
  return split(term).pop();
}

$("#cc_emails").on("input", function () {
  const new_value = extractLast($(this).val());
  $("#cc_emails").autocomplete({
    source: function (request, response) {
      $.ajax({
        url: "/ajax/email_matches_ldap.php",
        method: "GET",
        data: {
          input: new_value,
        },
        success: function (data, textStatus, xhr) {
          let mappedResults = $.map(data, function (item) {
            let itemLocation = item.location ? item.location : "unknown";
            return $.extend(item, {
              label:
                item.firstName +
                " " +
                item.lastName +
                " (" +
                itemLocation +
                ")",
              value: item.email,
            });
          });
          response(mappedResults);
        },
        error: function () {
          alert("Error: Autocomplete AJAX call failed");
        },
      });
    },
    minLength: 3,
    search: function () {
      const term = extractLast(this.value);
      if (term.length < 1) {
        return false;
      }
    },
    focus: function () {
      // prevent value inserted on focus
      return false;
    },
    select: function (event, ui) {
      let terms = split(this.value);

      terms.pop();
      terms.push(ui.item.value);
      terms.push("");

      this.value = terms.join(",");
      return false;
    },
  });
});

$("#bcc_emails").on("input", function () {
  const new_value = extractLast($(this).val());
  console.log("running");
  $("#bcc_emails").autocomplete({
    source: function (request, response) {
      $.ajax({
        url: "/ajax/email_matches_ldap.php",
        method: "GET",
        data: {
          input: new_value,
        },
        success: function (data, textStatus, xhr) {
          let mappedResults = $.map(data, function (item) {
            let itemLocation = item.location ? item.location : "unknown";
            return $.extend(item, {
              label:
                item.firstName +
                " " +
                item.lastName +
                " (" +
                itemLocation +
                ")",
              value: item.email,
            });
          });
          response(mappedResults);
        },
        error: function () {
          alert("Error: Autocomplete AJAX call failed");
        },
      });
    },
    minLength: 3,
    search: function () {
      const term = extractLast(this.value);
      if (term.length < 1) {
        return false;
      }
    },
    focus: function () {
      // prevent value inserted on focus
      return false;
    },
    select: function (event, ui) {
      let terms = split(this.value);

      terms.pop();
      terms.push(ui.item.value);
      terms.push("");

      this.value = terms.join(",");
      return false;
    },
  });
});

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

const newTaskButton = document.getElementById("new-task-button");

if (newTaskButton) {
  newTaskButton.addEventListener("click", function () {
    const newTaskForm = document.getElementById("new-task-form");
    const newTaskModalBackground = document.getElementById(
      "new-task-form-background"
    );
    if (newTaskForm) {
      if (newTaskForm.style.display === "none") {
        newTaskForm.style.display = "block";
        newTaskModalBackground.style.display = "block";
        newNoteEditor.scrollIntoView({ behavior: "smooth" }); // Scroll the view to the new note editor
      } else {
        newTaskForm.style.display = "none";
      }
    }
  });
}

const newTaskModalCloseButton = document.getElementById("new-task-form-close");
if (newTaskModalCloseButton) {
  newTaskModalCloseButton.onclick = function (event) {
    const newTaskModalBackground = document.getElementById(
      "new-task-form-background"
    );
    const newTaskForm = document.getElementById("new-task-form");
    if (event.target == newTaskModalCloseButton) {
      newTaskModalBackground.style.display = "none";
      newTaskForm.style.display = "none";
    }
  };
}

function scrollIntoNoteForm()
{
    document.getElementById('new-note-form').scrollIntoView({behavior: 'smooth'});
}

// display/hide new note form
var newNoteButtons = document.getElementsByClassName("new-note-button");

for (const button of newNoteButtons) {
    button.onclick = scrollIntoNoteForm();
}

var newNoteForm = document.getElementById("new-note-form");
var newNoteEditor = document.getElementById("new-note-form");

if (newNoteButtons && newNoteForm && newNoteEditor) {
  for (var i = 0; i < newNoteButtons.length; i++) {
    newNoteButtons[i].addEventListener("click", function () {
      if (newNoteForm.style.display === "none") {
        newNoteForm.style.display = "block";
        tinymce.activeEditor.focus(); // Set focus to the new note editor
        newNoteEditor.scrollIntoView({ behavior: "smooth" }); // Scroll the view to the new note editor
      } else {
        newNoteForm.style.display = "none";
      }
    });
  }
}

window.onclick = function (event) {
  const newTaskModalBackground = document.getElementById(
    "new-task-form-background"
  );
  const newTaskForm = document.getElementById("new-task-form");

  if (event.target == newTaskModalBackground) {
    newTaskModalBackground.style.display = "none";
    newTaskForm.style.display = "none";
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
      toggleFileUploadForm.style.display = "none";
    } else {
      fileUploadForm.style.display = "none";
      toggleFileUploadForm.style.display = "block";
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

//================================= Ticket - Client Search =================================
// Show the search-for-client div when the currentClient link is clicked
const searchClientButton = document.getElementById("search-client-button");
if (searchClientButton) {
  searchClientButton.addEventListener("click", function (event) {
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
    xhr.open("POST", "/ajax/client_search.php", true);
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
            " — " +
            results[i].location_name +
            " (" +
            results[i].title +
            ")" +
            "</a></p>";
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


