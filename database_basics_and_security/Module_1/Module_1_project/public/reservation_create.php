<?php
session_start();

require __DIR__ . '/../app/security.php';
require __DIR__ . '/../app/db.php';

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function fail(string $msg, int $code = 400): void {
    http_response_code($code);
    echo e($msg);
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pitch_id  = filter_input(INPUT_POST, 'pitch_id', FILTER_VALIDATE_INT);
$arrival   = $_POST['arrival_date'] ?? '';
$departure = $_POST['departure_date'] ?? '';
$notes     = $_POST['notes'] ?? null;

if (!$pitch_id) fail("Invalid pitch.", 422);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $arrival)) fail("Invalid arrival date.", 422);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $departure)) fail("Invalid departure date.", 422);
if ($departure <= $arrival) fail("Departure must be after arrival.", 422);

if ($notes !== null) {
    $notes = trim($notes);
    if ($notes === '') $notes = null;
    if (strlen($notes) > 500) fail("Notes too long.", 422);
}

$user_id = 1;
$public_id = bin2hex(random_bytes(16));

try {
    $pdo = db_rw();

    $sql = "INSERT INTO reservations
            (public_id, user_id, pitch_id, arrival_date, departure_date, notes)
            VALUES
            (:public_id, :user_id, :pitch_id, :arrival_date, :departure_date, :notes)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':public_id'      => $public_id,
        ':user_id'        => $user_id,
        ':pitch_id'       => $pitch_id,
        ':arrival_date'   => $arrival,
        ':departure_date' => $departure,
        ':notes'          => $notes,
    ]);

    http_response_code(201);
    echo "Reservation created. Public ID: " . e($public_id);

} catch (PDOException $ex) {
    echo $ex->getMessage();
    exit;
}

