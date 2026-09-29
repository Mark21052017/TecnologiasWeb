<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/includes/bootstrap.php';

/** Split MySQL scripts at semicolons outside quoted strings and comments. */
function split_mysql_statements(string $sql): array
{
    $statements = [];
    $buffer = '';
    $quote = null;
    $lineComment = false;
    $blockComment = false;
    $length = strlen($sql);

    for ($index = 0; $index < $length; $index++) {
        $char = $sql[$index];
        $next = $index + 1 < $length ? $sql[$index + 1] : '';

        if ($lineComment) {
            if ($char === "\n") {
                $lineComment = false;
                $buffer .= $char;
            }
            continue;
        }
        if ($blockComment) {
            if ($char === '*' && $next === '/') {
                $blockComment = false;
                $index++;
            }
            continue;
        }
        if ($quote !== null) {
            $buffer .= $char;
            if ($char === '\\' && $next !== '') {
                $buffer .= $next;
                $index++;
                continue;
            }
            if ($char === $quote) {
                if ($next === $quote) {
                    $buffer .= $next;
                    $index++;
                } else {
                    $quote = null;
                }
            }
            continue;
        }

        if (($char === '-' && $next === '-' && ($index + 2 >= $length || ctype_space($sql[$index + 2]))) || $char === '#') {
            $lineComment = true;
            if ($char === '-') {
                $index++;
            }
            continue;
        }
        if ($char === '/' && $next === '*') {
            $blockComment = true;
            $index++;
            continue;
        }
        if ($char === "'" || $char === '"' || $char === '`') {
            $quote = $char;
            $buffer .= $char;
            continue;
        }
        if ($char === ';') {
            if (trim($buffer) !== '') {
                $statements[] = trim($buffer);
            }
            $buffer = '';
            continue;
        }
        $buffer .= $char;
    }

    if (trim($buffer) !== '') {
        $statements[] = trim($buffer);
    }
    return $statements;
}

$migrationFiles = [
    '043_mg_general_parameters.sql',
    '044_mg_simplify_general_parameters.sql',
    '045_mg_solicitud_academic_evidence.sql',
];
$pdo = Database::connection();
if (class_exists(Pdo\Mysql::class)) {
    $pdo->setAttribute(Pdo\Mysql::ATTR_USE_BUFFERED_QUERY, true);
}
foreach ($migrationFiles as $file) {
    $path = dirname(__DIR__) . '/db/' . $file;
    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException('No se pudo leer la migración ' . $file . '.');
    }
    foreach (split_mysql_statements($sql) as $statement) {
        $result = $pdo->query($statement);
        if ($result instanceof PDOStatement) {
            do {
                if ($result->columnCount() > 0) {
                    $result->fetchAll(PDO::FETCH_ASSOC);
                }
            } while ($result->nextRowset());
            $result->closeCursor();
        }
    }
    echo 'Aplicada: ' . $file . PHP_EOL;
}
