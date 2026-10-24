</main>
<?php
/**
 * KAKI HALAMAN ANAK
 * Variabel opsional: $skrip = ['belajar.js'] untuk skrip khusus halaman.
 */
$skrip = $skrip ?? [];
?>
<script src="<?= e(url('assets/js/suara.js')) ?>"></script>
<script src="<?= e(url('assets/js/confetti.js')) ?>"></script>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
<?php foreach ($skrip as $s): ?>
<script src="<?= e(url('assets/js/' . $s)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
