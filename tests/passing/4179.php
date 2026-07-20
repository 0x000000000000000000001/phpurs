<?php

$runtimeImportImpl = function($nothing) {
    return function($just) use ($nothing) {
        return function($moduleName) use ($nothing, $just) {
            return function($body) use ($nothing, $just, $moduleName) {
                return function() use ($nothing, $just, $moduleName, $body) {
                    try {
                        foreach (array_keys($GLOBALS) as $key) {
                            if (str_starts_with($key, str_replace('.', '_', $moduleName) . "_")) {
                                \PhpursThunks::eval($key);
                            }
                        }
                        return $body($nothing)();
                    } catch (\Throwable $e) {
                        return $body($just(new \Data\Maybe\Phpurs_Data1("Just", $e->getMessage())))();
                    }
                };
            };
        };
    };
};

$exports['runtimeImportImpl'] = $runtimeImportImpl;

