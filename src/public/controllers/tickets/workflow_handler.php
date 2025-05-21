<?php
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    require_once('helpdbconnect.php');

    session_start();

    $ticket_id = intval($_POST['ticket_id'] ?? 0);
    $user_department = $_SESSION['department'] ?? null;
    $username = $_SESSION['username'] ?? null;




    // Add workflow step
    if (isset($_POST['add_workflow_step'])) {
        $step_name = htmlspecialchars(trim($_POST['step_name']), ENT_QUOTES, 'UTF-8');
        $assigned_user = htmlspecialchars(trim($_POST['assigned_user']), ENT_QUOTES, 'UTF-8');
        $step_order = intval($_POST['step_order']);

        // Increment step_order for all steps at or after the new step's order
        HelpDB::get()->execute_query(
            "UPDATE ticket_workflow_steps SET step_order = step_order + 1 WHERE ticket_id = ? AND step_order >= ?",
            [$ticket_id, $step_order]
        );

        // Now insert the new step
        HelpDB::get()->execute_query(
            "INSERT INTO ticket_workflow_steps (ticket_id, step_order, step_name, assigned_user) VALUES (?, ?, ?, ?)",
            [$ticket_id, $step_order, $step_name, $assigned_user]
        );

        // log action into ticket history  
        HelpDB::get()->execute_query(
            "INSERT INTO ticket_logs (ticket_id,department_id,user_id, field_name, new_value, created_at) VALUES (?,?,?,?,?, NOW())",
            [$ticket_id, $user_department, $username, 'workflow', "Added workflow step: $step_name"]
        );

        header("Location: edit_ticket.php?id=$ticket_id");
        exit;
    }




    // Workflow approval
    if (isset($_POST['approve_step_id'])) {
        $step_id = intval($_POST['approve_step_id']);
        // Approve this step
        HelpDB::get()->execute_query(
            "UPDATE ticket_workflow_steps SET status = 'approved', approved_at = NOW() WHERE id = ?",
            [$step_id]
        );
        // Auto-assign next step's user to ticket
        $next_step_res = HelpDB::get()->execute_query(
            "SELECT * FROM ticket_workflow_steps WHERE ticket_id = ? AND status = 'pending' ORDER BY step_order ASC LIMIT 1",
            [$ticket_id]
        );
        if ($next_step_res && $next_step = $next_step_res->fetch_assoc()) {
            HelpDB::get()->execute_query(
                "UPDATE tickets SET employee = ? WHERE id = ?",
                [$next_step['assigned_user'], $ticket_id]
            );
        }
        // log action into ticket history  
        HelpDB::get()->execute_query(
            "INSERT INTO ticket_logs (ticket_id,department_id,user_id, field_name, new_value, created_at) VALUES (?,?,?,?,?, NOW())",
            [$ticket_id, $user_department, $username, 'workflow', "Approved workflow step: {$next_step['step_name']}"]
        );
        header("Location: edit_ticket.php?id=$ticket_id");
        exit;
    }




    // Delete workflow step
    if (isset($_POST['delete_step_id'])) {
        $step_id = intval($_POST['delete_step_id']);
        $old_value = htmlspecialchars(trim($_POST['old_value']), ENT_QUOTES, 'UTF-8');

        // Get the ticket_id and step_order of the step being deleted
        $step_info_res = HelpDB::get()->execute_query(
            "SELECT ticket_id, step_order FROM ticket_workflow_steps WHERE id = ?",
            [$step_id]
        );
        $step_info = $step_info_res ? $step_info_res->fetch_assoc() : null;
        if ($step_info) {
            $ticket_id = intval($step_info['ticket_id']);
            $deleted_order = intval($step_info['step_order']);

            // Delete the step
            HelpDB::get()->execute_query(
                "DELETE FROM ticket_workflow_steps WHERE id = ?",
                [$step_id]
            );

            // Decrement step_order for all following steps
            HelpDB::get()->execute_query(
                "UPDATE ticket_workflow_steps SET step_order = step_order - 1 WHERE ticket_id = ? AND step_order > ?",
                [$ticket_id, $deleted_order]
            );

            // log action into ticket history  
            HelpDB::get()->execute_query(
                "INSERT INTO ticket_logs (ticket_id,department_id,user_id, field_name,old_value ,new_value, created_at) VALUES (?,?,?,?,?,?, NOW())",
                [$ticket_id, $user_department, $username, 'workflow', $old_value, "Deleted workflow step"]
            );
        }
        header("Location: edit_ticket.php?id=$ticket_id");
        exit;
    }



    // Update workflow step
    if (isset($_POST['update_step_id'])) {
        $step_id = intval($_POST['update_step_id']);
        $step_name = htmlspecialchars(trim($_POST['step_name']), ENT_QUOTES, 'UTF-8');
        $assigned_user = htmlspecialchars(trim($_POST['assigned_user']), ENT_QUOTES, 'UTF-8');
        $new_order = intval($_POST['step_order']);

        // Get current step_order and ticket_id for this step
        $current_step_res = HelpDB::get()->execute_query(
            "SELECT step_order, ticket_id, step_name, assigned_user FROM ticket_workflow_steps WHERE id = ?",
            [$step_id]
        );
        $current_step = $current_step_res ? $current_step_res->fetch_assoc() : null;
        if (!$current_step) {
            header("Location: edit_ticket.php?id=$ticket_id");
            exit;
        }
        $old_order = intval($current_step['step_order']);
        $ticket_id = intval($current_step['ticket_id']);
        $old_step_name = $current_step['step_name'];
        $old_assigned_user = $current_step['assigned_user'];

        if ($new_order < $old_order) {
            // Moving up: increment all steps between new_order and old_order - 1
            HelpDB::get()->execute_query(
                "UPDATE ticket_workflow_steps SET step_order = step_order + 1 
             WHERE ticket_id = ? AND step_order >= ? AND step_order < ? AND id != ?",
                [$ticket_id, $new_order, $old_order, $step_id]
            );
        } elseif ($new_order > $old_order) {
            // Moving down: decrement all steps between old_order + 1 and new_order
            HelpDB::get()->execute_query(
                "UPDATE ticket_workflow_steps SET step_order = step_order - 1 
             WHERE ticket_id = ? AND step_order <= ? AND step_order > ? AND id != ?",
                [$ticket_id, $new_order, $old_order, $step_id]
            );
        }

        // Update the moved step
        HelpDB::get()->execute_query(
            "UPDATE ticket_workflow_steps SET step_name = ?, assigned_user = ?, step_order = ? WHERE id = ?",
            [$step_name, $assigned_user, $new_order, $step_id]
        );

        // log action into ticket history with old and new values
        $old_value = "Name: $old_step_name, Assigned: $old_assigned_user, Order: $old_order";
        $new_value = "Name: $step_name, Assigned: $assigned_user, Order: $new_order";
        HelpDB::get()->execute_query(
            "INSERT INTO ticket_logs (ticket_id,department_id,user_id, field_name, old_value, new_value, created_at) VALUES (?,?,?,?,?,?, NOW())",
            [$ticket_id, $user_department, $username, 'workflow', $old_value, $new_value]
        );
        header("Location: edit_ticket.php?id=$ticket_id");
        exit;
    }





    // incomplete workflow step
    if (isset($_POST['uncomplete_step_id'])) {
        $step_id = intval($_POST['uncomplete_step_id']);
        $ticket_id = intval($_POST['ticket_id']);

        // Set step status back to pending and clear approved_at
        HelpDB::get()->execute_query(
            "UPDATE ticket_workflow_steps SET status = 'pending', approved_at = NULL WHERE id = ?",
            [$step_id]
        );

        // Reassign the ticket to this user (the assigned_user for this step)
        $step_res = HelpDB::get()->execute_query(
            "SELECT assigned_user, step_name FROM ticket_workflow_steps WHERE id = ?",
            [$step_id]
        );
        $step = $step_res ? $step_res->fetch_assoc() : null;
        if ($step) {
            HelpDB::get()->execute_query(
                "UPDATE tickets SET employee = ? WHERE id = ?",
                [$step['assigned_user'], $ticket_id]
            );
            // Log action
            HelpDB::get()->execute_query(
                "INSERT INTO ticket_logs (ticket_id,department_id,user_id, field_name, new_value, created_at) VALUES (?,?,?,?,?, NOW())",
                [$ticket_id, $user_department, $username, 'workflow', "Uncompleted workflow step: {$step['step_name']}"]
            );
        }

        header("Location: edit_ticket.php?id=$ticket_id");
        exit;
    }
}
