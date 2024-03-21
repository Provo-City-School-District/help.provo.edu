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
        tinymce.activeEditor.focus(); // Set focus to the new note editor
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
