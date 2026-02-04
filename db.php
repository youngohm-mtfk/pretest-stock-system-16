<?php
$host = 'stock_db';
$db = 'stock_system';
$user = 'user';
$pass = 'password';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Initialize schema if needed
    $sql = file_get_contents(__DIR__ . '/init.sql');
    if ($sql) {
        // Simple split by semicolon to handle multiple statements
        // Note: This is a basic split, but usually works for simple init scripts
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $statement) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Ignore errors like "Table already exists" or "Column already exists"
                // during initialization if using CREATE TABLE IF NOT EXISTS
            }
        }
    }
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int) $e->getCode());
}
?>