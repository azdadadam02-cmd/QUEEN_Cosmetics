<?php defined('APP_ROOT') || exit; ?>
<section class="page-head page-head-row">
    <div>
        <span class="eyebrow">Administration</span>
        <h1 class="h-serif">Utilisateurs</h1>
        <p class="lead">Clients et responsables. Les comptes administrateur n'apparaissent pas ici et ne peuvent être ni modifiés ni supprimés.</p>
    </div>
    <button type="button" class="btn btn-gold" data-user-add>+ Ajouter un utilisateur</button>
</section>

<div class="panel tablewrap">
    <div class="toolbar">
        <label class="search">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" placeholder="Rechercher (nom, email, rôle)…" data-filter="#tbl-users" autocomplete="off">
        </label>
        <span class="count"><?= count($users) ?> utilisateur<?= count($users) > 1 ? 's' : '' ?></span>
    </div>
    <table class="tbl" id="tbl-users">
        <thead><tr><th>Utilisateur</th><th>Rôle</th><th class="num">Commandes</th><th class="right">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td>
                    <div class="cell-prod">
                        <span class="avatar sm"><?= e(initial($u['nom'])) ?></span>
                        <div><strong><?= e($u['nom']) ?></strong><small><?= e($u['email']) ?></small></div>
                    </div>
                </td>
                <td><span class="pill pill-role pill-<?= e($u['role']) ?>"><?= e(ROLES_LABELS[$u['role']] ?? $u['role']) ?></span> <span hidden><?= e($u['role']) ?></span></td>
                <td class="num"><?= (int)$u['nb_commandes'] ?></td>
                <td class="right act">
                    <button type="button" class="btn-ghost"
                            data-user-edit
                            data-id="<?= (int)$u['id'] ?>"
                            data-nom="<?= e($u['nom']) ?>"
                            data-email="<?= e($u['email']) ?>"
                            data-role="<?= e($u['role']) ?>">Modifier</button>
                    <form method="post" action="<?= e(url('admin_utilisateur_supprimer')) ?>" class="inline"
                          data-confirm="Supprimer définitivement « <?= e($u['nom']) ?> » ?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                        <button type="submit" class="btn-danger">Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$users): ?><tr><td colspan="4" class="empty-row">Aucun utilisateur.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<dialog id="user-dialog" class="dialog">
    <form method="post" action="<?= e(url('admin_utilisateur_save')) ?>" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="u_id" value="0">
        <h3 id="u_title">Ajouter un utilisateur</h3>
        <div class="field">
            <label for="u_nom">Nom complet</label>
            <input id="u_nom" name="nom" type="text" maxlength="100" required placeholder="Plus de 3 caractères">
        </div>
        <div class="field">
            <label for="u_email">Adresse email</label>
            <input id="u_email" name="email" type="email" maxlength="150" required placeholder="nom@exemple.com">
        </div>
        <div class="field">
            <label for="u_role">Rôle</label>
            <select id="u_role" name="role">
                <?php foreach (User::ROLES_GERABLES as $r): ?>
                    <option value="<?= e($r) ?>"><?= e(ROLES_LABELS[$r]) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="u_pw">Mot de passe <small id="u_pw_hint">(8 caractères min. + 1 majuscule)</small></label>
            <input id="u_pw" name="mot_de_passe" type="password" autocomplete="new-password" placeholder="••••••••">
        </div>
        <div class="dialog-actions">
            <button type="button" class="btn-ghost" data-dialog-close>Annuler</button>
            <button type="submit" class="btn btn-gold btn-sm">Enregistrer</button>
        </div>
    </form>
</dialog>
