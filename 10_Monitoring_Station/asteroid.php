<?php

// Copyright 2019 Max Sprauer

const BEST_X = 17;
const BEST_Y = 23;

// indexed [y][x]
$map = explode("\n", trim(file_get_contents('input.txt')));

// Create an array of asteroid locations
$locations = array();
foreach ($map as $y => $line) {
    for ($x = 0; $x < strlen($line); $x++) {
        if ($line[$x] == '#') {
            $locations[] = "$x,$y";
        }
    }
}
$totalAsteroids = count($locations);


// Creates a rays array for a given asteroid.
// array[quadrant id,slope to 5 decimal places] = array of "x,y" strings
// I could get fancy and work outward from the asteroid to find the closest asteroid
// on each ray, but instead I'm going to do it in a two-step process.
function getRaysForAsteroid(&$locations, int $originX, int $originY)
{
    $rays = array();
    
    foreach ($locations as $loc) {
        list($x, $y) = explode(',', $loc);
                
        if ($x == $originX && $y == $originY) {
            continue;
        }

        // These quadrant numbers are not the normal scheme; they're for the clockwise rotation in part 2.
        //  Q4        Q1  y = 0
        //       * 
        //  Q3        Q2  y > 0
        if ($x >= $originX) {
            $quad = ($y >= $originY) ? 'Q2' : 'Q1';
        } else {
            $quad = ($y >= $originY) ? 'Q3' : 'Q4';
        }

        //  Veritcal lines should only appear in Q1 and Q2 since those include $x == $originX.
        $diffX = $x - $originX;

        if ($diffX == 0) {
            // Instead of infinity/negative infinity, use big integers.  Call the downward
            // veritcal negative infinity in order to sort correctly.
            $slope = ($quad == 'Q2') ? 999999999 : -999999999;
        } else {
            $slope = round(($y - $originY) / ($diffX), 5);
        }

        $rays["$quad,$slope"][] = $loc;
    }

    return $rays;
}

function dist(int $x1, int $y1, int $x2, int $y2) : float
{
    return sqrt(pow($x2 - $x1, 2) + pow($y2 - $y1, 2));
}

// Part One
$counts = array();
foreach ($locations as $loc) {
    list($x, $y) = explode(',', $loc);
    $rays = getRaysForAsteroid($locations, $x, $y);
    $counts[$loc] = count($rays); 
}

asort($counts);
// print_r($counts);

// Part Two
print "Part Two\n";
$rays = getRaysForAsteroid($locations, BEST_X, BEST_Y);

// Sort rays in order of clockwise rotation
assert(uksort($rays, function($a, $b) {
    list($quadA, $slopeA) = explode(',', $a);
    list($quadB, $slopeB) = explode(',', $b);

    if (strcmp($quadA, $quadB) != 0) {
        return strcmp($quadA, $quadB);
    } else {
        $slopeA = floatval($slopeA);
        $slopeB = floatval($slopeB);
        
        // Compare slopes in the same quadrant
        if (abs($slopeA - $slopeB) < 0.0001) {
            assert(false, "Two rays with the same slope: $a $b");
            return 0;
        }

        return $slopeA - $slopeB < 0 ? -1 : 1;  // php really wants an int
    }
}));

// print_r($rays);
// exit;

// Sort asteroids on each ray in order of distance
foreach ($rays as $key => &$asteroids) {
    // list($quad, $slope) = explode(',', $key);
    usort($asteroids, function($a, $b) {
        $originX = BEST_X;
        $originY = BEST_Y;
        list($xa, $ya) = explode(',', $a);
        list($xb, $yb) = explode(',', $b);

        return dist($originX, $originY, $xa, $ya) - dist($originX, $originY, $xb, $yb);
    });
    
    if (count($asteroids) > 1) {
        print "$key: " . implode(', ', $asteroids) . "\n";
    }
}

// Spin and eliminate!
$count = 0;
$loop = 1;
do {
    foreach ($rays as $key => &$asteroids) {
        if (count($asteroids)) {
            $lasteroid = array_shift($asteroids);
            $count++;

            list($x, $y) = explode(',', $lasteroid);
            print "[Loop $loop] $count: $lasteroid\t Coord: $key\t Distance: " . dist(BEST_X, BEST_Y, $x, $y) . "\n";

            if ($count == $totalAsteroids - 1) {
                break 2;
            }
        }
    }
    $loop++;
} while (1);
