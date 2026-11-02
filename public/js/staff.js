/* QUEEN — espace admin / responsables */
(() => {
  'use strict';
  const $  = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
  const norm = s => (s || '').toString().toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');

  /* Recherche instantanée dans un tableau : <input data-filter="#id"> */
  $$('[data-filter]').forEach(inp => {
    const table = $(inp.dataset.filter);
    if (!table) return;
    inp.addEventListener('input', () => {
      const q = norm(inp.value.trim());
      $$('tbody tr', table).forEach(tr => {
        if (tr.classList.contains('o-detail')) return;
        const hit = !q || norm(tr.textContent).includes(q);
        tr.hidden = !hit;
        const d = tr.nextElementSibling;
        if (d && d.classList.contains('o-detail') && !hit) d.hidden = true;
      });
    });
  });

  /* Détails d'une commande (ligne dépliable) */
  document.addEventListener('click', e => {
    const b = e.target.closest('[data-toggle]');
    if (!b) return;
    const d = b.closest('tr').nextElementSibling;
    if (!d) return;
    d.hidden = !d.hidden;
    b.setAttribute('aria-expanded', String(!d.hidden));
    b.textContent = d.hidden ? __('Détails') : __('Masquer');
  });

  /* Confirmation avant action sensible : <form data-confirm="…"> */
  document.addEventListener('submit', e => {
    const f = e.target;
    if (f instanceof HTMLFormElement && f.dataset.confirm && !confirm(f.dataset.confirm)) e.preventDefault();
  });

  /* Dialogue ajouter / modifier un utilisateur */
  const dlg = $('#user-dialog');
  if (dlg) {
    const f = $('form', dlg), id = $('#u_id'), nom = $('#u_nom'), email = $('#u_email'),
          role = $('#u_role'), pw = $('#u_pw'), title = $('#u_title'), hint = $('#u_pw_hint');
    const open = (u) => {
      f.reset();
      id.value = u ? u.id : 0;
      title.textContent = u ? __('Modifier l’utilisateur') : __('Ajouter un utilisateur');
      if (u) { nom.value = u.nom; email.value = u.email; role.value = u.role; }
      pw.placeholder = u ? __('Laisser vide pour ne pas changer') : '••••••••';
      hint.textContent = u ? __('(optionnel)') : __('(8 caractères min. + 1 majuscule)');
      typeof dlg.showModal === 'function' ? dlg.showModal() : dlg.setAttribute('open', '');
      nom.focus();
    };
    document.addEventListener('click', e => {
      if (e.target.closest('[data-user-add]')) return open(null);
      const b = e.target.closest('[data-user-edit]');
      if (b) return open({ id: b.dataset.id, nom: b.dataset.nom, email: b.dataset.email, role: b.dataset.role });
      if (e.target.closest('[data-dialog-close]') || e.target === dlg) dlg.close ? dlg.close() : dlg.removeAttribute('open');
    });
  }

  /* Dialogue modifier un produit */
  const pd = $('#prod-dialog');
  if (pd) {
    const g = id => $(id, pd), img = g('#e_img'), prevE = g('#e_preview'), hintE = g('#e_hint');
    document.addEventListener('click', e => {
      const b = e.target.closest('[data-prod-edit]');
      if (b) {
        g('#e_id').value = b.dataset.id; g('#e_nom').value = b.dataset.nom;
        g('#e_cat').value = b.dataset.categorie; g('#e_prix').value = b.dataset.prix; g('#e_stock').value = b.dataset.stock; g('#e_promo').value = b.dataset.promo || 0;
        img.value = '';
        if (b.dataset.image) { prevE.src = b.dataset.image; prevE.hidden = false; prevE.onerror = () => { prevE.hidden = true; }; hintE.textContent = __('Cliquez pour changer l\u2019image'); }
        else { prevE.hidden = true; hintE.textContent = __('Cliquez pour ajouter une image'); }
        typeof pd.showModal === 'function' ? pd.showModal() : pd.setAttribute('open', '');
        g('#e_nom').focus();
        return;
      }
      if (e.target.closest('#prod-dialog [data-dialog-close]') || e.target === pd) pd.close ? pd.close() : pd.removeAttribute('open');
    });
    img.addEventListener('change', () => {
      const f = img.files && img.files[0];
      if (f) { prevE.src = URL.createObjectURL(f); prevE.hidden = false; hintE.textContent = f.name; }
    });
  }

  /* Dialogue ajouter / modifier une publicité */
  const gd = $('#pub-dialog');
  if (gd) {
    const g = id => $(id, gd), img = g('#g_img'), prevG = g('#g_preview'), hintG = g('#g_hint');
    const open = u => {
      g('form').reset() || 0;
      g('#g_id').value = u ? u.id : 0;
      g('#g_title').textContent = u ? __('Modifier la publicité') : __('Ajouter une publicité');
      g('#g_titre').value = u ? u.titre : '';
      g('#g_red').value = u ? u.reduction : 0;
      g('#g_prod').value = u && u.produit ? u.produit : '0';
      img.value = '';
      if (u && u.image) { prevG.src = u.image; prevG.hidden = false; prevG.onerror = () => { prevG.hidden = true; }; hintG.textContent = __('Cliquez pour changer l\u2019image'); g('#g_img_hint').textContent = __('(vide = garder l\u2019actuelle)'); }
      else { prevG.hidden = true; hintG.textContent = __('Cliquez pour choisir une image'); g('#g_img_hint').textContent = __('(JPG, PNG, WEBP \u00b7 3 Mo max)'); }
      typeof gd.showModal === 'function' ? gd.showModal() : gd.setAttribute('open', '');
      g('#g_titre').focus();
    };
    document.addEventListener('click', e => {
      if (e.target.closest('[data-pub-add]')) return open(null);
      const b = e.target.closest('[data-pub-edit]');
      if (b) return open({ id: b.dataset.id, titre: b.dataset.titre, reduction: b.dataset.reduction, produit: b.dataset.produit, image: b.dataset.image });
      if (e.target.closest('#pub-dialog [data-dialog-close]') || e.target === gd) gd.close ? gd.close() : gd.removeAttribute('open');
    });
    img.addEventListener('change', () => {
      const f = img.files && img.files[0];
      if (f) { prevG.src = URL.createObjectURL(f); prevG.hidden = false; hintG.textContent = f.name; }
    });
  }

  /* Aperçu de l'image du produit */
  const file = $('#p_img'), prev = $('#p_preview'), hintP = $('#p_hint');
  if (file && prev) {
    file.addEventListener('change', () => {
      const f = file.files && file.files[0];
      if (!f) { prev.hidden = true; hintP.hidden = false; return; }
      prev.src = URL.createObjectURL(f); prev.hidden = false; hintP.textContent = f.name;
    });
  }
})();
