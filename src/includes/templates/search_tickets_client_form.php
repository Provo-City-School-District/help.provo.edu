<form method="get" action="" id="searchClient">
    <div class="form-group">
        <label for="search_id">Ticket ID:</label>
        <input type="number" class="form-control" id="search_id" name="search_id" value="<?php echo htmlspecialchars($search_id); ?>">
    </div>

    <button type="submit" class="btn btn-primary">Search</button>

    <button type="reset" id="resetBtn" class="btn btn-secondary">Reset</button>
</form>