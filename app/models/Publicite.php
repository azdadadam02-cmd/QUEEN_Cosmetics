<?php
class Publicite
{
    private PDO $db;
    public function __construct(?PDO $db = null) { $this->db = $db ?? db(); }

    public function toutes(): array {
        return $this->db->query('SELECT * FROM publicites ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
    }
}
