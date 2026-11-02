<?php
defined("APP_ROOT") || exit;
// app/views/auth/register.php
$errors    = $_SESSION['errors'] ?? [];
$authError = $_SESSION['auth_error'] ?? '';
$old       = $_SESSION['old'] ?? ['nom' => '', 'email' => ''];

unset($_SESSION['errors'], $_SESSION['auth_error'], $_SESSION['old']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un Compte Client | QUEEN Cosmetics</title>
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
                <span class="luxury-tag">CLUB PRIVILÈGE CLIENT</span>
                <h1>Rejoignez l'univers de beauté QUEEN.</h1>
                <p>Créez votre compte client pour commander vos produits favoris et profiter de nos offres promotionnelles exclusives.</p>
                <div class="visual-highlights">
                    <span>✦ Offres & Réductions VIP</span>
                    <span>✦ Suivi de commandes en temps réel</span>
                </div>
            </div>
        </div>

        <!-- Panneau Formulaire Droite -->
        <div class="auth-form-panel">
            <div class="lang-bar"><a class="lang-switch" href="<?= e(lang_switch_url()) ?>" hreflang="<?= is_rtl() ? 'fr' : 'ar' ?>"><?= e(lang_switch_label()) ?></a></div>
            
            <!-- ESPACE LOGO (IMG UNIQUEMENT) -->
            <div class="logo-box">
                <img src="images/logo.png" alt="QUEEN Cosmetics Logo" class="logo-img">
            </div>

            <!-- Onglets de navigation -->
            <div class="auth-tabs">
                <a href="index.php?action=login" class="tab-link">Se connecter</a>
                <a href="index.php?action=register" class="tab-link active">Créer un compte client</a>
            </div>

            <div class="form-title">
                <h2>Inscription Client</h2>
                <p>Remplissez vos informations pour créer votre compte</p>
            </div>

            <?php if ($authError !== ''): ?>
                <div class="alert alert-error"><?= htmlspecialchars($authError) ?></div>
            <?php endif; ?>

            <!-- Formulaire CRÉER UN COMPTE -->
            <form id="registerForm" action="index.php?action=register_post" method="POST" novalidate>
                <?= csrf_field() ?>
                
                <div class="input-group">
                    <label for="reg_nom">Nom complet</label>
                    <div class="input-wrapper">
                        <input 
                            type="text" 
                            id="reg_nom" 
                            name="nom" 
                            placeholder="Plus de 3 caractères (ex: Amira Tazi)"
                            value="<?= $old['nom'] ?>"
                            class="<?= isset($errors['nom']) ? 'is-invalid' : '' ?>"
                            autocomplete="name"
                        >
                    </div>
                    <span class="error-msg" id="reg_nom_error"><?= $errors['nom'] ?? '' ?></span>
                </div>

                <div class="input-group">
                    <label for="reg_email">Adresse Email</label>
                    <div class="input-wrapper">
                        <input 
                            type="email" 
                            id="reg_email" 
                            name="email" 
                            placeholder="votre.email@exemple.com"
                            value="<?= $old['email'] ?>"
                            class="<?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                            autocomplete="email"
                        >
                    </div>
                    <span class="error-msg" id="reg_email_error"><?= $errors['email'] ?? '' ?></span>
                </div>

                <div class="input-group">
                    <label for="reg_password">Mot de passe</label>
                    <div class="input-wrapper password-wrapper">
                        <input 
                            type="password" 
                            id="reg_password" 
                            name="mot_de_passe" 
                            placeholder="Min. 8 caractères + 1 Majuscule"
                            class="<?= isset($errors['mot_de_passe']) ? 'is-invalid' : '' ?>"
                            autocomplete="new-password"
                        >
                        <button type="button" class="btn-eye" onclick="togglePasswordVisibility('reg_password', this)" aria-label="Afficher le mot de passe">👁</button>
                    </div>

                    <div class="password-rules">
                        <span id="rule_length" class="rule-badge">● 8 caractères min.</span>
                        <span id="rule_upper" class="rule-badge">● 1 lettre Majuscule</span>
                    </div>

                    <span class="error-msg" id="reg_password_error"><?= $errors['mot_de_passe'] ?? '' ?></span>
                </div>

                <button type="submit" class="btn-submit">Créer mon compte client</button>
            </form>

            <div class="auth-bottom-text">
                <p>Vous avez déjà un compte ? <a href="index.php?action=login">Se connecter</a></p>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>