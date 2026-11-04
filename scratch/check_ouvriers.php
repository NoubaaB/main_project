<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$ouv = DB::select("SELECT COUNT(*) as tot, COUNT(section_id) as with_sec, COUNT(corporal_id) as with_corp FROM villamar.ouvriers")[0];
echo "Ouvriers: Total={$ouv->tot}, with_sec={$ouv->with_sec}, with_corp={$ouv->with_corp}\n";

$corps = DB::select("SELECT id, name, section_id FROM villamar.corporals");
echo "Corporals count: " . count($corps) . "\n";
foreach (array_slice($corps, 0, 5) as $c) {
    echo " Corp #{$c->id}: {$c->name}, section_id={$c->section_id}\n";
}

$secs = DB::select("SELECT id, nome_section FROM villamar.sections");
echo "Sections count: " . count($secs) . "\n";
foreach (array_slice($secs, 0, 5) as $s) {
    echo " Sec #{$s->id}: {$s->nome_section}\n";
}
