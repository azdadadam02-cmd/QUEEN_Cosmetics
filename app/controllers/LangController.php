<?php defined('APP_ROOT') || exit;
class LangController
{
    /** GET ?action=langue&l=ar|fr — mémorise la langue (cookie 1 an) puis revient à la page précédente */
    public function changer(): void {
        $l = $_GET['l'] ?? 'fr';
        $l = (is_string($l) && in_array($l, LANGS, true)) ? $l : 'fr';
        setcookie('queen_lang', $l, [
            'expires' => time() + 31536000, 'path' => '/', 'secure' => https_on(),
            'httponly' => true, 'samesite' => 'Lax',
        ]);
        redirect_back('accueil');
    }
}
