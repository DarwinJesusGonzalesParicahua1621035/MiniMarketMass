<?php

class ClienteRepository
{
    public function __construct(private PDO $db) {}

    public function listar(string $q = ''): array
    {
        $sql = 'SELECT id, tipo_documento, numero_documento, nombre FROM cliente';
        $p = [];
        if ($q !== '') {
            $sql .= ' WHERE numero_documento LIKE :a OR nombre LIKE :b';
            $p = [':a' => '%' . $q . '%', ':b' => '%' . $q . '%'];
        }
        $sql .= ' ORDER BY id';
        $st = $this->db->prepare($sql);
        $st->execute($p);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crear(string $tipo, string $numero, string $nombre): void
    {
        $st = $this->db->prepare(
            'INSERT INTO cliente (tipo_documento, numero_documento, nombre) VALUES (:t, :n, :nom)'
        );
        $st->execute([':t' => $tipo, ':n' => $numero, ':nom' => $nombre]);
    }

    public function actualizar(int $id, string $tipo, string $numero, string $nombre): void
    {
        $st = $this->db->prepare(
            'UPDATE cliente SET tipo_documento = :t, numero_documento = :n, nombre = :nom WHERE id = :id'
        );
        $st->execute([':t' => $tipo, ':n' => $numero, ':nom' => $nombre, ':id' => $id]);
    }

    public function eliminar(int $id): void
    {
        $st = $this->db->prepare('DELETE FROM cliente WHERE id = :id');
        $st->execute([':id' => $id]);
    }
}