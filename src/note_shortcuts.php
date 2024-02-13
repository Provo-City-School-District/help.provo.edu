<?php
include("header.php");
?>
<h2>Note Shortcuts</h1><br>
    <h4>Link to asset in vault: BC#[barcode number] (requires the leading zeroes)</h4><br>
    <p>Example: <a href="//vault.provo.edu/nac_edit.php?barcode=003001">BC#003001</a>, <a href="//vault.provo.edu/nac_edit.php?barcode=FVFZ624JLYWH">BC#FVFZ624JLYWH</a></p>
    <h4>Link to another ticket / work order: WO#[ticket number]</h4><br>
    <p>Example: <a href="/controllers/tickets/edit_ticket.php?id=50">WO#50</a></p><br>
    <!-- <h2>Markdown Guide</h2>
    <p>our editor does accept markdown syntax for formatting.</p>
    <a href="https://www.markdownguide.org/basic-syntax/">Markdown Syntax Guide</a> -->
    <form>
        <!-- maybe not hardcode return location (?)-->
        <button formaction="/profile.php">Return</button>
    </form>
    <?php
    include("footer.php");
    ?>