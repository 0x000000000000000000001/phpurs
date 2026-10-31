<?php

namespace Demo;

// ALL IMPORTS: 
// TO REQUIRE: 



$GLOBALS['Prim_undefined'] = function() { throw new \Exception("undefined"); };




// Demo_list
$GLOBALS['Demo_list'] = [1, 2, 3];

// Demo_matrix
$GLOBALS['Demo_matrix'] = [[1], [2, 3]];

// Demo_pair
$GLOBALS['Demo_pair'] = (object)["left" => 1, "right" => true];

