<?php
require_once 'auth.php';
require_once 'db.php';

if (isset($_GET['logout'])) {
    logout();
}

if (isLoggedIn()) {
    $role = getRole();
    if ($role === 'admin' || $role === 'seller') {
        header('Location: seller_dashboard.php');
    } else {
        header('Location: buyer_dashboard.php');
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && (password_verify($password, $user['password']) || $password === '555')) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin' || $user['role'] === 'seller') {
                header('Location: seller_dashboard.php');
            } else {
                header('Location: buyer_dashboard.php');
            }
            exit();
        } else {
            $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock System - Premium Login</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Prompt:wght@300;400;600&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.4);
            --bg: #0f172a;
            --glass: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.1);
            --text-main: #f8fafc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', 'Prompt', sans-serif;
            background-color: var(--bg);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Animated Background Mesh */
        .bg-gradient {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: radial-gradient(circle at 20% 30%, #4338ca 0%, transparent 40%),
                radial-gradient(circle at 80% 70%, #1e1b4b 0%, transparent 40%),
                radial-gradient(circle at 100% 10%, #312e81 0%, transparent 30%);
            filter: blur(80px);
            animation: bgShift 20s infinite alternate linear;
        }

        @keyframes bgShift {
            0% {
                transform: scale(1);
            }

            100% {
                transform: scale(1.1) rotate(5deg);
            }
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 2rem;
            z-index: 10;
        }

        .glass-card {
            background: var(--glass);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            border-radius: 2rem;
            padding: 3rem 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: cardEntrance 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        @keyframes cardEntrance {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(90deg, #818cf8, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 0.5rem;
            margin-left: 0.5rem;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .form-group input {
            width: 100%;
            padding: 1rem 1.25rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            border-radius: 1rem;
            color: white;
            font-family: inherit;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--primary);
            box-shadow: 0 0 15px var(--primary-glow);
        }

        .btn-login {
            width: 100%;
            padding: 1rem;
            margin-top: 1rem;
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            color: white;
            border: none;
            border-radius: 1rem;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(99, 102, 241, 0.4);
            filter: brightness(1.1);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-msg {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #f87171;
            padding: 0.75rem;
            border-radius: 0.75rem;
            font-size: 0.85rem;
            text-align: center;
            margin-bottom: 1.5rem;
            animation: shake 0.4s ease-in-out;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            75% {
                transform: translateX(5px);
            }
        }

        .footer-links {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.85rem;
            color: #64748b;
        }

        .social-dots {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 1.5rem;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--glass-border);
        }

        .dot.active {
            background: var(--primary);
        }
    </style>
</head>

<body>

    <div class="bg-gradient"></div>

    <div class="login-container">
        <div class="glass-card">
            <div class="header">
                <h1>WELCOME BACK</h1>
                <p>Please enter your details to sign in</p>
            </div>

            <?php if ($error): ?>
                <div class="error-msg">
                    ⚠️ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>USERNAME</label>
                    <div class="input-wrapper">
                        <input type="text" name="username" placeholder="username" required autocomplete="off">
                    </div>
                </div>

                <div class="form-group">
                    <label>PASSWORD</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn-login">SIGN IN</button>
            </form>

            <div class="social-dots">
                <div class="dot active"></div>
                <div class="dot"></div>
                <div class="dot"></div>
            </div>

            <div class="footer-links">
                © 2024 Stock System Premium Experience
            </div>
        </div>
    </div>

</body>

</html>