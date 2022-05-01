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

// Define variables and initialize with empty values
$device_err = $device = $notes = "";
$note_err = $user = "";
$user = $_SESSION['id'];
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
  if(empty(trim($_POST["device"]))){
      $device_err = "Please enter a device.";
  }
  if(empty(trim($_POST["notes"]))){
      $note_err = "Please enter a note";
  }
    // Check input errors before inserting in database
  if(empty($device_err) && empty($note_err)){
    $notes = trim($_POST['notes']);
    $device = $_POST['device'];


    echo $notes.' '.$device.' '.$user;


    // Prepare an insert statements
    $sql = 'INSERT INTO repair '.
           '(notes, device, input_by) '.
           'VALUES ("'.$notes.'",'.$device.','.$user.')';
    echo '<br>';
    echo $sql;
    if (mysqli_query($link, $sql)) {
        //echo "record inserted successfully";
        header("location: index.php");
    } else {
        echo "Oops! Something went wrong. Please try again later.";
        echo "Error: " . $sql . "<br>" . $link->error;
    }
    mysqli_close($link);


  }

}
?>

<!-- Header -->
<?php include('header.php'); ?>

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">

    <label for="device">Device:</label>
    <input type="text" name="device" class="form-control <?php echo (!empty($device_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $device; ?>">
    <?php echo $device_err; ?>


    <label for="notes">Repair Notes:</label>
    <textarea id="notes" name="notes" rows="5" cols="70" class="form-control <?php echo (!empty($note_err)) ? 'is-invalid' : ''; ?>"><?php echo $notes; ?></textarea>
    <?php echo $note_err; ?>

    <input type="submit" class="btn btn-primary" value="Save Repair">
    <input type="reset" class="btn btn-secondary ml-2" value="Reset">
</form>


<?php include('footer.php'); ?>
