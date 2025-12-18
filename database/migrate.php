<?php
// database/migrate.php

require_once 'db.php';

$sqlFile = __DIR__ . '/dispatch_system.sql';

if (!file_exists($sqlFile)) {
    die("Error: SQL file not found at $sqlFile");
}

$checksum = sha1_file($sqlFile);

function current_db_name(mysqli $conn): string {
    $res = $conn->query("SELECT DATABASE() AS db");
    $row = $res ? $res->fetch_assoc() : null;
    return $row && !empty($row['db']) ? $row['db'] : '';
}

function ensure_migrations_table(mysqli $conn): void {
    $conn->query("
        CREATE TABLE IF NOT EXISTS `schema_migrations` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `checksum` CHAR(40) NOT NULL,
            `applied_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_checksum` (`checksum`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
}

function latest_checksum(mysqli $conn): ?string {
    $res = $conn->query("SELECT `checksum` FROM `schema_migrations` ORDER BY `id` DESC LIMIT 1");
    if (!$res) {
        return null;
    }
    $row = $res->fetch_assoc();
    return $row ? $row['checksum'] : null;
}

function tokenize_statements(string $sql): array {
    $statements = [];
    $buf = '';
    $len = strlen($sql);
    $inString = false;
    $stringQuote = '';
    $inLineComment = false;
    $inBlockComment = false;

    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];
        $next = ($i + 1 < $len) ? $sql[$i + 1] : '';

        if ($inLineComment) {
            if ($ch === "\n") {
                $inLineComment = false;
                $buf .= $ch;
            }
            continue;
        }

        if ($inBlockComment) {
            if ($ch === '*' && $next === '/') {
                $inBlockComment = false;
                $i++;
            }
            continue;
        }

        if (!$inString) {
            if ($ch === '-' && $next === '-') {
                $prev = ($i > 0) ? $sql[$i - 1] : "\n";
                if ($prev === "\n" || $prev === "\r" || $prev === "\t" || $prev === ' ') {
                    $inLineComment = true;
                    $i++;
                    continue;
                }
            }
            if ($ch === '#') {
                $inLineComment = true;
                continue;
            }
            if ($ch === '/' && $next === '*') {
                $inBlockComment = true;
                $i++;
                continue;
            }
        }

        if ($ch === "'" || $ch === '"') {
            if ($inString) {
                if ($ch === $stringQuote) {
                    $escaped = ($i > 0 && $sql[$i - 1] === '\\');
                    if (!$escaped) {
                        $inString = false;
                        $stringQuote = '';
                    }
                }
            } else {
                $inString = true;
                $stringQuote = $ch;
            }
            $buf .= $ch;
            continue;
        }

        if (!$inString && $ch === ';') {
            $stmt = trim($buf);
            if ($stmt !== '') {
                $statements[] = $stmt;
            }
            $buf = '';
            continue;
        }

        $buf .= $ch;
    }

    $tail = trim($buf);
    if ($tail !== '') {
        $statements[] = $tail;
    }

    return $statements;
}

