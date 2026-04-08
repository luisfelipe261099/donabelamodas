<?php
require_once 'config/db.php';
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "E-mail ou senha inválidos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dona Bela</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-page {
            background: linear-gradient(135deg, #1C1C26 0%, #2D2D3A 40%, #1C1C26 100%);
            position: relative;
        }
        .login-page::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(212, 163, 74, 0.07) 0%, transparent 70%);
            top: -150px;
            right: -150px;
        }
        .login-page::after {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(212, 163, 74, 0.05) 0%, transparent 70%);
            bottom: -100px;
            left: -100px;
        }
        .login-box {
            z-index: 1;
            position: relative;
        }
        .login-box .card {
            border: 1px solid rgba(212, 163, 74, 0.2);
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.5);
            border-radius: 20px;
        }
        .login-logo {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(212, 163, 74, 0.4);
            box-shadow: 0 0 40px rgba(212, 163, 74, 0.15);
            margin-bottom: 16px;
        }
        .login-title {
            font-family: 'Playfair Display', serif;
            font-weight: 800;
            color: #D4A34A;
            letter-spacing: 3px;
            font-size: 1.6rem;
        }
        .login-subtitle {
            color: rgba(255,255,255,0.4);
            font-size: 0.85rem;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .login-box .form-control {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(212, 163, 74, 0.15);
            border-radius: 12px;
            padding: 12px 16px;
            color: #fff;
            font-size: 0.95rem;
        }
        .login-box .form-control:focus {
            border-color: #D4A34A;
            box-shadow: 0 0 0 3px rgba(212, 163, 74, 0.2);
            background: rgba(212, 163, 74, 0.04);
        }
        .login-box .form-label {
            color: rgba(255,255,255,0.5);
            font-weight: 500;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn-login-submit {
            background: linear-gradient(135deg, #D4A34A 0%, #B8862E 100%);
            border: none;
            color: #1C1C26;
            font-weight: 700;
            border-radius: 12px;
            padding: 13px;
            font-size: 1rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: all 0.3s;
        }
        .btn-login-submit:hover {
            background: linear-gradient(135deg, #E8C373 0%, #D4A34A 100%);
            color: #1C1C26;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(212, 163, 74, 0.3);
        }
        .back-link {
            color: rgba(212, 163, 74, 0.5);
            text-decoration: none;
            font-size: 0.85rem;
            transition: color 0.3s;
        }
        .back-link:hover {
            color: #D4A34A;
        }
    </style>
</head>

<body class="login-page">
    <div class="login-box">
        <div class="card">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <img src="website/img/logo.png" class="login-logo" alt="Dona Bela">
                    <h3 class="login-title">DONA BELA</h3>
                    <p class="login-subtitle">Área Restrita</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">E-mail</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background:rgba(212,163,74,0.08);border:1px solid rgba(212,163,74,0.15);border-right:none;color:#D4A34A;border-radius:12px 0 0 12px;">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" name="email" class="form-control" required placeholder="seu@email.com" style="border-left:none;border-radius:0 12px 12px 0;">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Senha</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background:rgba(212,163,74,0.08);border:1px solid rgba(212,163,74,0.15);border-right:none;color:#D4A34A;border-radius:12px 0 0 12px;">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" name="password" class="form-control" required placeholder="••••••" style="border-left:none;border-radius:0 12px 12px 0;">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-login-submit w-100">
                        <i class="fas fa-sign-in-alt me-2"></i>Entrar
                    </button>
                </form>

                <div class="mt-4 text-center">
                    <a href="website/index.html" class="back-link">
                        <i class="fas fa-arrow-left me-1"></i> Voltar ao site
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>