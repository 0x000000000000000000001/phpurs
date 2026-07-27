<?php

$exports['mergeImpl'] = function($l, $r) {
    return \array_merge((array) $r, (array) $l);
};
