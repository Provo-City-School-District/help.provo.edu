<form method="get" action="" id="searchNotes">
    <div class="form-group">
        <label for="search_noteid">Note ID:</label>
        <input type="number" class="form-control" id="search_noteid" name="search_noteid" value="<?php echo htmlspecialchars($search_id); ?>">
    </div>

    <button type="submit" class="btn btn-primary">Search</button>

    <button type="reset" id="resetBtn" class="btn btn-secondary">Reset</button>
</form>