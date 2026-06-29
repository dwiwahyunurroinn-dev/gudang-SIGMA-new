</main>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/lucide.min.js"></script>
<script>
  lucide.createIcons();
  function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('-translate-x-full');
    document.getElementById('overlay').classList.toggle('hidden');
  }
</script>
<?= $extra_js ?? '' ?>
</body>
</html>