<?php

echo "START\n";
echo "PHP=".PHP_VERSION."\n";
echo "PDO_DRIVERS=".implode(',', PDO::getAvailableDrivers())."\n";
echo "PDO_MYSQL=".(extension_loaded('pdo_mysql') ? 'yes' : 'no')."\n";
echo "MYSQLI=".(extension_loaded('mysqli') ? 'yes' : 'no')."\n";

$fp = @fsockopen('127.0.0.1', 3306, $errno, $errstr, 3);
if ($fp) {
    echo "FSOCK=OK\n";
    fclose($fp);
} else {
    echo "FSOCK=FAIL {$errno} {$errstr}\n";
}

echo "TRY_PDO\n";
try {
    $dbh = new PDO('mysql:host=127.0.0.1;port=3306;dbname=hotel_management_system', 'root', '', [PDO::ATTR_TIMEOUT => 3]);
    echo "PDO_OK\n";
} catch (Throwable $e) {
    echo "PDO_ERR ". $e->getMessage() ."\n";
}

echo "END\n";
