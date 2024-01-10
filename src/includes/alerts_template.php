<?php
function display_ticket_alerts($alerts)
{
    echo '<div class="alerts_wrapper">';
    foreach ($alerts as $alert) {
?>
        <p class="<?= $alert["alert_level"] ?>">
            <a href="/controllers/tickets/edit_ticket.php?id=<?= $alert["ticket_id"] ?>">Ticket: <?= $alert["ticket_id"].' -' ?>
            <?= $alert["message"] ?>
            </a>
            <!-- <a href="/controllers/tickets/alert_delete.php?id=<?= $alert["id"] ?>"> Delete</a> -->
            <a class="close-alert" href="/controllers/tickets/alert_delete.php?id=<?= $alert["id"] ?>">&times;</a>
        </p>
<?php

    }
    echo '</div>';
}
