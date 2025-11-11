</main>

<footer class="footer">
    &copy; <?php echo date('Y'); ?> StockTrack Inc.
</footer>

<!-- jQuery (HTTP, v1.11.3) -->
<script src="http://code.jquery.com/jquery-1.11.3.min.js"></script>
<script>
/* Highlight current page in the top nav */
$(function () {
    $('.topbar nav a').each(function () {
        if (this.href === window.location.href) {
            $(this).addClass('active');
        }
    });
});
</script>
</body>
</html>
