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

// add alert if no time is entered
$("#note-submit").on("submit", function (evt) {
  let fields = ["work_hours", "work_minutes", "travel_hours", "travel_minutes"];
  let allZero = fields.every(function (field) {
    return parseInt(document.getElementById(field).value, 10) === 0;
  });

  const note_content = tinymce.activeEditor.getContent("note");
  console.log(note_content);
  if (allZero) {
    alert(
      "Please enter a value greater than 0 for at least one of the time fields."
    );
    evt.preventDefault(); // Prevent the form submission
  } else if (!note_content) {
    alert("Please enter some note content");
    evt.preventDefault();
  }
});
