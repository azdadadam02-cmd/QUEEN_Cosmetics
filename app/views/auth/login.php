<?php
// app/views/auth/login.php
$errors     = $_SESSION['errors'] ?? [];
$authError  = $_SESSION['auth_error'] ?? '';
$successMsg = $_SESSION['success_msg'] ?? '';
$oldEmail   = $_SESSION['old_email'] ?? '';

unset($_SESSION['errors'], $_SESSION['auth_error'], $_SESSION['success_msg'], $_SESSION['old_email']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Se Connecter | QUEEN Cosmetics</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body class="auth-body">

    <div class="auth-container">
        <!-- Panneau Visuel Gauche -->
        <div class="auth-visual">
            <!-- Logo sur le panneau gauche -->
            <div class="visual-logo">
                <img src="images/logo.png" alt="QUEEN Cosmetics Logo">
            </div>

            <div class="visual-content">
                <span class="luxury-tag">HAUTE BEAUTÉ & PARFUMS</span>
                <h1>L'élégance royale au cœur de votre beauté.</h1>
                <p>Découvrez nos collections exclusives de maquillage, soins visage et parfums d'exception signés QUEEN.</p>
                <div class="visual-highlights">
                    <span>✦ Produits 100% Authentiques</span>
                    <span>✦ Paiement à la livraison</span>
                </div>
            </div>
        </div>

        <!-- Panneau Formulaire Droite -->
        <div class="auth-form-panel">
            
            <!-- ESPACE LOGO (IMG UNIQUEMENT) -->
            <div class="logo-box">
                <img src="images/logo.png" alt="QUEEN Cosmetics Logo" class="logo-img">
            </div>

            <!-- Onglets de navigation -->
            <div class="auth-tabs">
                <a href="index.php?action=login" class="tab-link active">Se connecter</a>
                <a href="index.php?action=register" class="tab-link">Créer un compte client</a>
            </div>

            <div class="form-title">
                <h2>Bienvenue chez QUEEN</h2>
                <p>Connectez-vous à votre espace</p>
            </div>

            <!-- Messages Flash -->
            <?php if ($authError !== ''): ?>
                <div class="alert alert-error"><?= htmlspecialchars($authError) ?></div>
            <?php endif; ?>

            <?php if ($successMsg !== ''): ?>
                <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
            <?php endif; ?>

            <!-- Formulaire SE CONNECTER -->
            <form id="loginForm" action="index.php?action=login_post" method="POST" novalidate>
                
                <div class="input-group">
                    <label for="login_email">Adresse Email</label>
                    <div class="input-wrapper">
                        <input 
                            type="email" 
                            id="login_email" 
                            name="email" 
                            placeholder="votre.email@exemple.com"
                            value="<?= $oldEmail ?>"
                            class="<?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                            autocomplete="email"
                        >
                    </div>
                    <span class="error-msg" id="login_email_error"><?= $errors['email'] ?? '' ?></span>
                </div>

                <div class="input-group">
                    <label for="login_password">Mot de passe</label>
                    <div class="input-wrapper password-wrapper">
                        <input 
                            type="password" 
                            id="login_password" 
                            name="mot_de_passe" 
                            placeholder="Min. 8 caractères dont 1 Majuscule"
                            class="<?= isset($errors['mot_de_passe']) ? 'is-invalid' : '' ?>"
                            autocomplete="current-password"
                        >
                        <button type="button" class="btn-eye" onclick="togglePasswordVisibility('login_password', this)" aria-label="Afficher le mot de passe">👁</button>
                    </div>
                    <span class="error-msg" id="login_password_error"><?= $errors['mot_de_passe'] ?? '' ?></span>
                </div>

                <button type="submit" class="btn-submit">Se connecter</button>
            </form>

            <div class="auth-bottom-text">
                <p>Nouveau client chez QUEEN ? <a href="index.php?action=register">Créer un compte gratuitement</a></p>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>