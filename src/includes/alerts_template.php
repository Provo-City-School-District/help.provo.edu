<?php
function display_ticket_alerts($alerts)
{
    echo '<div class="alerts_wrapper">';
    foreach ($alerts as $alert) {
?>
        <p class="<?= $alert["alert_level"] ?>">
            <a href="/controllers/tickets/edit_ticket.php?id=<?= $alert["ticket_id"] ?>">
                <?php if (basename($_SERVER['PHP_SELF']) == 'subordinate_tickets.php') {
                    echo  $alert["employee"] . ' - ';
                } ?>
                Ticket: <?= $alert["ticket_id"] . ' -' ?>
                <?= $alert["message"] ?>
            </a>

            <?php
            if (basename($_SERVER['PHP_SELF']) != 'subordinate_tickets.php') {
            ?>
                <a class="close-alert" href="/controllers/tickets/alert_delete.php?id=<?= $alert["id"] ?>">&times;</a>
            <?php
            }
            ?>

        </p>
<?php

    }
    echo '</div>';
}
