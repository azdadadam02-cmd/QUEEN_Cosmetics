/* Traduction des textes JS (arabe) — la langue vient de <html lang> */
window.__ = (() => {
  const AR = {
    'Ajouté ✓': 'تمت الإضافة ✓', 'Ajouter au panier': 'أضف إلى السلة',
    'Modifier l\u2019utilisateur': 'تعديل المستخدم', 'Ajouter un utilisateur': 'إضافة مستخدم',
    'Laisser vide pour ne pas changer': 'اتركه فارغًا لعدم التغيير', '(optionnel)': '(اختياري)',
    '(8 caractères min. + 1 majuscule)': '(8 أحرف على الأقل + حرف كبير)',
    'Cliquez pour changer l\u2019image': 'انقر لتغيير الصورة', 'Cliquez pour ajouter une image': 'انقر لإضافة صورة',
    'Cliquez pour choisir une image': 'انقر لاختيار صورة', 'Modifier la publicité': 'تعديل الإعلان', 'Ajouter une publicité': 'إضافة إعلان',
    '(vide = garder l\u2019actuelle)': '(فارغ = الإبقاء على الحالية)', '(JPG, PNG, WEBP \u00b7 3 Mo max)': '(JPG, PNG, WEBP \u00b7 3 م.ب كحد أقصى)',
    'Publicité': 'الإعلان', 'Masquer': 'إخفاء', 'Détails': 'التفاصيل'
  };
  const ar = document.documentElement.lang === 'ar';
  return s => (ar && AR[s]) || s;
})();

