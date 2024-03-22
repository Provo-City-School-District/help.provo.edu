// reset form Button
var resetBtn = document.getElementById("resetBtn");
if (resetBtn) {
  resetBtn.addEventListener("click", function () {
    document.getElementById("searchForm").reset();
    window.location.href = "search_tickets.php";
  });
}

//================================= Ticket - Client Search =================================
// Show the search-for-client div when the currentClient link is clicked
var currentClient = document.querySelector(".currentClient");
if (currentClient) {
  currentClient.addEventListener("click", function (event) {
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
    xhr.open("POST", "../../includes/client_search.php", true);
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
            " (" +
            results[i].username +
            ")</a></p>";
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

$("#search_client").on("input", function() {
    const new_value = $(this).val();
    $("#search_client").autocomplete({
        source: function (request, response) {
            $.ajax({
                url: "/ajax/username_search_ldap.php",
                method: "GET",
                data: {username: new_value},
                success: function(data, textStatus, xhr) {
                    let mappedResults = $.map(data, function (item) {
                        let itemLocation = item.location ? item.location : "unknown";
                        return $.extend(item, { label: item.firstName + ' ' + item.lastName + ' (' + itemLocation + ')', value: item.username });
                    });
                    response(mappedResults);
                },
                error: function() {
                    alert("Error: Autocomplete AJAX call failed");
                }
            });
        },
        minLength: 2,
        focus: function () {
            // prevent value inserted on focus
            return false;
        }
    });
});