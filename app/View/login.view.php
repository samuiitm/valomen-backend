<main class="main-login">
    <form class="login-form" method="POST" action="<?= htmlspecialchars(url('login')) ?>">
        <h1>Login to Valomen.gg</h1>
        <p>Continue earning points, making pick’ems, and proving who really runs the game.</p>

        <div class="container-oauth">
            <a class="btn-primary" href="<?= htmlspecialchars(url('auth/google')) ?>">
                <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="30" height="30" viewBox="0,0,256,256">
                    <g fill="#d4d4d4" fill-rule="nonzero" stroke="none" stroke-width="1" stroke-linecap="butt" stroke-linejoin="miter" stroke-miterlimit="10" stroke-dasharray="" stroke-dashoffset="0" font-family="none" font-weight="none" font-size="none" text-anchor="none" style="mix-blend-mode: normal"><g transform="scale(8.53333,8.53333)"><path d="M15.00391,3c-6.629,0 -12.00391,5.373 -12.00391,12c0,6.627 5.37491,12 12.00391,12c10.01,0 12.26517,-9.293 11.32617,-14h-1.33008h-2.26758h-7.73242v4h7.73828c-0.88958,3.44825 -4.01233,6 -7.73828,6c-4.418,0 -8,-3.582 -8,-8c0,-4.418 3.582,-8 8,-8c2.009,0 3.83914,0.74575 5.24414,1.96875l2.8418,-2.83984c-2.134,-1.944 -4.96903,-3.12891 -8.08203,-3.12891z"></path></g></g>
                </svg>
                <span>Continue with Google</span>
            </a>
            <a class="btn-primary" href="<?= htmlspecialchars(url('auth/github')) ?>">
                <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="30" height="30" viewBox="0,0,256,256">
                    <g fill="#d4d4d4" fill-rule="nonzero" stroke="none" stroke-width="1" stroke-linecap="butt" stroke-linejoin="miter" stroke-miterlimit="10" stroke-dasharray="" stroke-dashoffset="0" font-family="none" font-weight="none" font-size="none" text-anchor="none" style="mix-blend-mode: normal"><g transform="scale(5.12,5.12)"><path d="M17.791,46.836c0.711,-0.306 1.209,-1.013 1.209,-1.836v-5.4c0,-0.197 0.016,-0.402 0.041,-0.61c-0.014,0.004 -0.027,0.007 -0.041,0.01c0,0 -3,0 -3.6,0c-1.5,0 -2.8,-0.6 -3.4,-1.8c-0.7,-1.3 -1,-3.5 -2.8,-4.7c-0.3,-0.2 -0.1,-0.5 0.5,-0.5c0.6,0.1 1.9,0.9 2.7,2c0.9,1.1 1.8,2 3.4,2c2.487,0 3.82,-0.125 4.622,-0.555c0.934,-1.389 2.227,-2.445 3.578,-2.445v-0.025c-5.668,-0.182 -9.289,-2.066 -10.975,-4.975c-3.665,0.042 -6.856,0.405 -8.677,0.707c-0.058,-0.327 -0.108,-0.656 -0.151,-0.987c1.797,-0.296 4.843,-0.647 8.345,-0.714c-0.112,-0.276 -0.209,-0.559 -0.291,-0.849c-3.511,-0.178 -6.541,-0.039 -8.187,0.097c-0.02,-0.332 -0.047,-0.663 -0.051,-0.999c1.649,-0.135 4.597,-0.27 8.018,-0.111c-0.079,-0.5 -0.13,-1.011 -0.13,-1.543c0,-1.7 0.6,-3.5 1.7,-5c-0.5,-1.7 -1.2,-5.3 0.2,-6.6c2.7,0 4.6,1.3 5.5,2.1c1.699,-0.701 3.599,-1.101 5.699,-1.101c2.1,0 4,0.4 5.6,1.1c0.9,-0.8 2.8,-2.1 5.5,-2.1c1.5,1.4 0.7,5 0.2,6.6c1.1,1.5 1.7,3.2 1.6,5c0,0.484 -0.045,0.951 -0.11,1.409c3.499,-0.172 6.527,-0.034 8.204,0.102c-0.002,0.337 -0.033,0.666 -0.051,0.999c-1.671,-0.138 -4.775,-0.28 -8.359,-0.089c-0.089,0.336 -0.197,0.663 -0.325,0.98c3.546,0.046 6.665,0.389 8.548,0.689c-0.043,0.332 -0.093,0.661 -0.151,0.987c-1.912,-0.306 -5.171,-0.664 -8.879,-0.682c-1.665,2.878 -5.22,4.755 -10.777,4.974v0.031c2.6,0 5,3.9 5,6.6v5.4c0,0.823 0.498,1.53 1.209,1.836c9.161,-3.032 15.791,-11.672 15.791,-21.836c0,-12.682 -10.317,-23 -23,-23c-12.683,0 -23,10.318 -23,23c0,10.164 6.63,18.804 15.791,21.836z"></path></g></g>
                </svg>
                <span>Continue with GitHub</span>
            </a>
        </div>

        <div class="separador"></div>

        <?php if (!empty($expired)): ?>
            <p class="error-message">
                Your session has expired after 40 minutes of inactivity. Please log in again.
            </p>
        <?php endif; ?>

        <div class="form-fields">
            <div class="field-container">
                <div class="block">
                    <label>Username <span class="obligatory">*</span></label>
                    <input type="text" name="username" placeholder="Username"
                        value="<?= htmlspecialchars($username ?? '') ?>">
                </div>
                <div class="block">
                    <label>Password <span class="obligatory">*</span></label>
                    <input type="password" name="password" placeholder="Password">
                </div>
            </div>

            <?php if (!empty($loginError)): ?>
                <p class="field-error"><?= htmlspecialchars($loginError) ?></p>
            <?php endif; ?>

            <?php if (!empty($_SESSION['profile_error'])): ?>
                <p class="error-message"><?= htmlspecialchars($_SESSION['profile_error']) ?></p>
                <?php unset($_SESSION['profile_error']); ?>
            <?php endif; ?>

            <div class="container-login">
                <div class="field-block">
                    <label class="remember-label">
                        <input type="checkbox" name="remember_me" value="1"
                            <?= !empty($rememberMe) ? 'checked' : '' ?>>
                        Remember me
                    </label>
                </div>

                <a class="forgot-password" href="<?= htmlspecialchars(url('forgot_password')) ?>">Forgot your password?</a>
            </div>
        </div>

        <button class="send-button" type="submit">Log in</button>

        <?php if (!empty($showRecaptcha)): ?>
            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
            <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars(RECAPTCHA_SITE_KEY) ?>"></div>
        <?php endif; ?>
    </form>

    <a href="<?= htmlspecialchars(url('register')) ?>" class="register-link">
        Don't have an account? Click here
    </a>
</main>