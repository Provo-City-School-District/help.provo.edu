var timeoutId; // Declare timeoutId at the top level so it can be accessed from both functions

function setupInactivityModal() {
  var loginTime = new Date(loginTimeFromPHP); // loginTimeFromPHP is the variable echoed from PHP
  var currentTime = new Date();
  var timeSinceLogin = currentTime - loginTime; // Time since login in milliseconds

  var inactivityTime = 30 * 60 * 1000; // 30 minutes in milliseconds
  var sessionWarningTime = 2 * 60 * 60 * 1000 + 45 * 60 * 1000; // 2 hours 45 minutes in milliseconds

  var timeoutModal = document.getElementById("timeoutModal");
  var iframe = document.getElementById("note_ifr"); // iframe's id

  if (timeSinceLogin > sessionWarningTime) {
    showTimeoutModal();
  } else {
    timeoutId = setTimeout(showTimeoutModal, inactivityTime);
  }

  function showTimeoutModal() {
    var modalTitle = document.getElementById("modalTitle");
    var modalMessage = document.getElementById("modalMessage");
    var modalButton = document.getElementById("modalButton");

    var time_difference = calculateTimeSinceLastLogin();
    if (time_difference > 2 * 60 * 60 + 45 * 60) {
      modalTitle.textContent = "Session Expiry Alert";
      modalMessage.textContent =
        "Your session is close to expiring. It's recommended to log out and back in as soon as possible.";
      modalButton.textContent = "Logout";
      modalButton.onclick = function () {
        window.location.href = "/controller/logout.php";
      };
    } else if (time_difference > 2 * 60 * 60) {
      modalTitle.textContent = "Session Expiry Alert";
      modalMessage.textContent =
        "Your session may have expired. It's recommended to log out and back in.";
      modalButton.textContent = "Reload Page";
      modalButton.onclick = function () {
        location.reload();
      };
    } else {
      modalTitle.textContent = "Inactivity Alert";
      modalMessage.textContent =
        "You've been inactive for more than 30 minutes.";
      modalButton.style.display = "none";
    }

    document.getElementById("timeoutModal").style.display = "block";
  }

  function resetTimeout() {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(showTimeoutModal, inactivityTime);
  }

  window.addEventListener("click", resetTimeout);
  window.addEventListener("keydown", resetTimeout);
  window.addEventListener("mousemove", resetTimeout);
  window.addEventListener("scroll", resetTimeout);

  // Add same event listeners to the document inside the iframe
  iframe.contentWindow.document.addEventListener("click", resetTimeout);
  iframe.contentWindow.document.addEventListener("keydown", resetTimeout);
  iframe.contentWindow.document.addEventListener("mousemove", resetTimeout);
  iframe.contentWindow.document.addEventListener("scroll", resetTimeout);
}

function dismiss_timeout_modal() {
  const timeoutModal = document.getElementById("timeoutModal");
  timeoutModal.style.display = "none";
  clearTimeout(timeoutId); // Clear the existing timeout
  timeoutId = setTimeout(showTimeoutModal, 30 * 60 * 1000); // Start a new timeout
}

// Call the function when the page loads
window.addEventListener("load", setupInactivityModal);
