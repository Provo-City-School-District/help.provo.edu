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

// Prevent Double Submits
document.querySelectorAll("form").forEach((form) => {
  form.addEventListener("submit", (e) => {
    // Prevent if already submitting
    if (form.classList.contains("is-submitting")) {
      e.preventDefault();
    }

    // Add class to hook our visual indicator on
    form.classList.add("is-submitting");
  });
});

$("#client").on("input", function () {
  const new_value = extractLast($(this).val());
  $("#client").autocomplete({
    source: function (request, response) {
      $.ajax({
        url: "/ajax/name_search_ldap.php",
        method: "GET",
        data: {
          name: new_value,
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
              value: item.username,
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
  });
});
// Hide detailContainer, attachment-fields, and submit button if department is Maintenance(1700)
console.log(userDepartment);
document.getElementById("department").addEventListener("change", function () {
  var selectedValue = this.value;
  var detailContainer = document.querySelector(".detailContainer");
  var attachmentFields = document.getElementById("attachment-fields");
  var submitButton = document.querySelector('input[type="submit"]');
  var message = document.getElementById("message");
  var assignedField = document.getElementById("assigned");
  var assignToSelfCheckbox = document.getElementById("assign_to_self");
  if (selectedValue === "1700") {
    if (canInputMaintenance || userDepartment === 1700) {
      detailContainer.style.display = "block";
      attachmentFields.style.display = "block";
      submitButton.style.display = "block";
      message.style.display = "none";
      assignedField.style.display = "none";
      assignToSelfCheckbox.style.display = "block";
    } else {
      detailContainer.style.display = "none";
      attachmentFields.style.display = "none";
      submitButton.style.display = "none";
      message.style.display = "block";
      assignedField.style.display = "none";
      assignToSelfCheckbox.style.display = "none";
    }
  } else {
    detailContainer.style.display = "block";
    attachmentFields.style.display = "block";
    submitButton.style.display = "block";
    message.style.display = "none";
    assignedField.style.display = "block";
    assignToSelfCheckbox.style.display = "none";
  }
});


function dismissTemplateView() {
  const templateModalBackground = document.getElementById(
    "note-template-form-background"
  );
  const templateForm = document.getElementById("note-template-form");

  templateModalBackground.style.display = "none";
  templateForm.style.display = "none";
}

const showTemplatesButton = document.getElementById("show-templates-button");

if (showTemplatesButton) {
    showTemplatesButton.onclick = function (event) {
        const templateModalBackground = document.getElementById(
            "note-template-form-background"
        );
        const templateForm = document.getElementById("note-template-form");
        if (templateForm.style.display === "none") {
            templateForm.style.display = "block";
            templateModalBackground.style.display = "block";
        } else {
            dismissTemplateView();
        }
    };
}

const templateCloseButton = document.getElementById("note-template-form-close");
if (templateCloseButton) {
    templateCloseButton.onclick = function (event) {
        const templateModalBackground = document.getElementById(
            "note-template-form-background"
        );
        const templateForm = document.getElementById("note-template-form");
        if (event.target == templateCloseButton) {
            dismissTemplateView();
        }
    };
}


window.onclick = function (event) {
    const templateModalBackground = document.getElementById(
        "note-template-form-background"
    );
    if (event.target == templateModalBackground) {
        dismissTemplateView();
    }
};

function insertTemplate(template_name) {
    $.ajax({
        url: "/ajax/note_templates/get_template_content.php",
        method: "GET",
        data: {
            template_name: template_name,
        },
        success: function(data, textStatus, xhr) {
            const template_content = data.content;
            tinymce.get('note-textbox').setContent(template_content);
            dismissTemplateView();
        },
        error: function() {
            alert("Error: Note template content AJAX call failed");
        },
    });
}