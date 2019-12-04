<?php

// Copyright 2019 Max Sprauer

function getDistance($x1, $y1, $x2, $y2)
{
    // Since we are dealing with horizontal or vertical lines, only one will be nonzero
    return abs($x1 - $x2) + abs($y1 - $y2);
}

class Line {
    public $x1, $x2, $y1, $y2;

    public function __construct($x1, $y1) 
    {
        $this->x1 = $x1;
        $this->y1 = $y1;
    }

    public function __toString()
    {
        return sprintf("(%5d,%5d), (%5d,%5d)", $this->x1, $this->y1, $this->x2, $this->y2);
    }

    // These could be cached when the Line is created
    public function horizontal() : bool
    {
        return ($this->x1 != $this->x2 && $this->y1 == $this->y2);
    }

    public function vertical() : bool
    {
        return ($this->x1 == $this->x2 && $this->y1 != $this->y2);
    }

    public function minX() : int
    {
        return min($this->x1, $this->x2);
    }

    public function minY() : int
    {
        return min($this->y1, $this->y2);
    }

    public function maxX() : int
    {
        return max($this->x1, $this->x2);
    }

    public function maxY() : int
    {
        return max($this->y1, $this->y2);
    }

    public function length() : int
    {
        return getDistance($this->x1, $this->y1, $this->x2, $this->y2);
    }

    // returns array(x, y) or FALSE
    public function intersection(Line $l2)
    {
        // Finding min/max would be easier if we sorted each line's coordinates as they were created.
        if ($this->horizontal() && $l2->vertical()) {
            if ($this->minX() <= $l2->x1 && $l2->x1 <= $this->maxX() && $l2->minY() <= $this->y1 && $this->y1 <= $l2->maxY()) {
                // print "$this\n$l2\n\n";
                return [$l2->x1, $this->y1];
            }
        } else if ($this->vertical() && $l2->horizontal()) {
            if ($this->minY() <= $l2->y1 && $l2->y1 <= $this->maxY() && $l2->minX() <= $this->x1 && $this->x1 <= $l2->maxX()) {
                // print "$this\n$l2\n\n";

                return [$this->x1, $l2->y1];
            }
        }

        return FALSE;
    }
}

class Wire {
    public $lines = array();

    private function __construct() {}
    
    public static function NewFromDirections($dirs)
    {
        $curX = $curY = 0;
        $wire = new Wire();

        foreach ($dirs as $dir) {
            assert(preg_match('/([UDLR])(\d+)/i', $dir, $m), "Illegal direction: $dir");
            $line = new Line($curX, $curY);
            $dist = $m[2];

            switch ($m[1]) {
                case 'U':
                    $curY += $dist;
                    break;

                case 'D':
                    $curY -= $dist;
                    break;

                case 'L':
                    $curX -= $dist;
                    break;

                case 'R':
                    $curX += $dist;
                    break;
            }

            $line->x2 = $curX;
            $line->y2 = $curY;
            $wire->lines[] = $line;
        }

        return $wire;
    }

    public function __toString()
    {
        $str = '';
        foreach ($this->lines as $line) {
            $str .= "$line\n";
        }
        return $str;
    }

    public function getIntersections(Wire $w2) : array
    {
        $intersections = array();

        foreach ($this->lines as $line1) {
            foreach ($w2->lines as $line2) {
                $r = $line1->intersection($line2);
                // Do not count the origin
                if ($r !== FALSE && !($r[0] == 0 && $r[1] == 0)) {
                    $intersections[] = $r;
                }
            }
        }

        return $intersections;
    }

    public function findClosestIntersection(Wire $w2)
    {
        $dist1 = 0;
        $dist2 = 0;
        $bestAnswer = PHP_INT_MAX;

        foreach ($this->lines as $line1) {
            $dist2 = 0;

            foreach ($w2->lines as $line2) {
                $r = $line1->intersection($line2);
                // Do not count the origin
                if ($r !== FALSE && !($r[0] == 0 && $r[1] == 0)) {
                    // We found an intersection.  Add the partial distance for each line to the intersection.
                    $answer = $dist1 + $dist2 + getDistance($line1->x1, $line1->y1, $r[0], $r[1])
                        + getDistance($line2->x1, $line2->y1, $r[0], $r[1]);
                    // print "int: {$r[0]} {$r[1]}: $answer\n";
                    if ($answer < $bestAnswer) {
                        $bestAnswer = $answer;
                    }
                }

                $dist2 += $line2->length();
            }

            $dist1 += $line1->length();
        }

        return $bestAnswer;
    }
}

$strings = file_get_contents('input.txt');
$wires = explode("\n", $strings, 2);
$wireDirs1 = explode(',', $wires[0]);
$wireDirs2 = explode(',', $wires[1]);

$wire1 = Wire::NewFromDirections($wireDirs1);
$wire2 = Wire::NewFromDirections($wireDirs2);
// print "$wire1\n";
// print "$wire2\n";

// Part One
$inters = $wire1->getIntersections($wire2);
// print_r($inters);
$minDist = PHP_INT_MAX;

foreach ($inters as $i) {
    // Manhattan distance
    $dist = abs($i[0]) + abs($i[1]); 
    if ($dist < $minDist) {
        $minDist = $dist;
    }
}

print "Part 1: $minDist\n";

// Part Two
// The answer should be the same whichever wire we iterate
print "Part 2: {$wire1->findClosestIntersection($wire2)}\n";
