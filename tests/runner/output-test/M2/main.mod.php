<?php
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) require_once __DIR__ . '/../../vendor/autoload.php';
set_exception_handler(function($e) { echo 'FATAL: ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n"; exit(1); });
require_once __DIR__ . '/../M1/index.php';
require_once __DIR__ . '/../M2/index.php';
$GLOBALS['M2_main']();
if (class_exists('\\Revolt\\EventLoop')) { \Revolt\EventLoop::run(); }
