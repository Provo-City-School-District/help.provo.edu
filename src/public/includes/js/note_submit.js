//calc time on the fly
document
  .querySelectorAll(
    "#work_hours, #work_minutes, #travel_hours, #travel_minutes"
  )
  .forEach(function (el) {
    el.addEventListener("input", function () {
      const workHours =
        parseInt(document.getElementById("work_hours").value) || 0;
      const workMinutes =
        parseInt(document.getElementById("work_minutes").value) || 0;
      const travelHours =
        parseInt(document.getElementById("travel_hours").value) || 0;
      const travelMinutes =
        parseInt(document.getElementById("travel_minutes").value) || 0;

      const totalTime =
        (workHours + travelHours) * 60 + workMinutes + travelMinutes;

      document.getElementById("total_time").value = totalTime;
    });
    el.addEventListener("focus", function () {
      if (this.value === "0") {
        this.value = "";
      }
    });
    el.addEventListener("blur", function () {
      if (this.value === "") {
        this.value = "0";
      }
    });
  });

$(document).ready(function () {
  $("#note-submit").on("submit", function (e) {
    let fields = [
      "work_hours",
      "work_minutes",
      "travel_hours",
      "travel_minutes",
    ];
    let allZero = fields.every(function (field) {
      return parseInt(document.getElementById(field).value, 10) === 0;
    });

    const values = fields.map((field) =>
      parseInt(document.getElementById(field).value, 10)
    );

    const note_content = tinymce.activeEditor.getContent("note");

    // Check if the user is a tech user and if all the time fields are valid
    if (isTechUser) {
      if (allZero) {
        alert(
          "Please enter a value greater than 0 for at least one of the time fields."
        );
        e.preventDefault(); // Prevent the form submission
        return;
      } else if (
        values[0] < 0 ||
        values[1] < 0 ||
        values[2] < 0 ||
        values[3] < 0
      ) {
        alert("Negative time values are not allowed.");
        e.preventDefault(); // Prevent the form submission
        return;
      }
    }

    if (!note_content) {
      alert("Please enter some note content");
      e.preventDefault(); // Prevent the form submission
      return;
    }

    e.preventDefault();

    $.ajax({
      type: "POST",
      url: "add_note_handler.php",
      data: $(this).serialize(),
      success: function (response) {
        console.log("Response received:", response); // Log the response for debugging
        const res = JSON.parse(response);

        if (res.status === "duplicate") {
          alert(res.message);
          return;
        }

        if (res.status === "success") {
          // Reload the notes section
          $("#note-table").load(location.href + " #note-table", function () {
            // Scroll to the new note
            var newNote =
              noteOrder === "ASC"
                ? $(".ticketsTable tr").last()
                : $(".ticketsTable tr").first();
            $("html, body").animate(
              {
                scrollTop: newNote.offset().top,
              },
              200
            ); // 200 milliseconds
          });

          // Close the modal
          $("#new-note-form-background").hide();
          $("#new-note-form").hide();

          // Clear the TinyMCE editor
          tinymce.get("note").setContent("");

          // clear time input fields
          $("#work_minutes").val(0);
          $("#work_hours").val(0);
          $("#travel_hours").val(0);
          $("#travel_minutes").val(0);
        } else {
          alert(res.message);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.log("AJAX error:", textStatus, errorThrown); // Log the error for debugging
      },
    });
  });
});
