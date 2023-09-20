<?php
include("includes/header.php");

if ($_SESSION['permissions']['is_admin'] != 1) {
    // User is not an admin
    echo 'You do not have permission to view this page.';
    exit;
}
require_once('includes/helpdbconnect.php');
// Execute the SELECT query to retrieve all users from the users table
$query = "SELECT * FROM users";
$result = mysqli_query($database, $query);
// Check if the query was successful
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
?>
<h1>Admin</h1>
<h2>Users</h2>
<table>
    <tr>
        <th>Username</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Email</th>
        <th>Is Admin</th>
        <th>Employee ID</th>
        <th>Last Login</th>
    </tr>
    <tr>
        <?php // Display the results in an HTML table
        while ($row = mysqli_fetch_assoc($result)) {
        ?>
    <tr>
        <td><a href="controllers/users/manage_user.php?id=<?= $row['id'] ?>"><?= $row['username'] ?></a></td>
        <td><?= ucwords(strtolower($row['firstname'])) ?></td>
        <td><?= ucwords(strtolower($row['lastname'])) ?></td>
        <td><?= $row['email'] ?></td>
        <td><?= ($row['is_admin'] == 1 ? 'Yes' : 'No') ?></td>
        <td><?= $row['ifasid'] ?></td>
        <td><?= $row['last_login'] ?></td>
    </tr>
<?php
        }
?>
</tr>
</table>
<?php include("includes/footer.php"); ?>