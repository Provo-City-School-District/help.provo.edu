</main>
<footer id="mainFooter">
    <p>&copy; 2023-<?php echo date("Y"); ?> Provo City School District | <a href="https://provo.edu/helpdesk-feedback-form/">Help us Improve our Helpdesk</a></p>
</footer>
<div id="timeoutModal" style="display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
    <div style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%;">
        <h2>Inactivity Alert</h2>
        <p>This tab has been inactive for over 30 minutes. <strong>Your session may have ended.</strong></p>

        <button class="button" onclick="dismiss_timeout_modal()">Dismiss</button>
        <button class="button" onclick="location.reload()">Reload Page</button>
    </div>
</div>
</div>
<script src="/includes/js/external/jquery-3.7.1.min.js" type="text/javascript"></script>
<script src="/includes/js/external/jquery-ui.min.js" type="text/javascript"></script>
<script src="/includes/js/external/lightbox.js"></script>
<script src="/includes/js/external/dataTables/datatables.min.js" type="text/javascript"></script>
<script src="/includes/js/external/tinymce/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    var userPref = '<?php echo isset($_SESSION['color_scheme']) ? $_SESSION['color_scheme'] : 'light'; ?>';
    var ticketLimit = '<?php echo isset($_SESSION['ticket_limit']) ? $_SESSION['ticket_limit'] : 10; ?>';
</script>
<?php if (basename($_SERVER['PHP_SELF']) != 'index.php') : ?>
    <script src="/includes/js/inactiveModal.js?v=<?= $app_version; ?>" type="text/javascript"></script>
<?php endif; ?>
<!-- <script src="/includes/js/clientSearch.js?v=1.0.0" type="text/javascript"></script> -->
<script src="/includes/js/tinyMCE-conf.js?v=<?= $app_version; ?>" type="text/javascript"></script>
<script src="/includes/js/external/tinymce-prism.js?v=<?= $app_version; ?>" type="text/javascript"></script>
<script src="/includes/js/dataTables-conf.js?v=<?= $app_version; ?>" type="text/javascript"></script>
<script src="/includes/js/global.js?v=<?= $app_version; ?>" type="text/javascript"></script>
</body>

</html>