<?php
require 'C:/Users/rigna/OneDrive/Desktop/dev_stuff/database_basics_and_security/Module_1/app/db.php';

$pdo = db_ro();

$hasElectricity = filter_input(INPUT_GET, 'has_electricity', FILTER_VALIDATE_INT);

$sql = "SELECT code, has_electricity, created_at FROM pitches";
$params = [];

if ($hasElectricity !== null) {
    $sql .= " WHERE has_electricity = :has_electricity";
    $params[':has_electricity'] = $hasElectricity;
}

$sql .= " ORDER BY code";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pitches = $stmt->fetchAll();

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Pitches</title></head>
<body>
<h1>Pitches</h1>
<table border="1" cellpadding="6">
  <thead>
    <tr><th>Code</th><th>Electricity</th><th>Created</th></tr>
  </thead>
  <tbody>
    <?php foreach ($pitches as $p): ?>
      <tr>
        <td><?= e($p['code']) ?></td>
        <td><?= $p['has_electricity'] ? 'Yes' : 'No' ?></td>
        <td><?= e($p['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</body>
</html>
