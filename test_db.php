<?php
try {
    $dbh = new PDO('mysql:host=127.0.0.1;port=3306;dbname=hotel_management_system', 'root', '');
    echo 'CONNECTED';
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage();
}
