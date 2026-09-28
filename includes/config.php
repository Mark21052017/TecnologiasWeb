<?php

declare(strict_types=1);

function database_config(): array
{
    $config = [
        'host' => getenv('DB_HOST'),
        'port' => getenv('DB_PORT'),
        'name' => getenv('DB_DATABASE') ?: getenv('DB_NAME'),
        'user' => getenv('DB_USERNAME') ?: getenv('DB_USER'),
        'password' => getenv('DB_PASSWORD'),
    ];
    $environmentNames = [
        'host' => 'DB_HOST',
        'port' => 'DB_PORT',
        'name' => 'DB_DATABASE',
        'user' => 'DB_USERNAME',
        'password' => 'DB_PASSWORD',
    ];

    foreach ($config as $key => $value) {
        if ($value === false || trim((string) $value) === '') {
            throw new RuntimeException('Falta configurar la variable de entorno de base de datos: ' . $environmentNames[$key]);
        }
    }

    return [
        'host' => (string) $config['host'],
        'port' => (string) $config['port'],
        'name' => (string) $config['name'],
        'user' => (string) $config['user'],
        'password' => (string) $config['password'],
    ];
}