function table_exists(mysqli $conn, string $dbName, string $table): bool {
    $stmt = $conn->prepare("
        SELECT 1
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
        LIMIT 1
    ");
    $stmt->bind_param("ss", $dbName, $table);
    $stmt->execute();
    $res = $stmt->get_result();
    $exists = $res && $res->num_rows > 0;
    $stmt->close();
    return $exists;
}

function column_exists(mysqli $conn, string $dbName, string $table, string $column): bool {
    $stmt = $conn->prepare("
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
        LIMIT 1
    ");
    $stmt->bind_param("sss", $dbName, $table, $column);
    $stmt->execute();
    $res = $stmt->get_result();
    $exists = $res && $res->num_rows > 0;
    $stmt->close();
    return $exists;
}

function index_exists(mysqli $conn, string $dbName, string $table, string $indexName): bool {
    $stmt = $conn->prepare("
        SELECT 1
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?
        LIMIT 1
    ");
    $stmt->bind_param("sss", $dbName, $table, $indexName);
    $stmt->execute();
    $res = $stmt->get_result();
    $exists = $res && $res->num_rows > 0;
    $stmt->close();
    return $exists;
}

function constraint_exists(mysqli $conn, string $dbName, string $table, string $constraintName): bool {
    $stmt = $conn->prepare("
        SELECT 1
        FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?
        LIMIT 1
    ");
    $stmt->bind_param("sss", $dbName, $table, $constraintName);
    $stmt->execute();
    $res = $stmt->get_result();
    $exists = $res && $res->num_rows > 0;
    $stmt->close();
    return $exists;
}

function split_alter_ops(string $ops): array {
    $parts = [];
    $buf = '';
    $depth = 0;
    $len = strlen($ops);
    $inString = false;
    $quote = '';

    for ($i = 0; $i < $len; $i++) {
        $ch = $ops[$i];
        if ($ch === "'" || $ch === '"') {
            if ($inString) {
                if ($ch === $quote) {
                    $escaped = ($i > 0 && $ops[$i - 1] === '\\');
                    if (!$escaped) {
                        $inString = false;
                        $quote = '';
                    }
                }
            } else {
                $inString = true;
                $quote = $ch;
            }
            $buf .= $ch;
            continue;
        }

        if (!$inString) {
            if ($ch === '(') {
                $depth++;
            } elseif ($ch === ')') {
                $depth = max(0, $depth - 1);
            } elseif ($ch === ',' && $depth === 0) {
                $part = trim($buf);
                if ($part !== '') {
                    $parts[] = $part;
                }
                $buf = '';
                continue;
            }
        }

        $buf .= $ch;
    }

    $tail = trim($buf);
    if ($tail !== '') {
        $parts[] = $tail;
    }

    return $parts;
}

function apply_create_table(mysqli $conn, string $dbName, string $stmt): void {
    if (!preg_match('/^CREATE\s+TABLE\s+(`?)([a-zA-Z0-9_]+)\1\s*\(/i', $stmt, $m)) {
        return;
    }

    $table = $m[2];
    $create = preg_replace('/^CREATE\s+TABLE\s+/i', 'CREATE TABLE IF NOT EXISTS ', $stmt, 1);

    try {
        $conn->query($create);
        echo "Ensured table exists: {$table}<br>";
    } catch (mysqli_sql_exception $e) {
        if ((int)$e->getCode() !== 1050) {
            throw $e;
        }
    }

    if (!preg_match('/^CREATE\s+TABLE\s+`?' . preg_quote($table, '/') . '`?\s*\((.*)\)\s*ENGINE=/is', $stmt, $mcols)) {
        return;
    }

    $inside = $mcols[1];
    $lines = preg_split('/\r\n|\r|\n/', $inside);
    foreach ($lines as $line) {
        $line = trim($line);
        $line = rtrim($line, ",");
        if (!preg_match('/^`([^`]+)`\s+(.+)$/', $line, $cm)) {
            continue;
        }
        $colName = $cm[1];
        $colDefRemainder = $cm[2];

        if (column_exists($conn, $dbName, $table, $colName)) {
            continue;
        }

        $alter = "ALTER TABLE `{$table}` ADD COLUMN `{$colName}` {$colDefRemainder}";
        try {
            $conn->query($alter);
            echo "Added column: {$table}.{$colName}<br>";
        } catch (mysqli_sql_exception $e) {
            echo "Skipped column add (failed): {$table}.{$colName} ({$e->getMessage()})<br>";
        }
    }
}

function apply_alter_table(mysqli $conn, string $dbName, string $stmt): void {
    if (!preg_match('/^ALTER\s+TABLE\s+(`?)([a-zA-Z0-9_]+)\1\s+(.*)$/is', $stmt, $m)) {
        return;
    }

    $table = $m[2];
    $opsRaw = trim($m[3]);
    $ops = split_alter_ops($opsRaw);

    foreach ($ops as $op) {
        $opTrim = trim($op);

        if (preg_match('/^ADD\s+COLUMN\s+`?([a-zA-Z0-9_]+)`?\s+/i', $opTrim, $cm)) {
            $col = $cm[1];
            if (column_exists($conn, $dbName, $table, $col)) {
                continue;
            }
            $sql = "ALTER TABLE `{$table}` {$opTrim}";
            $conn->query($sql);
            echo "Altered table (add column): {$table}.{$col}<br>";
            continue;
        }

        if (preg_match('/^DROP\s+INDEX\s+(IF\s+EXISTS\s+)?`?([a-zA-Z0-9_]+)`?/i', $opTrim, $im)) {
            $idx = $im[2];
            if (!index_exists($conn, $dbName, $table, $idx)) {
                continue;
            }
            $conn->query("ALTER TABLE `{$table}` DROP INDEX `{$idx}`");
            echo "Dropped index: {$table}.{$idx}<br>";
            continue;
        }

        if (preg_match('/^ADD\s+(UNIQUE\s+)?KEY\s+`?([a-zA-Z0-9_]+)`?/i', $opTrim, $im)) {
            $idx = $im[2];
            if (index_exists($conn, $dbName, $table, $idx)) {
                continue;
            }
            $conn->query("ALTER TABLE `{$table}` {$opTrim}");
            echo "Added index: {$table}.{$idx}<br>";
            continue;
        }

        if (preg_match('/^ADD\s+PRIMARY\s+KEY/i', $opTrim)) {
            if (index_exists($conn, $dbName, $table, 'PRIMARY')) {
                continue;
            }
            $conn->query("ALTER TABLE `{$table}` {$opTrim}");
            echo "Added primary key: {$table}<br>";
            continue;
        }

        if (preg_match('/^ADD\s+CONSTRAINT\s+`?([a-zA-Z0-9_]+)`?/i', $opTrim, $cm)) {
            $cname = $cm[1];
            if (constraint_exists($conn, $dbName, $table, $cname)) {
                continue;
            }
            $conn->query("ALTER TABLE `{$table}` {$opTrim}");
            echo "Added constraint: {$table}.{$cname}<br>";
            continue;
        }

        if (preg_match('/^MODIFY\s+`?([a-zA-Z0-9_]+)`?\s+/i', $opTrim, $cm)) {
            $col = $cm[1];
            if (!column_exists($conn, $dbName, $table, $col)) {
                continue;
            }
            try {
                $conn->query("ALTER TABLE `{$table}` {$opTrim}");
                echo "Modified column: {$table}.{$col}<br>";
            } catch (mysqli_sql_exception $e) {
                echo "Skipped modify (failed): {$table}.{$col} ({$e->getMessage()})<br>";
            }
            continue;
        }

        try {
            $conn->query("ALTER TABLE `{$table}` {$opTrim}");
            echo "Altered table: {$table}<br>";
        } catch (mysqli_sql_exception $e) {
            echo "Skipped alter op (failed): {$table} ({$e->getMessage()})<br>";
        }
    }
}

function apply_create_index(mysqli $conn, string $dbName, string $stmt): void {
    if (!preg_match('/^CREATE\s+(UNIQUE\s+)?INDEX\s+`?([a-zA-Z0-9_]+)`?\s+ON\s+`?([a-zA-Z0-9_]+)`?\s*\(/i', $stmt, $m)) {
        return;
    }
    $idx = $m[2];
    $table = $m[3];
    if (index_exists($conn, $dbName, $table, $idx)) {
        return;
    }
    $conn->query($stmt);
    echo "Created index: {$table}.{$idx}<br>";
}

echo "Starting migration...<br>";
echo "Reading from: " . basename($sqlFile) . "<br>";

ensure_migrations_table($conn);
$dbName = current_db_name($conn);

$last = latest_checksum($conn);
if ($last === $checksum) {
    echo "Database schema is already up to date.<br>";
    $conn->close();
    exit;
}

$sql = file_get_contents($sqlFile);
if ($sql === false || trim($sql) === '') {
    die("Error: SQL file is empty");
}

$statements = tokenize_statements($sql);

try {
    foreach ($statements as $stmt) {
        $t = ltrim($stmt);
        if ($t === '') {
            continue;
        }
        if (preg_match('/^(SET|START\s+TRANSACTION|COMMIT|LOCK\s+TABLES|UNLOCK\s+TABLES)\b/i', $t)) {
            continue;
        }
        if (preg_match('/^(INSERT|SELECT)\b/i', $t)) {
            continue;
        }
        if (preg_match('/^UPDATE\s+queue\b/i', $t)) {
            if (column_exists($conn, $dbName, 'queue', 'queue_date')) {
                try {
                    $conn->query($t);
                    echo "Updated queue dates.<br>";
                } catch (mysqli_sql_exception $e) {
                    echo "Skipped queue update (failed): {$e->getMessage()}<br>";
                }
            }
            continue;
        }
        if (preg_match('/^CREATE\s+DATABASE\b/i', $t)) {
            continue;
        }
        if (preg_match('/^DROP\s+TABLE\b/i', $t)) {
            continue;
        }

        if (preg_match('/^CREATE\s+TABLE\b/i', $t)) {
            apply_create_table($conn, $dbName, $t);
            continue;
        }
        if (preg_match('/^ALTER\s+TABLE\b/i', $t)) {
            apply_alter_table($conn, $dbName, $t);
            continue;
        }
        if (preg_match('/^CREATE\s+(UNIQUE\s+)?INDEX\b/i', $t)) {
            apply_create_index($conn, $dbName, $t);
            continue;
        }
    }

    $stmt = $conn->prepare("INSERT INTO `schema_migrations` (`checksum`) VALUES (?)");
    $stmt->bind_param("s", $checksum);
    $stmt->execute();
    $stmt->close();

    echo "Migration completed successfully.<br>";
} catch (mysqli_sql_exception $e) {
    echo "Migration failed with error: " . $e->getMessage() . "<br>";
}

$conn->close();
?>
