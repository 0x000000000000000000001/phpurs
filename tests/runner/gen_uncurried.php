<?php
$out = "<?php\n\n";

for ($i = 0; $i <= 10; $i++) {
    $out .= "\$mkFn$i = function(\$fn) use (&\$mkFn$i) {\n";
    $out .= "    if (\\func_num_args() < 1) {\n";
    $out .= "        \$__args = \\func_get_args();\n";
    $out .= "        return function(...\$more) use (\$__args, &\$mkFn$i) {\n";
    $out .= "            return \$mkFn$i(...\\array_merge(\$__args, \$more));\n";
    $out .= "        };\n";
    $out .= "    }\n";
    
    $args = [];
    for ($j = 0; $j < $i; $j++) {
        $args[] = "\$a$j = null";
    }
    $argsStr = implode(", ", $args);
    
    $out .= "    return function($argsStr) use (\$fn) {\n";
    if ($i === 0) {
        $out .= "        return \$fn(null);\n";
    } else {
        $call = "\$fn";
        for ($j = 0; $j < $i; $j++) {
            $call .= "(\$a$j)";
        }
        $out .= "        return $call;\n";
    }
    $out .= "    };\n";
    $out .= "};\n";
}

for ($i = 0; $i <= 10; $i++) {
    $argsDef = ["\$fn = null"];
    $argsCall = [];
    for ($j = 0; $j < $i; $j++) {
        $argsDef[] = "\$a$j = null";
        $argsCall[] = "\$a$j";
    }
    $argsDefStr = implode(", ", $argsDef);
    $argsCallStr = implode(", ", $argsCall);
    $expectedArgs = $i + 1;
    
    $out .= "\$runFn$i = function($argsDefStr) use (&\$runFn$i) {\n";
    $out .= "    if (\\func_num_args() < $expectedArgs) {\n";
    $out .= "        \$__args = \\func_get_args();\n";
    $out .= "        return function(...\$more) use (\$__args, &\$runFn$i) {\n";
    $out .= "            return \$runFn$i(...\\array_merge(\$__args, \$more));\n";
    $out .= "        };\n";
    $out .= "    }\n";
    if ($i === 0) {
        $out .= "    return \$fn();\n";
    } else {
        $out .= "    return \$fn($argsCallStr);\n";
    }
    $out .= "};\n";
}

for ($i = 0; $i <= 10; $i++) {
    $out .= "\$exports['mkFn$i'] = \$mkFn$i;\n";
    $out .= "\$exports['runFn$i'] = \$runFn$i;\n";
}
$out .= "return \$exports;\n";

file_put_contents("/Users/0x1/Documents/htdocs/phpurs-functions/src/Data/Function/Uncurried.php", $out);
echo "Done generating Uncurried.php\n";
