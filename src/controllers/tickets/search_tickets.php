<?php include("../../includes/header.php");
require_once('../../includes/helpdbconnect.php');

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Get the search terms from the form
    $search_id = isset($_GET['search_id']) ? mysqli_real_escape_string($database, $_GET['search_id']) : '';
    $search_name = isset($_GET['search_name']) ? mysqli_real_escape_string($database, $_GET['search_name']) : '';
    $search_location = isset($_GET['search_location']) ? mysqli_real_escape_string($database, $_GET['search_location']) : '';
    $search_employee = isset($_GET['search_employee']) ? mysqli_real_escape_string($database, $_GET['search_employee']) : '';
    $search_client = isset($_GET['search_client']) ? mysqli_real_escape_string($database, $_GET['search_client']) : '';
    $search_status = isset($_GET['search_status']) ? mysqli_real_escape_string($database, $_GET['search_status']) : '';


    // Construct the SQL query based on the selected search options
    $ticket_query = "SELECT * FROM tickets WHERE 1=1";
    if (!empty($search_id)) {
        $search_id = intval($search_id);
        $ticket_query .= " AND id LIKE '$search_id'";
    }
    if (!empty($search_name)) {
        $ticket_query .= " AND (name LIKE '%$search_name%' OR description LIKE '%$search_name%')";
    }
    if (!empty($search_location)) {
        $ticket_query .= " AND location LIKE '%$search_location%'";
    }
    if (!empty($search_employee)) {
        $ticket_query .= " AND employee LIKE '%$search_employee%'";
    }
    if (!empty($search_client)) {
        $ticket_query .= " AND client LIKE '%$search_client%'";
    }
    if (!empty($search_status)) {
        $ticket_query .= " AND status LIKE '%$search_status%'";
    }

    // Execute the SQL query to search for matching tickets
    $ticket_result = mysqli_query($database, $ticket_query);
}

// Fetch the list of usernames from the users table
$usernamesQuery = "SELECT username FROM users";
$usernamesResult = mysqli_query($database, $usernamesQuery);

if (!$usernamesResult) {
    die('Error fetching usernames: ' . mysqli_error($database));
}

// Store the usernames in an array
$usernames = array();
while ($usernameRow = mysqli_fetch_assoc($usernamesResult)) {
    $usernames[] = $usernameRow['username'];
}
?>

<article id="ticketWrapper">
    <h1>Search Tickets</h1>
    <form method="get" action="search_tickets.php" id="searchForm">
        <div class="form-group">
            <label for="search_id">Ticket ID:</label>
            <input type="number" class="form-control" id="search_id" name="search_id" value="<?php echo htmlspecialchars($search_id); ?>">
        </div>
        <div class="form-group">
            <label for="search_name">Name or Description:</label>
            <input type="text" class="form-control" id="search_name" name="search_name" value="<?php echo htmlspecialchars($search_name); ?>">
        </div>
        <div class="form-group">
            <label for="search_location">Location:</label>
            <input type="text" class="form-control" id="search_location" name="search_location" value="<?php echo htmlspecialchars($search_location); ?>">
        </div>
        <div class="form-group">
            <label for="search_employee">Employee:</label>
            <!-- <input type="text" class="form-control" id="search_employee" name="search_employee" value="<?php echo htmlspecialchars($search_employee); ?>"> -->
            <select id="search_employee" name="search_employee">
                <option value="" selected></option>
                <?php foreach ($usernames as $username) : ?>
                    <option value="<?= $username ?>" <?= $search_employee === $username ? 'selected' : '' ?>><?= $username ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="search_client">Client:</label>
            <!-- <input type="text" class="form-control" id="search_client" name="search_client" value="<?php echo htmlspecialchars($search_client); ?>"> -->
            <select id="search_client" name="search_client">
                <option value="" selected></option>
                <?php foreach ($usernames as $username) : ?>

                    <option value="<?= $username ?>" <?= $search_client === $username ? 'selected' : '' ?>><?= $username ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="search_status">Status:</label>
            <select id="status" name="search_status">
                <option value="" selected></option>
                <option value="open" <?= ($search_status == 'open') ? ' selected' : '' ?>>Open</option>
                <option value="closed" <?= ($search_status == 'closed') ? ' selected' : '' ?>>Closed</option>
                <option value="resolved" <?= ($search_status == 'resolved') ? ' selected' : '' ?>>Resolved</option>
                <option value="pending" <?= ($search_status == 'pending') ? ' selected' : '' ?>>Pending</option>
                <option value="vendor" <?= ($search_status == 'vendor') ? ' selected' : '' ?>>Vendor</option>
                <option value="maintenance" <?= ($search_status == 'maintenance') ? ' selected' : '' ?>>Maintenance</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
        <button type="reset" id="resetBtn" class="btn btn-secondary">Reset</button>
    </form>


    <h2>Search Results</h2>
    <table class="ticketsTable data-table">
        <thead>
            <tr>
                <th class="tID">ID</th>
                <th>Subject</th>
                <th>Request Detail</th>
                <th class="tLocation">Location</th>
                <th>Request Category</th>
                <th class="tUser">Assigned Tech</th>
                <th>Current Status</th>
                <th class="tDate">Created</th>
                <th class="tDate">Last Updated</th>
                <th class="tDate">Due</th>
            </tr>
        </thead>
        <tbody>
            <?php
            //   print_r(mysqli_fetch_assoc($ticket_result));
            // Display the search results in an HTML table
            while ($row = mysqli_fetch_assoc($ticket_result)) {

            ?>
                <?php
                $due_date = date("y-m-d", strtotime($row['due_date']));
                $overdue = strtotime($due_date) < strtotime(date("Y-m-d"));
                ?>
                <tr>
                    <td data-cell="ID"><a href="/controllers/tickets/edit_ticket.php?id=<?= $row["id"]; ?>"><?= $row["id"] ?></a></td>
                    <td data-cell="Subject"><a href="/controllers/tickets/edit_ticket.php?id=<?= $row["id"]; ?>"><?= $row["name"] ?></a></td>
                    <td data-cell="Request Detail"><?= limitChars(html_entity_decode($row["description"]), 100) ?></td>
                    <td data-cell="Location"><?= $row["location"] ?> <br><br>RM <?= $row['room'] ?></td>
                    <td data-cell="Category"></td>
                    <td data-cell="Assigned Employee"><?= $row['employee'] ?></td>
                    <td data-cell="Current Status"><?= $row['status'] ?></td>
                    <td data-cell="Created"><?= $row['created'] ?></td>
                    <td data-cell="Last Updated"><?= $row['last_updated'] ?></td>
                    <?php if ($overdue) { ?>
                        <td data-cell="Due">
                            <p class="warning"><?= $row['due_date'] ?></p>
                        </td>
                    <?php } else { ?>
                        <td data-cell="Due"><?= $row['due_date'] ?></td>
                    <?php } ?>
                </tr>

            <?php
            }

            ?>
        </tbody>
    </table>








</article>
<script>
    // reset search form
    document.getElementById("resetBtn").addEventListener("click", function() {
        document.getElementById("searchForm").reset();
        window.location.href = 'search_tickets.php';
    });
</script>
<?php include("../../includes/footer.php"); ?>