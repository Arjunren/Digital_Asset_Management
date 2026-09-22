  </main>
  <footer class="px-4 pb-4 text-muted small">&copy; <?= date('Y') ?> <?= e(setting('system_name', APP_NAME)) ?></footer>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
<?= $extraScripts ?? '' ?>
</body>
</html>

