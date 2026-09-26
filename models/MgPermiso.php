<?php

declare(strict_types=1);

final class MgPermiso
{
    public function userHasPermission(int $userId, string $permission): bool
    {
        $statement = Database::connection()->prepare(
            "SELECT 1
             FROM usuarios u
             INNER JOIN roles r ON r.id_rol = u.id_rol
             INNER JOIN mg_permisos_rol mpr ON mpr.id_rol = r.id_rol AND mpr.permitido = 1
             INNER JOIN mg_permisos mp ON mp.codigo = mpr.codigo_permiso AND mp.estado = 'activo'
             WHERE u.id_usuario = :id_usuario
               AND u.estado = 'activo'
               AND mp.codigo = :codigo
             LIMIT 1"
        );
        $statement->execute(['id_usuario' => $userId, 'codigo' => $permission]);

        return (bool) $statement->fetchColumn();
    }
}
