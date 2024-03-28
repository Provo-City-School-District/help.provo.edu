<form method="get" action="" id="searchTickets">
    <div class="form-group">
        <label for="search_id">Ticket ID:</label>
        <input type="number" class="form-control" id="search_id" name="search_id" value="<?php echo htmlspecialchars($search_id); ?>">
    </div>
    <div class="form-group">
        <label for="search_name">Keywords:</label>
        <input type="text" class="form-control" id="search_name" name="search_name" value="<?php echo htmlspecialchars($search_name); ?>">
    </div>
    <div class="form-group">
        <label for="priority">Priority:</label>
        <select id="priority" name="priority">
            <option value="" selected></option>

            <option value="1" <?= ($search_priority == '1') ? ' selected' : '' ?>>Critical</option>
            <option value="3" <?= ($search_priority == '3') ? ' selected' : '' ?>>Urgent</option>
            <option value="5" <?= ($search_priority == '5') ? ' selected' : '' ?>>High</option>
            <option value="10" <?= ($search_priority == '10') ? ' selected' : '' ?>>Standard</option>
            <option value="15" <?= ($search_priority == '15') ? ' selected' : '' ?>>Client Response</option>
            <option value="30" <?= ($search_priority == '30') ? ' selected' : '' ?>>Project</option>
            <option value="60" <?= ($search_priority == '60') ? ' selected' : '' ?>>Meeting Support</option>
        </select>
    </div>
    <div class="form-group">
        <label for="search_location">Department/Location:</label>
        <!-- <input type="text" class="form-control" id="search_location" name="search_location" value="<?php echo htmlspecialchars($search_location); ?>"> -->
        <select id="search_location" name="search_location">
            <option value="" selected></option>
            <?php
            // Query the locations table to get the departments
            $department_query = "SELECT * FROM locations WHERE is_department = TRUE ORDER BY location_name ASC";
            $department_result = $database->execute_query($department_query);

            // Create a "Department" optgroup and create an option for each department
            echo '<optgroup label="Department">';
            while ($locations = mysqli_fetch_assoc($department_result)) {
                $selected = '';
                if (isset($search_location) && $locations['sitenumber'] == $search_location) {
                    $selected = 'selected';
                }
                echo '<option value="' . $locations['sitenumber'] . '" ' . $selected . '>' . $locations['location_name'] . '</option>';
            }
            echo '</optgroup>';

            // Query the locations table to get the locations
            $location_query = "SELECT * FROM locations WHERE is_department = FALSE ORDER BY location_name ASC";
            $location_result = $database->execute_query($location_query);

            // Create a "Location" optgroup and create an option for each location
            echo '<optgroup label="Location">';
            while ($locations = mysqli_fetch_assoc($location_result)) {
                $selected = '';
                if (isset($search_location) && $locations['sitenumber'] == $search_location) {
                    $selected = 'selected';
                }
                echo '<option value="' . $locations['sitenumber'] . '" ' . $selected . '>' . $locations['location_name'] . '</option>';
            }
            echo '</optgroup>';
            ?>
        </select>
    </div>
    <?= $search_employee ?>
    <div class="form-group">
        <label for="search_employee">Tech:</label>
        <!-- <input type="text" class="form-control" id="search_employee" name="search_employee" value="<?php echo htmlspecialchars($search_employee); ?>"> -->
        <select id="search_employee" name="search_employee">
            <option value="" selected></option>
            <option value="Unassigned" <?= $search_employee == 'helpdesk' ? 'selected' : '' ?>>Unassigned</option>
            <?php foreach ($techusernames as $techuser) : ?>
                <option value="<?= $techuser ?>" <?= $search_employee === $techuser ? 'selected' : '' ?>><?= $techuser ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="search_client">Client:</label>
        <input type="text" class="form-control" id="search_client" name="search_client" value="<?php echo htmlspecialchars($search_client); ?>">
    </div>
    <div class="form-group">
        <label for="search_status">Status:</label>
        <select id="status" name="search_status">
            <option value="" selected></option>
            <option value="open" <?= ($search_status == 'open' || $search_status == 1) ? ' selected' : '' ?>>Open</option>
            <option value="closed" <?= ($search_status == 'closed' || $search_status == 3) ? ' selected' : '' ?>>Closed</option>
            <option value="resolved" <?= ($search_status == 'resolved' || $search_status == 5) ? ' selected' : '' ?>>Resolved</option>
            <option value="pending" <?= ($search_status == 'pending' || $search_status == 7) ? ' selected' : '' ?>>Pending</option>
            <option value="vendor" <?= ($search_status == 'vendor' || $search_status == 12) ? ' selected' : '' ?>>Vendor</option>
            <option value="maintenance" <?= ($search_status == 'maintenance' || $search_status == 11) ? ' selected' : '' ?>>Maintenance</option>
        </select>
    </div>

    <div>

        <label for="start_date">Start Date:</label>
        <input type="date" id="start_date" name="start_date" value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>">

        <label for="end_date">End Date:</label>
        <input type="date" id="end_date" name="end_date" value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>">

        <input type="checkbox" name="dates[]" value="created" <?php echo (isset($_GET['dates']) && in_array('created', $_GET['dates'])) ? 'checked' : ''; ?>> Created Date
        <input type="checkbox" name="dates[]" value="last_updated" <?php echo (isset($_GET['dates']) && in_array('last_updated', $_GET['dates'])) ? 'checked' : ''; ?>> Last Updated
        <input type="checkbox" name="dates[]" value="due_date" <?php echo (isset($_GET['dates']) && in_array('due_date', $_GET['dates'])) ? 'checked' : ''; ?>> Due Date
    </div>
    <div>
        <label for="search_name">Search Archived Tickets:</label>
        <input type="checkbox" id="search_archived" name="search_archived" value="1" <?php echo (isset($_GET['search_archived']) == 1) ? 'checked' : ''; ?>>
    </div>
    
    <button type="submit" class="btn btn-primary">Search</button>

    <button type="reset" id="resetBtn" class="btn btn-secondary">Reset</button>
</form>