<?php
$exports['mergeImpl'] = function($l) {
    return function($r) use ($l) {
        return \array_merge($r, $l);
    };
};
