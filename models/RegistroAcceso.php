<?php

declare(strict_types=1);

final class RegistroAcceso
{
    public function all(): array
    {
        $sql = <<<'SQL'
            SELECT ra.id_acceso, ra.fecha_hora, ra.ip_origen, ra.resultado,
                   u.usuario, u.nombre, u.apellido
            FROM registro_accesos ra
            INNER JOIN usuarios u ON u.id_usuario = ra.id_usuario
            ORDER BY ra.fecha_hora DESC, ra.id_acceso DESC
            LIMIT 200
        SQL;

        return Database::connection()->query($sql)->fetchAll();
    }
}
