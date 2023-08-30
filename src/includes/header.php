<?php
$root_domain = getenv('ROOTDOMAIN');
$is_logged_in = false;
// Checks if Session exists, if not starts one.
if (!session_id()) {
    session_start();
}
//Checks current page in $_SERVER variable.
function endsWith($haystack, $needle)
{
    return substr($haystack, -strlen($needle)) === $needle;
}

if (isset($_SESSION['username'])) {
    $is_logged_in = true;
} elseif (!endsWith($_SERVER['PHP_SELF'], 'index.php')) {
    header('Location: index.php');
    exit();
}

// Get the current page URL
$current_page = $_SERVER['REQUEST_URI'];
// Define the URLs of the pages you want to highlight
$home_page_url = '/home.php';
$about_page_url = '/tickets.php';

// Function to check if the current page matches a given URL
function isActivePage($current_page, $page_url)
{
    return ($current_page === $page_url) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>| Help For Provo City School District</title>
    <link rel="stylesheet" href="<?= $root_domain; ?>/includes/css/main.css">
</head>

<body>
    <div id="wrapper">
        <header id="mainHeader">
            <a href="home.php">
                <img src="includes/img/pcsd-logo-website-header-160w.png" alt="Provo City School District Logo" />
            </a>
            <?php
            if ($is_logged_in) {
            ?>
                <nav id="headerNav">
                    <a class="<?php echo isActivePage($current_page, $home_page_url); ?>" href="home.php">Home</a>
                    <a class="<?php echo isActivePage($current_page, $about_page_url); ?>" href="tickets.php">Tickets</a>
                    <a href="controllers/logout.php">Logout</a>
                </nav>
            <?php
            }
            ?>
        </header>
        <main>