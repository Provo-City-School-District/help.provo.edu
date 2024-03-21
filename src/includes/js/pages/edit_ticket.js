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
        url: "/email_matches_ldap.php",
        method: "GET",
        data: {
          email: new_value,
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
        url: "/email_matches_ldap.php",
        method: "GET",
        data: {
          email: new_value,
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
