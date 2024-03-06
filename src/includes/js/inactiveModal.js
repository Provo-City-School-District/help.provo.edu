//================================= Inactive User Modal =================================
var timeoutId; // Declare timeoutId at the top level so it can be accessed from both functions

function setupInactivityModal() {
  var inactivityTime = 30 * 60 * 1000; // 30 minutes in milliseconds
  var timeoutModal = document.getElementById("timeoutModal");
  var iframe = document.getElementById("note_ifr"); // Replace with your iframe's id

  timeoutId = setTimeout(showTimeoutModal, inactivityTime);

  function showTimeoutModal() {
    timeoutModal.style.display = "block";
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
