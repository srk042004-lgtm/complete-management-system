<?php

echo "START\n";
file_put_contents(__DIR__ . '/test_db2_log.txt', "START\n", FILE_APPEND);

try {
    $dbh = new PDO('mysql:host=127.0.0.1;port=3306;dbname=hotel_management_system', 'root', '', [PDO::ATTR_TIMEOUT => 5]);
    echo "CONNECTED\n";
    file_put_contents(__DIR__ . '/test_db2_log.txt', "CONNECTED\n", FILE_APPEND);
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    file_put_contents(__DIR__ . '/test_db2_log.txt', "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
}

echo "END\n";
file_put_contents(__DIR__ . '/test_db2_log.txt', "END\n", FILE_APPEND);
