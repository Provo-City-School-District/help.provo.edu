<?php

//Checks current page in $_SERVER variable.
function endsWith($haystack, $needle)
{
    return substr($haystack, -strlen($needle)) === $needle;
}

// Function to check if the current URL matches any of the specified URLs
function isCurrentPage($urls)
{
    $currentPage = $_SERVER['REQUEST_URI'];
    foreach ($urls as $url) {
        if (strpos($currentPage, $url) !== false) {
            return true;
        }
    }
    return false;
}

// List of Ticket pages for which you want to display a ticket sub-menu
$ticketPages = array(
    '/tickets.php',
    '/edit_ticket.php',
    '/create_ticket.php',
    '/recent_tickets.php',
    '/search_tickets.php',
    '/ticket_history.php',
);

//limit characters in a string
function limitChars($string, $limit)
{
    if (strlen($string) > $limit) {
        $string = substr($string, 0, $limit) . '...';
    }
    return $string;
}



// process the data for our charts
function processQueryResult($query_result, $label_field)
{
    $count = [];

    while ($row = mysqli_fetch_assoc($query_result)) {
        $label = $row[$label_field];
        if ($label == null || $label == "")
            $label = "unassigned";

        if (!isset($count[$label]))
            $count[$label] = 1;
        else
            $count[$label]++;
    }

    asort($count);

    $processedData = [];
    foreach ($count as $name => $count) {
        $processedData[] = array("y" => $count, "label" => $name);
    }

    return $processedData;
}
