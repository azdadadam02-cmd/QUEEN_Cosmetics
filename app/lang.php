<?php defined('APP_ROOT') || exit;
/**
 * Langues : français (par défaut) + arabe (RTL).
 * Les vues restent écrites en français ; en arabe, le HTML final est traduit
 * (app/lang/ar.php) et la page passe en dir="rtl".
 * Pour traduire un nouveau texte : ajoute « français => arabe » dans app/lang/ar.php.
 */
const LANGS = ['fr', 'ar'];

function lang(): string {
    static $l = null;
    if ($l === null) {
        $c = $_COOKIE['queen_lang'] ?? '';
        $l = (is_string($c) && $c === 'ar') ? 'ar' : 'fr';
    }
    return $l;
}
function is_rtl(): bool { return lang() === 'ar'; }

/** Lien du sélecteur de langue (affiche la langue vers laquelle on bascule) */
function lang_switch_url(): string { return url('langue', ['l' => is_rtl() ? 'fr' : 'ar']); }
function lang_switch_label(): string { return is_rtl() ? 'Français' : 'العربية'; }

function lang_dict(): array {
    static $d = null;
    if ($d === null) { $d = require __DIR__ . '/lang/ar.php'; }
    return $d;
}

/** Traduit un texte français (déjà décodé). Retourne le texte inchangé si inconnu. */
function tr(string $s): string {
    if (!is_rtl() || $s === '') return $s;
    static $cache = [];
    if (isset($cache[$s])) return $cache[$s];
    $k = trim(preg_replace('/\s+/u', ' ', str_replace(["\u{2019}", "\u{00A0}"], ["'", ' '], $s)));
    $out = $s;
    if ($k !== '') {
        $d = lang_dict();
        if (preg_match('/^(.+?) — QUEEN(?: .+)?$/u', $k, $mm)) {
            $out = tr($mm[1]) . ' — QUEEN';
        } elseif (isset($d['exact'][$k])) {
            $out = $d['exact'][$k];
        } else {
            foreach ($d['patterns'] as [$re, $to]) {
                if (preg_match($re, $k)) { $out = preg_replace($re, $to, $k); break; }
            }
        }
        if ($out !== $s) {
            // conserve les espaces autour
            preg_match('/^\s*/u', $s, $a); preg_match('/\s*$/u', $s, $b);
            $out = $a[0] . $out . $b[0];
        }
    }
    $out = preg_replace('/(\d) DH\b/u', '$1 د.م', $out);
    return $cache[$s] = $out;
}

/** Traduit un document HTML complet (texte + quelques attributs) et le passe en RTL */
function lang_translate_html(string $html): string {
    $parts = preg_split('/(<script\b.*?<\/script>|<style\b.*?<\/style>)/is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    foreach ($parts as $i => $p) {
        if ($i % 2 === 1) continue;                                   // script / style : intacts
        $p = preg_replace_callback('/>([^<>]+)</u', function ($m) {
            $dec = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $t = tr($dec);
            return $t === $dec ? $m[0] : '>' . htmlspecialchars($t, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '<';
        }, $p);
        $p = preg_replace_callback('/\b(placeholder|title|aria-label|alt|data-confirm)="([^"]*)"/u', function ($m) {
            $dec = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $t = tr($dec);
            return $t === $dec ? $m[0] : $m[1] . '="' . htmlspecialchars($t, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
        }, $p);
        $parts[$i] = $p;
    }
    $html = implode('', $parts);
    $html = preg_replace('/<html lang="fr"[^>]*>/i', '<html lang="ar" dir="rtl">', $html, 1);
    $head = '<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">'
          . '<link rel="stylesheet" href="' . htmlspecialchars(asset('css/rtl.css'), ENT_QUOTES) . '">';
    return preg_replace('/<\/head>/i', $head . '</head>', $html, 1);
}

/** Callback d'ob_start : ne touche que les pages HTML */
function lang_buffer(string $buf): string {
    foreach (headers_list() as $h) {
        if (stripos($h, 'content-type:') === 0 && stripos($h, 'text/html') === false) return $buf;
    }
    if ($buf === '' || strpos(ltrim($buf), '<') !== 0) return $buf;
    return lang_translate_html($buf);
}

function lang_boot(): void {
    if (is_rtl()) ob_start('lang_buffer');
}

/** Traduit récursivement les messages d'une réponse JSON */
function lang_json(array $d): array {
    if (!is_rtl()) return $d;
    foreach ($d as $k => $v) {
        if (is_string($v) && $k === 'message') $d[$k] = tr($v);
        elseif (is_array($v)) $d[$k] = lang_json($v);
    }
    return $d;
}
