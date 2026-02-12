<?php

function pdo_common(array $cfg, string $user, string $pass): PDO {
    $dsn = sprintf( 
        "mysql:host=%s;dbname=%s;charset=%s",
        $cfg['host'],
        $cfg['name'],
        $cfg['charset']
    );
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function db_ro(): PDO {
    $config = require __DIR__ . '/config.php';
    $db =$config['db'];
    return pdo_common($db, $db['ro_user'], $db['ro_pass']);
}

function db_rw(): PDO {
    $config = require __DIR__ . '/config.php';
    $db = $config['db'];
    return pdo_common($db, $db['rw_user'], $db['rw_pass']);
}
?>