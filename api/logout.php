<?php
// api/logout.php
require_once '../db.php';
session_destroy();
?>
<!DOCTYPE html>
<html>
<head><script>
sessionStorage.removeItem('user');
window.location.href = '../index.html';
</script></head>
</html>