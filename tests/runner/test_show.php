<?php
if (!\class_exists('\\PhpursThunks', false)) {
  class PhpursThunks {
    public static $thunks = [];
    public static $cache = [];
    public static function eval($id) {
      if (isset(self::$cache[$id]) || \array_key_exists($id, self::$cache)) return self::$cache[$id];
      if (isset(self::$thunks[$id])) {
        self::$cache[$id] = self::$thunks[$id]();
        return self::$cache[$id];
      }
      throw new \Exception("FATAL: Unknown thunk " . $id);
    }
  }
}
require_once __DIR__ . '/output/Main/index.php';
var_dump(\PhpursThunks::eval('Main_showFFI2')((object)["a" => 1, "b" => true, "c" => "d", "e" => 4.0]));
