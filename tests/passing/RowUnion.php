<?php

$exports['mergeImpl'] = function($l, $r) {
    return (object) \array_merge((array) $r, (array) $l);
};
