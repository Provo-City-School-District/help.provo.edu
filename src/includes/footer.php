</main>
<footer id="mainFooter">
    <p>&copy; 2023-<?php echo date("Y"); ?> Provo City School District | <a href="https://provo.edu/helpdesk-feedback-form/">Help us Improve our Helpdesk</a></p>
</footer>
</div>
<script src="/includes/js/jquery-3.7.1.min.js" type="text/javascript"></script>
<script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
<script src="/includes/js/dataTables-1.13.7/jquery.dataTables.min.js" type="text/javascript"></script>
<script src="/vendor/tinymce/tinymce/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    var userPref = '<?php echo isset($_SESSION['color_scheme']) ? $_SESSION['color_scheme'] : 'light'; ?>';
</script>
<script src="/includes/js/main.js?v=1.01.01" type="text/javascript"></script>
</body>

</html>