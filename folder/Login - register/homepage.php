<?php
session_start();
// Redirect if already logged in
if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Smart ID</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #2E7D32;
            --primary-dark: #1B5E20;
            --primary-light: #4CAF50;
            --primary-lighter: #66BB6A;
            --primary-pale: #E8F5E9;
            --white: #FFFFFF;
            --gray-100: #F5F5F5;
            --gray-200: #EEEEEE;
            --gray-600: #757575;
            --gray-800: #424242;
            --gray-900: #212121;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #1B5E20 0%, #2E7D32 30%, #43A047 60%, #66BB6A 100%);
            overflow: hidden;
            position: relative;
        }

        /* Animated background circles */
        .bg-circle {
            position: absolute;
            border-radius: 50%;
            opacity: 0.08;
            background: white;
            animation: float 20s infinite ease-in-out;
        }

        .bg-circle:nth-child(1) {
            width: 400px;
            height: 400px;
            top: -100px;
            left: -100px;
            animation-delay: 0s;
        }

        .bg-circle:nth-child(2) {
            width: 300px;
            height: 300px;
            bottom: -80px;
            right: -80px;
            animation-delay: -5s;
        }

        .bg-circle:nth-child(3) {
            width: 200px;
            height: 200px;
            top: 50%;
            right: 10%;
            animation-delay: -10s;
        }

        .bg-circle:nth-child(4) {
            width: 150px;
            height: 150px;
            bottom: 20%;
            left: 15%;
            animation-delay: -15s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            25% { transform: translateY(-30px) scale(1.05); }
            50% { transform: translateY(0) scale(1); }
            75% { transform: translateY(30px) scale(0.95); }
        }

        /* Main container */
        .homepage-container {
            text-align: center;
            padding: 3rem 2rem;
            max-width: 600px;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        /* Logo */
        .logo-wrapper {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: white;
            margin: 0 auto 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.25), 0 0 0 8px rgba(255, 255, 255, 0.15);
            overflow: hidden;
            animation: logoEntrance 0.8s ease-out;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .logo-wrapper:hover {
            transform: scale(1.05);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 0 10px rgba(255, 255, 255, 0.2);
        }

        .logo-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        @keyframes logoEntrance {
            from {
                opacity: 0;
                transform: scale(0.5) translateY(-30px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        /* Welcome text */
        .welcome-title {
            font-size: 2.8rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.75rem;
            text-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            animation: fadeInUp 0.8s ease-out 0.2s both;
            line-height: 1.2;
        }

        .welcome-subtitle {
            font-size: 1.15rem;
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 3rem;
            font-weight: 300;
            line-height: 1.6;
            animation: fadeInUp 0.8s ease-out 0.4s both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(25px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Get Started button */
        .btn-get-started {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 1.1rem 3.5rem;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-dark);
            background: white;
            border: none;
            border-radius: 60px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
            text-transform: uppercase;
            letter-spacing: 1px;
            animation: fadeInUp 0.8s ease-out 0.6s both;
        }

        .btn-get-started:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
            background: var(--primary-pale);
        }

        .btn-get-started:active {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        .btn-get-started i {
            font-size: 1.3rem;
            transition: transform 0.3s ease;
        }

        .btn-get-started:hover i {
            transform: translateX(4px);
        }

        /* Auth options (hidden initially) */
        .auth-options {
            display: none;
            gap: 1.25rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 0.5s ease-out;
        }

        .auth-options.show {
            display: flex;
        }

        .btn-auth {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 1.1rem 2.5rem;
            font-size: 1.1rem;
            font-weight: 700;
            border: none;
            border-radius: 60px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.75px;
            min-width: 200px;
        }

        .btn-signup {
            color: var(--primary-dark);
            background: white;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
        }

        .btn-signup:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
            background: var(--primary-pale);
            color: var(--primary-dark);
        }

        .btn-signin {
            color: white;
            background: rgba(255, 255, 255, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(10px);
        }

        .btn-signin:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.25);
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.8);
            color: white;
        }

        .btn-auth:active {
            transform: translateY(-1px);
        }

        .btn-auth i {
            font-size: 1.2rem;
        }

        /* Back link */
        .back-link {
            display: none;
            margin-top: 1.5rem;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.95rem;
            cursor: pointer;
            transition: color 0.3s ease;
            background: none;
            border: none;
            font-family: 'Poppins', sans-serif;
            animation: fadeInUp 0.5s ease-out 0.1s both;
        }

        .back-link.show {
            display: inline-block;
        }

        .back-link:hover {
            color: white;
        }

        .back-link i {
            margin-right: 0.35rem;
        }

        /* Responsive */
        @media (max-width: 576px) {
            .logo-wrapper {
                width: 140px;
                height: 140px;
                margin-bottom: 2rem;
            }

            .welcome-title {
                font-size: 2rem;
            }

            .welcome-subtitle {
                font-size: 1rem;
                margin-bottom: 2.5rem;
            }

            .btn-get-started {
                padding: 1rem 2.5rem;
                font-size: 1.05rem;
            }

            .btn-auth {
                min-width: 170px;
                padding: 1rem 2rem;
                font-size: 1rem;
            }

            .auth-options {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <!-- Animated background -->
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>

    <div class="homepage-container">
        <!-- Logo -->
        <div class="logo-wrapper">
            <img src="logo.png" alt="My Smart ID Logo">
        </div>

        <!-- Welcome text -->
        <h1 class="welcome-title">Welcome to My Smart ID</h1>
        <p class="welcome-subtitle">Your all-in-one student ID management system.<br>Efficient, secure, and easy to use.</p>

        <!-- Get Started button -->
        <button class="btn-get-started" id="getStartedBtn">
            Get Started <i class="bi bi-arrow-right"></i>
        </button>

        <!-- Auth options (hidden initially) -->
        <div class="auth-options" id="authOptions">
            <a href="registration.php" class="btn-auth btn-signup">
                <i class="bi bi-person-plus-fill"></i> Sign Up
            </a>
            <a href="Login.php" class="btn-auth btn-signin">
                <i class="bi bi-box-arrow-in-right"></i> Sign In
            </a>
        </div>

        <button class="back-link" id="backLink">
            <i class="bi bi-arrow-left"></i> Back
        </button>
    </div>

    <script>
        const getStartedBtn = document.getElementById('getStartedBtn');
        const authOptions = document.getElementById('authOptions');
        const backLink = document.getElementById('backLink');

        getStartedBtn.addEventListener('click', function() {
            // Fade out Get Started button
            this.style.opacity = '0';
            this.style.transform = 'translateY(10px)';
            this.style.transition = 'all 0.3s ease';

            setTimeout(() => {
                this.style.display = 'none';
                // Show auth options
                authOptions.classList.add('show');
                backLink.classList.add('show');
            }, 300);
        });

        backLink.addEventListener('click', function() {
            // Hide auth options
            authOptions.style.opacity = '0';
            authOptions.style.transform = 'translateY(10px)';
            authOptions.style.transition = 'all 0.3s ease';
            backLink.style.opacity = '0';

            setTimeout(() => {
                authOptions.classList.remove('show');
                authOptions.style.opacity = '';
                authOptions.style.transform = '';
                authOptions.style.transition = '';
                backLink.classList.remove('show');
                backLink.style.opacity = '';

                // Show Get Started button again
                getStartedBtn.style.display = '';
                setTimeout(() => {
                    getStartedBtn.style.opacity = '1';
                    getStartedBtn.style.transform = 'translateY(0)';
                }, 10);
            }, 300);
        });
    </script>
</body>
</html>
