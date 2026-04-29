<script>
/* normal heartbeat */
setInterval(() => {
    fetch("/safcode/config/heartbeat.php", { method: "POST", keepalive: true });
}, 30000);

/* tab close detection */
window.addEventListener("beforeunload", function () {
    navigator.sendBeacon("/safcode/config/auto_logout.php");
});
</script>