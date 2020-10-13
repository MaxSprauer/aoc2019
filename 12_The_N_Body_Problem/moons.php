<?php

// Copyright 2020 Max Sprauer

class Moon
{
    public $x, $y, $z, $vx, $vy, $vz, $initialState, $stepsToLoop;

    function __construct($x, $y, $z)
    {
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;        
        $this->vx = 0;
        $this->vy = 0;
        $this->vz = 0;

        $this->initialState['x'] = $x;
        $this->initialState['y'] = $y;
        $this->initialState['z'] = $z;
        $this->stepsToLoop = [];
    }

    function checkLoop($ticks)
    {
        foreach(['x', 'y', 'z'] as $axis) {
            if (!isset($this->stepsToLoop[$axis])) {
                $velocityField = "v{$axis}";
                if ($this->{$axis} == $this->initialState[$axis] && $this->{$velocityField} == 0) {
                    print "Found $axis loop: $ticks\n";
                    $this->stepsToLoop[$axis] = $ticks;
                }
            }
        }

        return (3 == count($this->stepsToLoop));
    }

    function matchesInitial($axis)
    {
        $velocityField = "v{$axis}";
        return (($this->{$axis} == $this->initialState[$axis]) && ($this->{$velocityField} == 0));
    }

    function startGravity()
    {
        /*
        $this->dx = 0;
        $this->dy = 0;
        $this->dz = 0;
        */
    }

    function calculateGravity(Moon $other)
    {
        foreach(['x', 'y', 'z'] as $axis) {
            $field = "v$axis";
            if ($other->{$axis} > $this->{$axis}) {
                $this->{$field}++;
            } else if ($other->{$axis} < $this->{$axis}) {
                $this->{$field}--;
            }
        }
    }

    function endGravity()
    {
        foreach(['x', 'y', 'z'] as $axis) {
            $field = "v$axis";
            $this->{$axis} += $this->{$field};
        }
    }

    function getTotalEnergy()
    {
        $pot = abs($this->x) + abs($this->y) + abs($this->z);
        $kin = abs($this->vx) + abs($this->vy) + abs($this->vz);
        return $pot * $kin; 
    }

    function printStatus()
    {
        $pot = abs($this->x) + abs($this->y) + abs($this->z);
        $kin = abs($this->vx) + abs($this->vy) + abs($this->vz);
        $tot = $pot * $kin;
       
        printf("pos=<x=%4d, y=%4d, z=%4d>, vel=<x=%4d, y=%4d, z=%4d>, potential=%4u kinetic=%4u total=%4u\n",
            $this->x,
            $this->y,
            $this->z,
            $this->vx,
            $this->vy,
            $this->vz,
            $pot,
            $kin,
            $tot        
        );

        return array($pot, $kin, $tot);
    }
}

function loadMoons($file = 'input.txt')
{
    $moons = array();
    $lines = explode("\n", trim(file_get_contents($file)));

    foreach ($lines as $line) {
        sscanf($line, '<x=%d, y=%d, z=%d>', $x, $y, $z);
        $moons[] = new Moon($x, $y, $z);
    }
    
    return $moons;
}

function timeStep(&$moons)
{
    array_map(function($m) { $m->startGravity(); }, $moons);

    array_map(function($m) use ($moons) { 
        array_map(function($other) use ($m) {
            if ($m !== $other) {
                $m->calculateGravity($other);
            }
        }, $moons);
    }, $moons);

    array_map(function($m) { $m->endGravity(); }, $moons);
}

// Part One
$moons = loadMoons();
$timeStep = 0;

while ($timeStep < 1000) {
    timeStep($moons);
    $timeStep++;
}

$total = array_reduce($moons, function (int $carry, Moon $m) { return $carry + $m->getTotalEnergy(); }, 0);
print "Part One Total: $total\n";
// print_r($moons);


// Part Two
/*
$moons = loadMoons('example1.txt');
$timeStep = 0;

while ($timeStep < 2773) {
    print "After $timeStep steps:\n";
    $pot = $kin = $tot = 0;
    $str = "$timeStep,";
    foreach ($moons as $moon) {
        list($p, $k, $t) = $moon->printStatus();
        $pot += $p;
        $kin += $k;
        $tot += $t;
        $sum = $pot + $kin;

        $str .= "$pot,$kin,$sum,$tot,";
    }

    //file_put_contents('sheet.csv', "$timeStep,$pot,$kin,$tot\n", FILE_APPEND);
    file_put_contents('sheet_moons.csv', "$str\n", FILE_APPEND);

    printf("potential = %4d, kinetic = %4d, sum = %4d, total = %4d\n", $pot, $kin, $pot + $kin, $tot);
    print "\n";

    timeStep($moons);
    $timeStep++;
}
exit;
*/

// Part Two: We could probably do all 4 moons at once, but this is fast enough.
// Hypothesis: Each moon has a repeating orbit of different length.  The time at
// which they all return to the same position is a product of each orbit length.
// A key part of that that I picked up by reading the bare mininum hint below
// is that each axis is independent.  So for one moon, if X repeats every 2 ticks,
// Y repeats every 3 ticks, and Z repeats every 4 ticks, then the orbit is 12 ticks.
// BUT solving it that way didn't work (still not sure why, maybe the numbers were too big).  
// I had to solve for the position of all four moons on each axis, then find the least
// common multiple of those three periods.
// https://www.reddit.com/r/adventofcode/comments/e9jxh2/help_2019_day_12_part_2_what_am_i_not_seeing/

$moons = loadMoons();

$timeSteps = 0;
$matchTimes = array();

do {
    $done = true;
    timeStep($moons);
    $timeSteps++;

    foreach(['x', 'y', 'z'] as $axis) {        
        if (!isset($matchTimes[$axis])) {
            $match = true;
            for ($ii = 0; $ii < 4; $ii++) {
                $match = $match && $moons[$ii]->matchesInitial($axis);
            }

            if ($match) {
                $matchTimes[$axis] = $timeSteps;
            }
        }
    }

    $done = (count($matchTimes) == 3);
} while (!$done);

print_r($matchTimes);
// Least Common Multiple = 327,636,285,682,704
