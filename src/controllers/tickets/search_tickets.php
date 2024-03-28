<?php
require_once("block_file.php");
include("header.php");
require_once('helpdbconnect.php');
require_once('swdbconnect.php');
include("ticket_utils.php");
?>
<h1>Search Ticket Information</h1>
<?php
if (user_is_tech($_SESSION['username'])) {
    //Technician Search Form
?>
    <div class="tabNav">
        <button class="tablinks" onclick="openTab(event, 'Tickets')">Search Tickets</button>
        <button class="tablinks" onclick="openTab(event, 'Notes')">Search Notes</button>
    </div>

    <div id="Tickets" class="tabcontent">
        <h2>Search Tickets</h2>
        <?php include __DIR__ . '/../../includes/templates/search_tickets_tech_form.php'; ?>
    </div>

    <div id="Notes" class="tabcontent">
        <h2>Search Notes</h2>
        <?php include __DIR__ . '/../../includes/templates/search_notes_form.php'; ?>
    </div>
<?php
} //End Technician Search Forms
else {
    //Non-Technician Search Form
    include __DIR__ . '/../../includes/templates/search_tickets_client_form.php';
}
?>


<div id="results">

</div>




<script>
    function openTab(evt, tabName) {
        var i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tabcontent");
        for (i = 0; i < tabcontent.length; i++) {
            if (tabcontent[i]) {
                tabcontent[i].style.display = "none";
            }
        }
        tablinks = document.getElementsByClassName("tablinks");
        for (i = 0; i < tablinks.length; i++) {
            if (tablinks[i]) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
        }
        var tab = document.getElementById(tabName);
        if (tab) {
            tab.style.display = "block";
        }
        if (evt.currentTarget) {
            evt.currentTarget.className += " active";
        }
    }

    // Handle Search Form Submission
    function handleSubmit(formId, urlPath) {
        var form = document.getElementById(formId);
        if (form) {
            form.addEventListener("submit", function(e) {
                e.preventDefault();
                console.log(formId);
                console.log(urlPath);
                var url = new URL(urlPath, window.location.origin);
                var formData = new FormData(this);
                var params = new URLSearchParams(formData);

                url.search = params;

                fetch(url, {
                        method: "GET",
                    })
                    .then((response) => response.text())
                    .then((data) => {
                        // Display the data in your page
                        document.getElementById("results").innerHTML = data;
                    })
                    .catch((error) => {
                        console.error("Error:", error);
                    });
            });
        } else {
            console.log('Form with ID ' + formId + ' does not exist.');
        }
    }
    handleSubmit("searchClient", "includes/search_tickets_client_query_builder.php");
    handleSubmit("searchTickets", "includes/search_tickets_tech_query_builder.php");
    handleSubmit("searchNotes", "includes/search_notes_query_builder.php");
</script>



<script src="/includes/js/pages/search_tickets.js?v=1.0.0" type="text/javascript"></script>
<?php include("footer.php"); ?>