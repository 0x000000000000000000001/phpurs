<?php
$entry = $argv[1];
require $argv[2];
$source = file_get_contents($entry);
preg_match_all("#require_once __DIR__ \. '/\.\./([^']+)/index\.php';#", $source, $matches);
$modules = $matches[1];
$started = hrtime(true);
foreach ($modules as $module) require_once dirname($entry) . '/../' . $module . '/index.php';
echo json_encode(['modules' => count($modules), 'ms' => (hrtime(true) - $started) / 1e6]), PHP_EOL;
