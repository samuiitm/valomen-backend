<main class="main-login">
    <form class="login-form" method="POST" action="index.php?page=login">
        <h1>Login to Valomen.gg</h1>
        <p>Continue earning points, making pick’ems, and proving who really runs the game.</p>

        <?php if (!empty($expired)): ?>
            <p class="error-message">
                Your session has expired after 40 minutes of inactivity. Please log in again.
            </p>
        <?php endif; ?>
        <div class="form-fields">
            <div class="field-container">
                <div class="block">
                    <label>Username <span class="obligatory">*</span></label>
                    <input required type="text" name="username" placeholder="Username">
                </div>
                <div class="block">
                    <label>Password <span class="obligatory">*</span></label>
                    <input required type="password" name="password" placeholder="Password">
                </div>
            </div>    
            <?php if (!empty($loginError)): ?>
            <p class="field-error">
                <?= htmlspecialchars($loginError) ?>
            </p>
            <?php endif; ?>
            <a class="forgot-password">Forgot your password?</a>  
        </div>
        <button class="send-button" type="submit">Log in</button>
    </form>
    <a href="index.php?page=register" class="register-link">Don't have an account? Click here</a>
</main>
