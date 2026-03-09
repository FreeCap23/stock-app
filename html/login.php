<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Stock App - Log In</title>
</head>
<body class="body-authentication">

    <div class="login-container">
        <h2>Welcome Back!</h2>

        <form action="index.php" method="POST">

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" name="action" value="login" class="submit-btn">Login</button>

        </form>
        <div style="text-align: center; margin-top: 15px; font-size: 14px; color: #555;">
            Don't have an account? <a href="register.php" style="color: #4CAF50; text-decoration: none; font-weight: bold;">Sign up here</a>
        </div>
        <a href="index.php" class="back-link">&larr; Back to Chart</a>
    </div>

</body>
</html>
