// Define the table variable
var table;

// Initialize the data table library on the table with the class data-table
$(document).ready(function () {
  if (
    window.location.pathname === "/controllers/tickets/ticket_history.php" ||
    window.location.pathname === "/controllers/tickets/recent_tickets.php" ||
    window.location.pathname === "/controllers/tickets/flagged_tickets.php"
  ) {
    table = $(".data-table").DataTable({
      order: [[9, "desc"]], // Sort by the second column in ascending order
      stateSave: true, // Enable state saving
    });
  } else if (
    window.location.pathname === "/supervisor.php" ||
    window.location.pathname === "/admin.php" ||
    window.location.pathname === "/tickets.php"
  ) {
    table = $(".data-table").DataTable({
      order: [[8, "asc"]], // Sort by the second column in ascending order
      stateSave: true, // Enable state saving
    });
  } else if (
    window.location.pathname === "/controllers/tickets/subordinate_tickets.php"
  ) {
    table = $(".data-table").DataTable({
      order: [[11, "asc"]], // Sort by the second column in ascending order
      // pageLength: 50, // Set the number of rows per page
      stateSave: true, // Enable state saving
    });
  } else {
    table = $(".data-table").DataTable({
      paging: true, // Enable pagination
      // pageLength: 10, // Set the number of rows per page
      stateSave: true, // Enable state saving
      ordering: true, // Enable sorting
      columns: [
        { width: "5%" },
        { width: "20%" },
        { width: "10%" },
        { width: "5%" },
        { width: "5%" },
        { width: "5%" },
        { width: "5%" },
        { width: "5%" },
        { width: "5%" },
        { width: "5%" },
        { width: "5%" },
        { width: "5%" },
        { width: "5%" },
      ],
      // autoWidth: true, // Disable auto width calculation
    });
  }
});

// Initialize the data table library on the table with the class search-data-table
$(document).ready(function () {
  table = $(".search-data-table").DataTable({
    paging: true, // Enable pagination
    pageLength: 10, // Set the number of rows per page
    stateSave: true, // Enable state saving
    ordering: true, // Enable sorting
    autoWidth: false, // Disable auto width calculation
  });
});

// Initialize the data table library on the table with the class search-data-table
$(document).ready(function () {
  table = $(".nst").DataTable({
    paging: true, // Enable pagination
    pageLength: 10, // Set the number of rows per page
    stateSave: true, // Enable state saving
    ordering: true, // Enable sorting
    autoWidth: false, // Disable auto width calculation
  });
});
