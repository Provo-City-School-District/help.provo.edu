</main>
<footer id="mainFooter">
    <p>&copy; 2023-<?php echo date("Y"); ?> Provo City School District | <a href="https://provo.edu/helpdesk-feedback-form/">Help us Improve our Helpdesk</a></p>
</footer>
<div id="timeoutModal">
    <div>
        <h2 id="modalTitle">Alert</h2>
        <p id="modalMessage"></p>
        <button id="modalButton"></button>
        <button onclick="dismiss_timeout_modal()">Dismiss</button>
    </div>
</div>
<script src="/includes/js/jquery-3.7.1.min.js" type="text/javascript"></script>
<script src="/includes/js/jquery-ui.min.js" type="text/javascript"></script>
<script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
<script src="/includes/js/dataTables-1.13.7/jquery.dataTables.min.js" type="text/javascript"></script>
<script src="/vendor/tinymce/tinymce/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    var userPref = '<?php echo isset($_SESSION['color_scheme']) ? $_SESSION['color_scheme'] : 'light'; ?>';
    var loginTimeFromPHP = "<?php echo $_SESSION['last_login']; ?>";
</script>
<?php if (basename($_SERVER['PHP_SELF']) != 'index.php') : ?>
    <script src="/includes/js/inactiveModal.js?v=0.1.0" type="text/javascript"></script>
<?php endif; ?>

<script src="/includes/js/main.js?v=0.1.22" type="text/javascript"></script>
</body>

</html>