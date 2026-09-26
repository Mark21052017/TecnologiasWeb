<?php

declare(strict_types=1);

final class RegistroUniversitario
{
    public static function next(PDO $pdo): string
    {
        $statement = $pdo->query(
            'SELECT ultimo_numero FROM registro_universitario_secuencia WHERE id = 1 FOR UPDATE'
        );
        $current = $statement->fetchColumn();
        if ($current === false) {
            throw new RuntimeException('La secuencia de registros universitarios no esta configurada.');
        }

        $next = (int) $current + 1;
        $update = $pdo->prepare(
            'UPDATE registro_universitario_secuencia SET ultimo_numero = :ultimo_numero WHERE id = 1'
        );
        $update->execute(['ultimo_numero' => $next]);

        return 'RU-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
