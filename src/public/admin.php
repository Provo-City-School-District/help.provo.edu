<?php
include "header.php";
require_once "tickets_template.php";
require "status_popup.php";

if ($_SESSION['permissions']['is_admin'] != 1) {
    // User is not an admin
    echo 'You do not have permission to view this page.';
    exit;
}

require_once('helpdbconnect.php');


// Execute the SELECT query to retrieve all users and their permissions/settings
$user_query = <<<SQL
SELECT u.*, us.*
FROM users u
LEFT JOIN user_settings us ON u.id = us.user_id
ORDER BY u.username ASC
SQL;

$user_result = HelpDB::get()->execute_query($user_query);

// Check if the query was successful
if (!$user_result) {
    die("Query failed: " . mysqli_error(HelpDB::get()));
}



// Check if an error message is set
if (isset($_SESSION['current_status'])) {
    $status_popup = new StatusPopup($_SESSION["current_status"], StatusPopupType::fromString($_SESSION["status_type"]));
    echo $status_popup;

    unset($_SESSION['current_status']);
    unset($_SESSION['status_type']);
}

// fetch ticket feedback   
$feedback_query = <<<STR
SELECT * FROM feedback
ORDER BY id DESC
STR;
$feedback_result = HelpDB::get()->execute_query($feedback_query);

?>
<h1>Admin</h1>
<h2>All Unassigned Tickets</h2>

<?php
//query for unassigned tickets
$ticket_query = <<<STR
SELECT tickets.*, GROUP_CONCAT(DISTINCT alerts.alert_level) AS alert_levels
FROM tickets
LEFT JOIN alerts ON tickets.id = alerts.ticket_id
WHERE status NOT IN ('closed', 'resolved') 
AND (tickets.employee IS NULL OR tickets.employee = 'unassigned' OR tickets.employee = '')
GROUP BY tickets.id
ORDER BY tickets.id ASC
STR;

$ticket_result = HelpDB::get()->execute_query($ticket_query);
display_tickets_table($ticket_result, HelpDB::get(), "admin-data-table", true);
?>

<h2>All Users</h2>
<table class="allUsers nst">
    <thead>
        <tr>
            <th>Username</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Email</th>
            <th>Is Admin</th>
            <th>Is Tech</th>
            <th>is Supervisor</th>
            <!-- <th>Employee ID</th> -->

            <th>Last Login</th>
        </tr>
    </thead>
    <tbody>
        <?php // Display the results in an HTML table
        while ($user_row = mysqli_fetch_assoc($user_result)) {
        ?>
            <tr>
                <td data-cell="User Name"><a href="controllers/users/manage_user.php?id=<?= $user_row['id'] ?>"><?= $user_row['username'] ?></a></td>
                <td data-cell="First Name"><?= ucwords(strtolower($user_row['firstname'])) ?></td>
                <td data-cell="Last Name"><?= ucwords(strtolower($user_row['lastname'])) ?></td>
                <td data-cell="Email"><?= $user_row['email'] ?></td>
                <td data-cell="Is an Admin"><?= ($user_row['is_admin'] == 1 ? 'Yes' : 'No') ?></td>
                <td data-cell="Is a Tech"><?= ($user_row['is_tech'] == 1 ? 'Yes' : 'No') ?></td>
                <td data-cell="Is a Supervisor"><?= ($user_row['is_supervisor'] == 1 ? 'Yes' : 'No') ?></td>
                <!-- <td data-cell="Employee ID"><?= $user_row['ifasid'] ?></td> -->
                <td data-cell="Last Login"><?= $user_row['last_login'] ?></td>
            </tr>
        <?php
        }
        ?>
    </tbody>


</table>

<h1>Add Exclude Day</h1>
<form method="POST" action="/controllers/admin/exclude_days.php">
    <div>
        <label for="exclude_day">Exclude Day:</label>
        <input type="date" id="exclude_day" name="exclude_day">
        <input type="hidden" name="username" value="<?= $_SESSION['username'] ?>">
    </div>
    <button class="button" type="submit">Add Exclude Day</button>
</form>
<h1>Exclude Days</h1>
<?php
// Fetch the exclude days from the database. only displaying current and future exclude days
$exclude_result = HelpDB::get()->execute_query("SELECT * FROM exclude_days WHERE exclude_day >= CURDATE() ORDER BY exclude_day");
?>
<table class="exclude_days nst">
    <thead>
        <tr>
            <th>Excluded Date</th>
            <th>Added By</th>
            <th>When Created</th>
            <th>Options</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($exclude_row = mysqli_fetch_assoc($exclude_result)) : ?>
            <tr>
                <td data-cell="Excluded Day" class="center"><?= $exclude_row['exclude_day'] ?></td>
                <td data-cell="Added By" class="center"><?= $exclude_row['entered_by'] ?></td>
                <td data-cell="Date Added" class="center"><?= $exclude_row['entered_at'] ?></td>
                <td data-cell="Remove Excluded Day" class="center"><a href="/controllers/admin/delete_exclude_day.php?id=<?= $exclude_row['id'] ?>">Delete</a></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table><br>
<h2>Merge Tickets</h2>
<form method="POST" action="/controllers/tickets/merge_tickets_handler.php">
    <input type="hidden" name="username" value="<?= $_SESSION['username'] ?>">
    Host Ticket ID: <input type="text" id="ticket_id_host" name="ticket_id_host" value=""><br>
    Source Ticket ID:<input type="text" id="ticket_id_source" name="ticket_id_source" value=""><br>
    <button class="button" type="submit">Merge</button><br>
</form>

