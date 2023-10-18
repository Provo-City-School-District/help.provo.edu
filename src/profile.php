<?php
include("includes/header.php");
include("includes/helpdbconnect.php");

$user_query = "SELECT * FROM users WHERE username = '" . $_SESSION['username'] . "'";
$user_result = mysqli_query($database, $user_query);
// Check if the query was successful
if (!$user_result) {
    die("Query failed: " . mysqli_error($conn));
}
$user_data = mysqli_fetch_assoc($user_result);
print_r($user_data);
?>
<h1>Profile For <?= $user_data['firstname'] . ' ' . $user_data['lastname'] ?> (<span><?= $user_data['username'] ?></span>)</h1>
Potential features
-profile modifications
-see ticket stats?
-see ticket history
-timeclock info in the future

<?php include("includes/footer.php"); ?>