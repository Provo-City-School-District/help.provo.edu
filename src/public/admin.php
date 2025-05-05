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

$is_developer = get_user_setting(get_id_for_user($_SESSION['username']), "is_developer") ?? 0;

$loader = new \Twig\Loader\FilesystemLoader(from_root('/../views'));
$twig = new \Twig\Environment($loader, [
    'cache' => from_root('/../twig-cache'),
    'auto_reload' => true
]);

// Render the admin menu with the is_dev flag
$admin_menu = $twig->render('admin_menu.twig', [
    'is_developer' => $is_developer, // Pass the is_dev flag to Twig
]);

// Display the menu
echo $admin_menu;

// Check if an error message is set
if (isset($_SESSION['current_status'])) {
    $status_popup = new StatusPopup($_SESSION["current_status"], StatusPopupType::fromString($_SESSION["status_type"]));
    echo $status_popup;

    unset($_SESSION['current_status']);
    unset($_SESSION['status_type']);
}

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


echo '<h1>Admin</h1>';
echo '<h2>All Unassigned Tickets</h2>';

display_tickets_table($ticket_result, HelpDB::get(), "admin-data-table", true);

include("footer.php");
?>

<!-- Page Specific Elements and Scripts  -->
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