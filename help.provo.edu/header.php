<?php
define("ROOT_DIR", "http://localhost/help.provo.edu/help.provo.edu/");
 ?>
<!doctype html>
<html lang="en">
<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
  <link rel="stylesheet" href="assets/css/mainStyle.css">
  <title>Help.provo.edu</title>
</head>
<body>
  <header>
    <h1>Help.provo.edu</h1>
  </header>
  <nav>
    <a href="<?php echo ROOT_DIR; ?>index.php">Login</a>
    <a href="<?php echo ROOT_DIR; ?>register.php">Sign Up</a>
    <a href="<?php echo ROOT_DIR; ?>dashboard.php">Dashboard</a>
  </nav>
