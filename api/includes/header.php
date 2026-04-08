<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) != 'login.php') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dona Bela - Sistema PDV</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>

<body>

    <?php if (basename($_SERVER['PHP_SELF']) != 'login.php'): ?>
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg navbar-dark mb-4 rounded">
                <div class="container-fluid">
                    <button class="btn btn-link text-white d-md-none" type="button" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <span class="navbar-brand ms-2" style="font-family:'Playfair Display',serif;color:#D4A34A !important;letter-spacing:1px;">
                        <?php
                        $page = basename($_SERVER['PHP_SELF'], ".php");
                        echo ucfirst($page == 'index' ? 'Dashboard' : str_replace('_', ' ', $page));
                        ?>
                    </span>

                    <div class="collapse navbar-collapse justify-content-end">
                        <ul class="navbar-nav">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                                    data-bs-toggle="dropdown">
                                    <i class="fas fa-user-circle me-1"></i>
                                    <?php echo $_SESSION['user_name'] ?? 'Usuário'; ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">
                                    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>
                                            Sair</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
        <?php endif; ?>