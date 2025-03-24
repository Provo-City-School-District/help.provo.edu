document.getElementById("mobileMenu").addEventListener("click", function () {
  var nav = document.getElementById("mainNav");
  if (nav.style.display === "none") {
    nav.style.display = "block";
  } else {
    nav.style.display = "none";
  }
});

function disableSubmitButton() {
  document.getElementById("submitButton").disabled = true;
}
