<?php

// Copyright 2019 Max Sprauer

const MIN = 206938;
const MAX = 679128;

function isNonDecreasing(string $n) : bool
{
    $prevVal = 0;
    for ($i = 0; $i < strlen($n); $i++) {
        $val = $n[$i];

        if ($val < $prevVal) {
            return false;
        }

        $prevVal = $val;
    }

    return true;
}

// We know all numbers will be six digits long.
function nextNonDecreasing(string $n)
{
    $prevVal = 0;
    for ($i = 0; $i < strlen($n); $i++) {
        $val = $n[$i];

        // 143692
        if ($val < $prevVal) {
            $next = '';

            for ($j = 0; $j < $i; $j++) {
                $next[$j] = $n[$j];                
            }

            for ($j = $i; $j < strlen($n); $j++) {
                $next[$j] = $prevVal;
            }

            return $next;
        }

        $prevVal = $val;
    }

    if (isNonDecreasing((int) $n + 1)) {
        return (int) $n + 1;
    }

    return nextNonDecreasing((int) $n + 1);
}

function validPassword(string $pw) : bool
{
    $prevVal = 'x';
    for ($i = 0; $i < strlen($pw); $i++) {
        if ($pw[$i] == $prevVal) {
            return true;
        }
        $prevVal = $pw[$i];
    }
    return false;
}

// I bet a regex could do this
function validPasswordPartTwo(string $pw) : bool
{
    // 112233
    $groups = array();
    $prevVal = 'x';

    for ($i = 0; $i < strlen($pw); $i++) {
        if ($pw[$i] == $prevVal) {
            $groups[count($groups) - 1] .= $pw[$i];                        
        } else {
            $groups[] = $pw[$i];
        }

        $prevVal = $pw[$i];
    }

    foreach ($groups as $group) {
        if (strlen($group) == 2) {
            return true;
        }
    }

    return false;
}

/* test
foreach (array(599999, 123456, 654321, 65000, 666667, 666665, 600000) as $n) {
    print "$n: " . nextNonDecreasing($n) . ' ' . (validPassword($n) ? 'valid' : 'nonvalid') . "\n";
}
*/

// Part One
$validCount = 0;
$valid = array();
for ($n = nextNonDecreasing(MIN); $n <= MAX; $n = nextNonDecreasing($n)) {
    if (validPassword($n)) {
        $valid[] = $n;
        $validCount++;
    }
}

/* Brute force every number 
$validCount = 0;
$valid = array();
for ($n = MIN; $n <= MAX; $n++) {
    if (validPassword($n) && isNonDecreasing($n)) {
        $valid[] = $n;
        $validCount++;
    }
}
*/

print "Valid passwords: $validCount\n";
// print_r($valid);

/* test
foreach (array(112233, 123444, 111122, 599999, 123456, 654321, 65000, 666667, 666665, 600000) as $n) {
    print "$n: " . nextNonDecreasing($n) . ' ' . (validPasswordPartTwo($n) ? 'valid' : 'nonvalid') . "\n";
}
*/

// Part Two
$validCount = 0;
$valid = array();
for ($n = nextNonDecreasing(MIN); $n <= MAX; $n = nextNonDecreasing($n)) {
    if (validPasswordPartTwo($n)) {
        $valid[] = $n;
        $validCount++;
    }
}

/* Brute force every number 
$validCount = 0;
$valid = array();
for ($n = MIN; $n <= MAX; $n++) {
    if (validPasswordPartTwo($n) && isNonDecreasing($n)) {
        $valid[] = $n;
        $validCount++;
    }
}
*/

print "Valid passwords: $validCount\n";


