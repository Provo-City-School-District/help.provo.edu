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


// Execute the SELECT query to retrieve all users from the users table
$user_result = HelpDB::get()->execute_query("SELECT * FROM users ORDER BY username ASC");
// Check if the query was successful
if (!$user_result) {
    die("Query failed: " . mysqli_error($conn));
}

// Check if an error message is set
if (isset($_SESSION['current_status'])) {
    $status_popup = new StatusPopup($_SESSION["current_status"], StatusPopupType::fromString($_SESSION["status_type"]));
    echo $status_popup;

    unset($_SESSION['current_status']);
    unset($_SESSION['status_type']);
}



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
            <th>Exclude Day</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php while ($exclude_row = mysqli_fetch_assoc($exclude_result)) : ?>
            <tr>
                <td data-cell="Excluded Day"><?= $exclude_row['exclude_day'] ?></td>
                <td data-cell="Remove Excluded Day"><a href="/controllers/admin/delete_exclude_day.php?id=<?= $exclude_row['id'] ?>">Delete</a></td>
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
<?php include("footer.php"); ?>
<div id="bulk-actions-container">
    <form action="/ajax/bulk_action.php" id="bulk-actions-form">
        <div id="bulk-actions-content">
            <p id="bulk-actions-row-count"></p>
            <div>
                <label for="ticket_action">Ticket action:</label>
                <select name="ticket_action">
                    <option value="resolve">Resolve</option>
                    <option value="close">Close</option>
                    <option value="assign">Assign</option>
                </select>
            </div>
            <input type="submit" value="Commit action">
        </div>
    </form>
</div>
<script>
    $("#bulk-actions-form").submit(do_bulk_action);
    function do_bulk_action(e)
    {   
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

    function get_selected_tickets()
    {
        const admin_table = $(".admin-data-table").first().DataTable();
        const row_data = admin_table.rows({selected: true}).data();

        let ticket_ids = [];
        row_data.each(function (value, index) {
            const ticket_url = value[1];
            ticket_id = ticket_url.replace(/<\/?[^>]+(>|$)/g, "");
            ticket_ids.push(ticket_id);
        });
        return ticket_ids;
    }


    function update_bulk_actions_container(e, dt, type, indexes)
    {
        const row_count = dt.rows({'selected': true}).count();
        if (row_count == 0) {
            $('#bulk-actions-container').hide();
        } else {
            $('#bulk-actions-container').show();
            $('#bulk-actions-row-count').text(`Selected rows: ${row_count}`);
        }
    }

    function dt_row_selected(e, dt, type, indexes)
    {
        update_bulk_actions_container(e, dt, type, indexes);
    }

    function dt_row_deselected(e, dt, type, indexes)
    {
        update_bulk_actions_container(e, dt, type, indexes);
    }


    const options = getDataTableOptions();
    options.columnDefs = [
        {
            target: 0,
            orderable: false,
            render: DataTable.render.select(),
        }
    ];

    options.select = { 
        style: 'multi',
        selector: 'td:first-child input[type="checkbox"]' 
    };
 

    const admin_table = $(".admin-data-table").first().DataTable(options);

    admin_table.on('select', function (e, dt, type, indexes) {
        if (type === 'row') {
            dt_row_selected(e, dt, type, indexes);
        }
    });

    admin_table.on('deselect', function (e, dt, type, indexes) {
        if (type === 'row') {
            dt_row_deselected(e, dt, type, indexes);
        }
    });
</script>