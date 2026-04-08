<?php
header("Location: website/index.php");
?>
<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="refresh" content="0;url=website/index.php">
    <script type="text/javascript">
        window.location.href = "website/index.php";
    </script>
    <title>Redirecionando...</title>
</head>

<body>
    Se você não for redirecionado automaticamente, <a href="website/index.php">clique aqui</a>.
</body>

</html>
<?php
exit;
?>