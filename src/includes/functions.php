<?php 

//Checks current page in $_SERVER variable.
function endsWith($haystack, $needle)
{
    return substr($haystack, -strlen($needle)) === $needle;
}

// Function to check if the current page matches a given URL
function isActivePage($current_page, $page_url)
{
    return ($current_page === $page_url) ? 'active' : '';
}

// Function to check if the current URL matches any of the specified URLs
function isCurrentPage($urls) {
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
);

//limit characters in a string
function limitChars($string, $limit) {
    if (strlen($string) > $limit) {
        $string = substr($string, 0, $limit) . '...';
    }
    return $string;
}


?>