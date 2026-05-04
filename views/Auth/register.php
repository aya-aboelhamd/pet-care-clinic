<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petlor - Create Account</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body, html {
            height: 100%;
            width: 100%;
            display: flex;
        }

        .container {
            display: flex;
            width: 100%;
            height: 100vh;
        }

        .left-panel {
            background-color: #589A64;
            width: 50%;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 4rem;
            position: relative;
        }

        .logo {
            position: absolute;
            top: 2.5rem;
            left: 3rem;
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: -0.5px;
        }

        .logo i {
            color: black;
            font-size: 1.8rem;
        }

        .hero-content h1 {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 1rem;
            letter-spacing: -1px;
        }

        .hero-content p {
            font-size: 1.1rem;
            line-height: 1.5;
            color: rgba(255, 255, 255, 0.9);
            max-width: 85%;
        }

        .footer-text {
            position: absolute;
            bottom: 2.5rem;
            left: 3rem;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.8);
        }

        .right-panel {
            background-color: #F8FAF8;
            width: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-card {
            background: white;
            padding: 2.5rem 3rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 480px;
        }

        .form-card h2 {
            font-size: 1.8rem;
            color: #1A1A1A;
            margin-bottom: 0.3rem;
        }

        .form-card .subtitle {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 2rem;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"],
        select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #E0E0E0;
            border-radius: 8px;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s;
            margin-bottom: 1.2rem;
        }

        input:focus, select:focus {
            border-color: #589A64;
        }

        .submit-btn {
            width: 100%;
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 0.85rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            margin-bottom: 1.5rem;
            margin-top: 0.5rem;
        }

        .submit-btn:hover {
            background-color: #45a049;
        }

        /* --- تم إضافة تنسيق اللينك هنا --- */
        .login-link {
            text-align: center;
            font-size: 0.9rem;
            color: #666;
        }

        .login-link a {
            color: #589A64;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }
        /* ---------------------------------- */

        @media (max-width: 900px) {
            .container {
                flex-direction: column;
            }
            .left-panel, .right-panel {
                width: 100%;
                height: auto;
            }
            .left-panel {
                padding: 4rem 2rem;
                align-items: center;
                text-align: center;
            }
            .logo, .footer-text {
                position: static;
                margin-bottom: 1rem;
            }
            .logo { justify-content: center; }
            .hero-content p { max-width: 100%; }
            .right-panel { padding: 2rem 1rem; }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="left-panel">
            <div class="logo">
                <i class="fa-solid fa-paw"></i> Petlor
            </div>
            
            <div class="hero-content">
                <h1>Join the Petlor family.</h1>
                <p>Create your free account and start caring smarter today.</p>
            </div>

            <div class="footer-text">
                © Petlor — Smart Pet Care
            </div>
        </div>

        <div class="right-panel">
            <div class="form-card">
                <h2>Create your account</h2>
                <p class="subtitle">It only takes a minute.</p>

            <form action="../../controllers/AuthController.php" method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="tel" name="phone" placeholder="Phone Number" required>
                <input type="password" name="password" placeholder="Password" required>
                
                <select name="roleid" required>
                    <option value="" disabled selected>Select your role...</option>
                    <option value="2">Pet Owner</option>
                    <option value="3">Veterinarian</option>
                    <option value="4">Service Provider</option>
                </select>
                
                <button class="submit-btn" type="submit" name="register">Register</button>
            </form>

            <!-- --- تم إضافة اللينك هنا --- -->
            <div class="login-link">
                Already have an account? <a href="login.php">Log in</a>
            </div>
            <!-- ----------------------------- -->

            </div>
        </div>
    </div>

</body>
</html>

