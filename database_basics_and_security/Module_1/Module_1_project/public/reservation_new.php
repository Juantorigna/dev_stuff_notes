<?php
session_start();

require __DIR__ . '/../app/security.php';
require __DIR__ . '/../app/db.php';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pdo = db_ro();
$stmt = $pdo->prepare("SELECT id, code FROM pitches ORDER BY code");
$stmt->execute();
$pitches = $stmt->fetchAll();

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>New Reservation</title></head>
<body>
<h1>Create Reservation</h1>

<form method="post" action="reservation_create.php" id="resForm">
  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

  <label>Pitch:</label>
  <select name="pitch_id" required>
    <?php foreach ($pitches as $p): ?>
      <option value="<?= (int)$p['id'] ?>"><?= e($p['code']) ?></option>
    <?php endforeach; ?>
  </select>
  <br><br>

  <label>Arrival date:</label>
  <input type="date" name="arrival_date" required>
  <br><br>

  <label>Departure date:</label>
  <input type="date" name="departure_date" required>
  <br><br>

  <label>Notes (optional):</label><br>
  <textarea name="notes" maxlength="500"></textarea>
  <br><br>

  <button type="submit">Create</button>
</form>

<script>
document.getElementById('resForm').addEventListener('submit', (e) => {
  const a = document.querySelector('[name="arrival_date"]').value;
  const d = document.querySelector('[name="departure_date"]').value;
  if (a && d && d <= a) {
    e.preventDefault();
    alert("Departure must be after arrival.");
  }
});
</script>
</body>
</html>
