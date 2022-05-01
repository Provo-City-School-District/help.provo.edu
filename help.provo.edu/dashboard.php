<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}
// Include config file
require_once "config/db.php";
$query_repairs = "SELECT * FROM helpprovoedu.repair";
$repairs_results = $link->query($query_repairs);
print_r($repairs_results);

?>

<!-- Header -->
<?php include('header.php'); ?>
    <h1 class="my-5">Hi, <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>. Welcome to our site.</h1>
    <p>
        <a href="reset-password.php" class="btn btn-warning">Reset Your Password</a>
        <a href="new-repair.php" class="btn btn-warning">New Repair</a>
        <a href="controllers/logout.php" class="btn btn-danger ml-3">Sign Out of Your Account</a>
    </p>
    <h2>Repairs</h2>
    <ul>
    <?php
    if ($repairs_results->num_rows > 0) {
      // output data of each row
      while($row = $repairs_results->fetch_assoc()) {
        echo "<li>id: " . $row["ID"]. " - Device: " . $row["device"]. " " . $row["notes"]. "</li>";
      }
    } else {
      echo "0 results";
    }

     ?>


    </ul>


<?php
$link->close();
include('footer.php');
?>
