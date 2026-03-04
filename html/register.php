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
        <h2>Welcome!</h2>

        <form action="create_account.php" method="POST">

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="submit-btn">Register</button>

        </form>
        <div style="text-align: center; margin-top: 15px; font-size: 14px; color: #555;">
            Already have an account? <a href="login.php" style="color: #4CAF50; text-decoration: none; font-weight: bold;">Log in here</a>
        </div>
        <a href="index.php" class="back-link">&larr; Back to Chart</a>
    </div>

</body>
</html>
