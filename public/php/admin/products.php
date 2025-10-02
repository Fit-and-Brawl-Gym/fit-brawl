<script>
fetch("../api/product_api.php")
  .then(res => res.json())
  .then(console.log);
</script>