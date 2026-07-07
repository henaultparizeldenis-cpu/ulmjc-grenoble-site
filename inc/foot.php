<?php /* Pied de page public ULMJC — délègue au partiel unique inc/site-footer.php,
   partagé avec les pages statiques converties en PHP. */ ?>
<?php include __DIR__ . '/site-footer.php'; ?>

<script src="js/main.js?v=<?= isset($v) ? e($v) : 'cms1' ?>"></script>
</body>
</html>
