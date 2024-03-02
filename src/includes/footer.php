</main>
<footer id="mainFooter">
    <p>&copy; 2023-<?php echo date("Y"); ?> Provo City School District | <a href="https://provo.edu/helpdesk-feedback-form/">Help us Improve our Helpdesk</a></p>
</footer>
<div id="timeoutModal" style="display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
    <div style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%;">
        <h2>Inactivity Alert</h2>
        <p>Your session will expire soon due to inactivity.</p>
        <button onclick="dismiss_timeout_modal()">Dismiss</button>
        <button onclick="location.reload()">Reload Page</button>
    </div>
</div>
</div>
<script src="/includes/js/jquery-3.7.1.min.js" type="text/javascript"></script>
<script src="/includes/js/jquery-ui.min.js" type="text/javascript"></script>
<script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
<script src="/includes/js/dataTables-1.13.7/jquery.dataTables.min.js" type="text/javascript"></script>
<script src="/vendor/tinymce/tinymce/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    var userPref = '<?php echo isset($_SESSION['color_scheme']) ? $_SESSION['color_scheme'] : 'light'; ?>';
</script>
<script src="/includes/js/main.js?v=0.1.19" type="text/javascript"></script>
</body>

</html>