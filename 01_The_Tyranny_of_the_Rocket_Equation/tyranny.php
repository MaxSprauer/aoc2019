<?php

// Copyright 2019 Max Sprauer

$modules = explode("\n", file_get_contents('input.txt'));

// Part One
$fuel = 0;
foreach ($modules as $m) {
    $fuel += (int) ($m / 3) - 2;
}

print "Fuel: $fuel\n";


// Part Two
$fuel = 0;

function fuelForModule($m)
{
    $fuel = 0;
    $delta = $m;

    do {
        $delta = (int) ($delta / 3) - 2;
        if ($delta > 0) {
            $fuel += $delta;
        }

    } while ($delta > 0);

    return $fuel;
}

foreach ($modules as $m) {
    $fuel += fuelForModule($m);
}

print "Fuel: $fuel\n";


