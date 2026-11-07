<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=villamar', 'root', '');
$stmt = $pdo->query('SELECT count(*) as total, 
    sum(case when corporal_id is not null and corporal_id > 0 then 1 else 0 end) as has_corp,
    sum(case when section_id is not null and section_id > 0 then 1 else 0 end) as has_sec
    FROM ouvriers');
print_r($stmt->fetch(PDO::FETCH_ASSOC));

$stmt2 = $pdo->query('SELECT count(*) as total, 
    sum(case when section_id is not null and section_id > 0 then 1 else 0 end) as has_sec
    FROM pointages');
print_r($stmt2->fetch(PDO::FETCH_ASSOC));

$stmt3 = $pdo->query('SELECT id, section_id FROM corporals WHERE section_id IS NOT NULL LIMIT 5');
print_r($stmt3->fetchAll(PDO::FETCH_ASSOC));