<h2>Feedback</h2>
<table class="feedback">
    <thead>
        <tr>
            <th>Ticket</th>
            <th>Client</th>
            <th>Rating</th>
            <th>Comment</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($feedback = mysqli_fetch_assoc($feedback_result)) : ?>

            <tr>
                <td data-cell="Ticket" class="center"><a href="https://help.provo.edu/controllers/tickets/edit_ticket.php?id=<?= $feedback['ticket_id'] ?>"><?= $feedback['ticket_id'] ?></a></td>
                <td data-cell="Client" class="center"><?= $feedback['client'] ?></td>
                <td data-cell="Rating" class="center"><?= $feedback['rating'] ?></td>
                <td data-cell="Comment" class="center"><?= $feedback['comments'] ?></td>
                <td data-cell="Date" class="center"><?= $feedback['created_at'] ?></td>

            </tr>
        <?php endwhile;
        ?>
    </tbody>
</table>

<?php include("footer.php"); ?>
<div id="bulk-actions-container">
    <form action="/ajax/bulk_action.php" id="bulk-actions-form">
        <div id="bulk-actions-content">
            <div class="bulk-actions-element">
                <p id="bulk-actions-row-count"></p>
            </div>
            <div class="bulk-actions-element bulk-actions-center-element">
                <label for="ticket_action">Ticket action:</label>
                <select id="ticket-action-dropdown" name="ticket_action">
                    <option value="assign_tech">Assign Tech</option>
                    <option value="assign_dept">Assign Department</option>
                    <option value="resolve">Resolve</option>
                    <option value="close">Close</option>
                </select>
            </div>
            <div id="assigned-tech-container" class="bulk-actions-element bulk-actions-center-element">
                <label for="assigned_tech">Assigned Tech:</label>
                <select name="assigned_tech">
                    <option value="unassigned">Unassigned</option>
                    <?php foreach (get_tech_usernames() as $username) : ?>
                        <?php
                        $name = get_local_name_for_user($username);
                        $firstname = ucwords(strtolower($name["firstname"]));
                        $lastname = ucwords(strtolower($name["lastname"]));
                        $display_string = $firstname . " " . $lastname . " - " . location_name_from_id(get_fast_client_location($username) ?: "");
                        ?>
                        <option value="<?= $username ?>"><?= $display_string ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="assigned-dept-container" class="bulk-actions-element bulk-actions-center-element hidden">
                <label for="assigned_dept">Assigned Dept:</label>
                <select name="assigned_dept">
                    <option hidden disabled selected value></option>
                    <?php foreach (get_departments() as $dept) : ?>
                        <?php
                        $display_string = $dept["location_name"];
                        ?>
                        <option value="<?= $dept["sitenumber"] ?>"><?= $display_string ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="bulk-actions-element bulk-actions-center-element">
                <input type="submit" value="Commit action">
            </div>
        </div>
    </form>
</div>
<script>
    $("#bulk-actions-form").submit(do_bulk_action);

    function do_bulk_action(e) {
        const ticket_ids = get_selected_tickets();
        for (const ticket_id of ticket_ids) {
            $("<input />").attr("type", "hidden")
                .attr("name", "ticket_ids[]")
                .attr("value", ticket_id)
                .appendTo("#bulk-actions-form");
        }


        e.preventDefault();

        var form = $(this);
        var action_url = form.attr('action');
        console.log(form);

        $.ajax({
            type: "POST",
            url: action_url,
            data: form.serialize(),
            success: function(data) {
                window.location.reload();
            }
        });
    }

    function get_selected_tickets() {
        const admin_table = $(".admin-data-table").first().DataTable();
        const row_data = admin_table.rows({
            selected: true
        }).data();

        let ticket_ids = [];
        row_data.each(function(value, index) {
            // This relies on ticket id being in the 2nd column as well as being parsable (pretty crusty)
            const ticket_url = value[1];
            ticket_id = ticket_url.replace(/<\/?[^>]+(>|$)/g, "");
            ticket_ids.push(ticket_id);
        });
        return ticket_ids;
    }


    function update_bulk_actions_container(e, dt, type, indexes) {
        const row_count = dt.rows({
            'selected': true
        }).count();
        if (row_count == 0) {
            $('#bulk-actions-container').hide();
        } else {
            $('#bulk-actions-container').show();
            $('#bulk-actions-row-count').text(`Selected rows: ${row_count}`);
        }
    }

    function dt_row_selected(e, dt, type, indexes) {
        update_bulk_actions_container(e, dt, type, indexes);
    }

    function dt_row_deselected(e, dt, type, indexes) {
        update_bulk_actions_container(e, dt, type, indexes);
    }


    const options = getDataTableOptions();
    options.columnDefs = [{
        target: 0,
        orderable: false,
        render: DataTable.render.select(),
    }];

    options.select = {
        style: 'multi',
        selector: 'td:first-child input[type="checkbox"]'
    };


    const admin_table = $(".admin-data-table").first().DataTable(options);

    admin_table.on('select', function(e, dt, type, indexes) {
        if (type === 'row') {
            dt_row_selected(e, dt, type, indexes);
        }
    });

    admin_table.on('deselect', function(e, dt, type, indexes) {
        if (type === 'row') {
            dt_row_deselected(e, dt, type, indexes);
        }
    });

    $("#ticket-action-dropdown").change(function() {
        const new_value = this.value;
        if (new_value == "assign_tech") {
            $('#assigned-tech-container').show();
            $('#assigned-dept-container').hide();
        } else if (new_value == "assign_dept") {
            $('#assigned-dept-container').show();
            $('#assigned-tech-container').hide();
        } else {
            $('#assigned-tech-container').hide();
            $('#assigned-dept-container').hide();
        }
    });
</script>