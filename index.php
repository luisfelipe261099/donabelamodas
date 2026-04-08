<?php
header("Location: website/index.html");
?>
<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="refresh" content="0;url=website/index.html">
    <script type="text/javascript">
        window.location.href = "website/index.html";
    </script>
    <title>Redirecionando...</title>
</head>

<body>
    Se você não for redirecionado automaticamente, <a href="website/index.html">clique aqui</a>.
</body>

</html>
<?php
exit;
?>