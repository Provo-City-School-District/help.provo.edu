// Define the table variable
var table;

function getDataTableOptions() {
  const options = {
    paging: true,
    pageLength: ticketLimit, // Enable pagination
    stateSave: true, // Enable state saving
    ordering: true, // Enable sorting
    autoWidth: false, // Disable auto width calculation
  };

  return options;
}

// Initialize the data table library on the table with the class data-table
$(document).ready(function () {
  // Define general properties
  let options = getDataTableOptions();

  // Modify properties for specific cases
  if (
    window.location.pathname === "/controllers/tickets/ticket_history.php" ||
    window.location.pathname === "/controllers/tickets/recent_tickets.php" ||
    window.location.pathname === "/controllers/tickets/flagged_tickets.php"
  ) {
    options.order = [[10, "desc"]];
  } else if (
    window.location.pathname === "/supervisor.php" ||
    window.location.pathname === "/tickets.php"
  ) {
    options.order = [[9, "asc"]];
  } else if (
    window.location.pathname === "/controllers/tickets/subordinate_tickets.php"
  ) {
    options.order = [[11, "asc"]];
  }

  // Initialize the data table library on the table with the class data-table
  table = $(".data-table").DataTable(options);
});

// Initialize the data table library on the table with the class search-data-table
$(document).ready(function () {
  table = $(".search-data-table").DataTable({
    paging: true, // Enable pagination
    pageLength: 50, // Set the number of rows per page
    stateSave: true, // Enable state saving
    ordering: true, // Enable sorting
    autoWidth: false, // Disable auto width calculation
  });
});

// Initialize the data table library on the table with the class nst (non standard table)
$(document).ready(function () {
  table = $(".nst").DataTable({
    paging: true, // Enable pagination
    pageLength: 10, // Set the number of rows per page
    stateSave: true, // Enable state saving
    ordering: true, // Enable sorting
    autoWidth: false, // Disable auto width calculation
  });
});
$(document).ready(function () {
  $("#alertsTable").DataTable({
    paging: true, // Enable pagination
    pageLength: 10, // Set the number of rows per page
    stateSave: true, // Enable state saving
    ordering: true, // Enable sorting
    autoWidth: false, // Disable auto width calculation
  });
});
$(document).ready(function () {
  $(".feedback").DataTable({
    order: [[4, "desc"]],
  });
});
