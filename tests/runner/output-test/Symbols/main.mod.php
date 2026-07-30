<?php
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) require_once __DIR__ . '/../../vendor/autoload.php';
set_exception_handler(function($e) { echo 'FATAL: ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n"; exit(1); });
require_once __DIR__ . '/../Type.Proxy/index.php';
require_once __DIR__ . '/../Data.Eq/index.php';
require_once __DIR__ . '/../Control.Bind/index.php';
require_once __DIR__ . '/../Effect/index.php';
require_once __DIR__ . '/../Effect.Console/index.php';
require_once __DIR__ . '/../Test.Assert/index.php';
require_once __DIR__ . '/../Symbols/index.php';
$GLOBALS['Symbols_main']();
if (class_exists('\\Revolt\\EventLoop')) { \Revolt\EventLoop::run(); }
