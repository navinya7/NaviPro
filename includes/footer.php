<?php
// ============================================================
// FlashRide — HTML Footer Include
// ============================================================
?>
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<!-- App JS -->
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
<?php if (!empty($extraScripts)) echo $extraScripts; ?>
</body>
</html>