/* QUEEN Cosmetics — espace client */
(() => {
  'use strict';
  const $  = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const norm = s => (s || '').toString().toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');

  /* ───── Toasts ───── */
  const toasts = $('#toasts');
  function dismiss(t) { t.classList.add('out'); setTimeout(() => t.remove(), 400); }
  function toast(msg, type = 'success') {
    if (!toasts) return;
    const t = document.createElement('div');
    t.className = 'toast toast-' + type;
    t.textContent = msg;
    toasts.appendChild(t);
    setTimeout(() => dismiss(t), 4200);
  }
  $$('.toast', toasts || document).forEach(t => setTimeout(() => dismiss(t), 5200));

  /* ───── Menu profil (animation + fermeture) ───── */
  const profile = $('#profile'), pbtn = $('#profile-btn');
  if (profile && pbtn) {
    const close = () => { profile.classList.remove('open'); pbtn.setAttribute('aria-expanded', 'false'); };
    pbtn.addEventListener('click', e => {
      e.stopPropagation();
      const open = profile.classList.toggle('open');
      pbtn.setAttribute('aria-expanded', String(open));
    });
    document.addEventListener('click', e => { if (!profile.contains(e.target)) close(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
  }

  /* ───── Bandeau publicitaire : défilement automatique ───── */
  const ads = $('#ads'), track = $('#ads-track');
  if (ads && track) {
    const slides = $$('.ad-slide', track), dots = $('#ads-dots');
    let i = 0, timer = null, hover = false;
    const mark = () => dots && $$('button', dots).forEach((d, k) => d.classList.toggle('on', k === i));
    const go = (n, smooth = true) => {
      i = (n + slides.length) % slides.length;
      track.scrollTo({ left: i * track.clientWidth, behavior: smooth && !reduce ? 'smooth' : 'auto' });
      mark();
    };
    const play = () => {
      if (slides.length < 2 || reduce) return;
      timer = setInterval(() => { if (!hover && !document.hidden) go(i + 1); }, 5200);
    };
    const restart = () => { clearInterval(timer); play(); };

    if (dots) slides.forEach((_, k) => {
      const b = document.createElement('button');
      b.type = 'button'; b.setAttribute('aria-label', __('Publicité') + ' ' + (k + 1));
      b.addEventListener('click', () => { go(k); restart(); });
      dots.appendChild(b);
    });
    track.addEventListener('scroll', () => {
      const k = Math.round(track.scrollLeft / track.clientWidth);
      if (k !== i && k >= 0 && k < slides.length) { i = k; mark(); }
    }, { passive: true });
    $('#ads-prev')?.addEventListener('click', () => { go(i - 1); restart(); });
    $('#ads-next')?.addEventListener('click', () => { go(i + 1); restart(); });
    ads.addEventListener('mouseenter', () => hover = true);
    ads.addEventListener('mouseleave', () => hover = false);
    ads.addEventListener('touchstart', () => hover = true, { passive: true });
    ads.addEventListener('touchend', () => { hover = false; restart(); }, { passive: true });
    addEventListener('resize', () => go(i, false));
    mark(); play();
  }

  /* ───── Filtres par catégorie + recherche ───── */
  const grid = $('#grid');
  if (grid) {
    const cards = $$('.card', grid), chips = $$('.chip'), search = $('#search'), empty = $('#empty');
    let cat = 'all', q = '';
    const apply = () => {
      let n = 0;
      cards.forEach(c => {
        const ok = (cat === 'all' || c.dataset.cat === cat) && (!q || norm(c.dataset.name).includes(q));
        c.hidden = !ok; if (ok) n++;
      });
      if (empty) empty.hidden = n > 0;
    };
    chips.forEach(ch => ch.addEventListener('click', () => {
      chips.forEach(x => x.classList.remove('active')); ch.classList.add('active');
      cat = ch.dataset.cat; apply();
    }));
    search?.addEventListener('input', () => { q = norm(search.value.trim()); apply(); });
  }

  /* ───── Ajout au panier sans rechargement ───── */
  const badge = $('#cart-badge');
  const setCart = n => {
    if (!badge) return;
    badge.textContent = n; badge.hidden = n < 1;
    badge.classList.remove('pop'); void badge.offsetWidth; badge.classList.add('pop');
  };
  document.addEventListener('submit', async e => {
    const f = e.target;
    if (!(f instanceof HTMLFormElement) || !f.matches('.add-form') || !window.fetch) return;
    e.preventDefault();
    const btn = f.querySelector('button[type=submit]'), lbl = btn.querySelector('.lbl');
    if (btn.disabled) return;
    btn.classList.add('loading');
    try {
      const r = await fetch(f.action, {
        method: 'POST', body: new FormData(f), credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
      });
      const d = await r.json();
      toast(d.message, d.ok ? 'success' : 'error');
      if (typeof d.count === 'number') setCart(d.count);
      if (d.ok && lbl) {
        btn.classList.add('done'); lbl.textContent = __('Ajouté ✓');
        setTimeout(() => { btn.classList.remove('done'); lbl.textContent = __('Ajouter au panier'); }, 1600);
      }
    } catch (err) {
      f.submit();                       // repli : envoi classique du formulaire
    } finally {
      btn.classList.remove('loading');
    }
  });

  /* ───── Confirmation : « +18 » obligatoire pour valider ───── */
  const majeur = $('#majeur'), valider = $('#btn-valider');
  if (majeur && valider) {
    const sync = () => { valider.disabled = !majeur.checked; };
    majeur.addEventListener('change', sync); sync();
  }

  /* ───── Apparition au scroll ───── */
  const rev = $$('.reveal');
  if ('IntersectionObserver' in window && !reduce) {
    const io = new IntersectionObserver(es => es.forEach(en => {
      if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
    }), { threshold: .08, rootMargin: '0px 0px -40px 0px' });
    rev.forEach((el, k) => { el.style.transitionDelay = Math.min(k % 8, 7) * 50 + 'ms'; io.observe(el); });
  } else {
    rev.forEach(el => el.classList.add('in'));
  }
})();

/* Image cassée → on la retire (remplace l'ancien onerror inline, interdit par la CSP) */
(() => {
  const fix = img => { if (img.complete && img.naturalWidth === 0 && img.getAttribute('src')) img.remove(); };
  document.querySelectorAll('img[data-fallback]').forEach(img => {
    img.addEventListener('error', () => img.remove());
    fix(img);
  });
})();
