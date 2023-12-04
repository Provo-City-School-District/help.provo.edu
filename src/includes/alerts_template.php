<?php
function display_ticket_alerts($alerts)
{
    echo '<div class="alerts_wrapper">';
    foreach ($alerts as $alert) {
?>
        <p>
            <a href="/controllers/tickets/edit_ticket.php?id=<?= $alert["ticket_id"] ?>">Ticket: <?= $alert["ticket_id"] ?></a>
            <?= $alert["message"] ?>
            <a href="/controllers/tickets/alert_delete.php?id=<?= $alert["id"] ?>"> Delete</a>
        </p>
<?php

    }
    echo '</div>';
}
