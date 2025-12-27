<main class="main-register">
    <form class="register-form" method="POST" action="register" novalidate>
        <?php if (!empty($registerSuccess)): ?>
            <p class="success-message">
                Account created successfully. You can now
                <a href="login" class="inline-link">log in</a>.
            </p>
        <?php endif; ?>

        <?php if (empty($registerSuccess)): ?>
            <h1>Create your account</h1>
            <p>Join the community, make predictions, earn rewards, and experience Valorant like never before.</p>

            <div class="form-fields">
                <div class="field-container">
                    <div class="block">
                        <label>Username <span class="obligatory">*</span></label>
                        <input
                            required
                            type="text"
                            name="username"
                            id="username"
                            placeholder="Username"
                            value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                        >
                        <p class="field-error">
                            <?= htmlspecialchars($registerErrors['username'] ?? '') ?>
                        </p>
                    </div>
                    <div class="block">
                        <label>Email <span class="obligatory">*</span></label>
                        <input
                            required
                            type="email"
                            name="email"
                            id="email"
                            placeholder="Email"
                            value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                        >
                        <p class="field-error">
                            <?= htmlspecialchars($registerErrors['email'] ?? '') ?>
                        </p>
                    </div>
                </div>
                <div class="field-container">
                    <div class="block">
                        <label>Password <span class="obligatory">*</span></label>
                        <input
                            required
                            type="password"
                            name="password"
                            id="password"
                            placeholder="Password"
                        >
                        <p class="field-error">
                            <?= htmlspecialchars($registerErrors['password'] ?? '') ?>
                        </p>
                    </div>
                    <div class="block">
                        <label>Confirm password <span class="obligatory">*</span></label>
                        <input
                            required
                            type="password"
                            name="confirm_password"
                            id="confirm_password"
                            placeholder="Confirm password"
                        >
                        <p class="field-error">
                            <?= htmlspecialchars($registerErrors['confirm_password'] ?? '') ?>
                        </p>
                    </div>
                </div>
            </div>
            <button class="send-button" type="submit">Register</button>
        </form>

        <a href="login" class="login-link">Already have an account? Click here</a>
        <?php endif; ?>
</main>