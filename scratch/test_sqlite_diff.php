<?php
$pdo = new PDO('sqlite::memory:');
$stmt = $pdo->query("SELECT ROUND(julianday('now') - julianday('2026-10-25')) as diff");
$res = $stmt->fetch(PDO::FETCH_ASSOC);
echo "SQLite diff: " . $res['diff'] . "\n";

