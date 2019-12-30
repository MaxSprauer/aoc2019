<?php

// Copyright 2019 Max Sprauer

$lines = explode("\n", trim(file_get_contents('test_2.txt')));

class Reaction {
    public $result;     // Compound
    public $inputs;     // Array of Compounds
    public function isMadeFromOre()
    {
        return count($this->inputs) == 1 && $this->inputs[0]->element == 'ORE';
    }
}

class Compound {
    public $count;
    public $element;
}

$reactions = array();       // Array of all parsed Reactions
$bblocks = array();         // Array of all "building block" Reactions built from ore
// $fuel = null;               // Reaction that creates Fuel

// Find Reaction that produces element by Name
function findElement($name) : Reaction
{
    global $reactions;
    foreach ($reactions as &$r) {
        if ($r->result->element == $name) {
            return $r;
        }
    }
    assert(false, "Element $name not found");
}

function parseLines($lines) 
{
    global $reactions, $bblocks;

    foreach ($lines as $line) {
        list($ingredients, $result) = explode(' => ', trim($line));
        assert(isset($ingredients) && isset($result), "Bad line: $line");
        
        $r = new Reaction();
        list($count, $element) = sscanf($result, '%u %s');
        $c = new Compound();
        $c->count = $count;
        $c->element = $element;
        $r->result = $c;
        $reactions[] = $r;

        $inputs = explode(', ', $ingredients);
        assert(count($inputs) >= 1, "No ingredients found: $line");

        foreach ($inputs as $input) {
            list($count, $element) = sscanf($input, '%u %s');
            $i = new Compound();
            $i->count = $count;
            $i->element = $element;
            $r->inputs[] = $i;
        }

        if ($r->isMadeFromOre()) {
            $bblocks[] = $r->result->element;
        }
    }
}

$needed = ['FUEL' => 1];

/*
function dfs($product)
{
    global $needed, $bblocks;

    $el = findElement($product);
    assert(isset($needed[$product]));
    $quant = isset($needed[$product]) ? $needed[$product] : 1;
    $scale = findLeastMultiple($el->result->count, $quant) / $el->result->count;

    foreach ($el->inputs as $in) {
        if ($in->element != 'ORE') {

        // if (in_array($in->element, $bblocks)) {
            if (!isset($needed[$in->element])) {
                $needed[$in->element] = $in->count * $scale;
            } else {
                $needed[$in->element] += $in->count * $scale;
            }
        //}

            dfs($in->element);
        }
    }
}
*/

function dfs($product)
{
    global $needed, $bblocks;

    $el = findElement($product);
    assert(isset($needed[$product]));
    $quant = isset($needed[$product]) ? $needed[$product] : 1;
    $newQuant = findLeastMultiple($el->result->count, $quant);
    $scale = $newQuant / $el->result->count;
    // This may produce an excess of the output.  We should bank that excess so it can be used.
    if ($newQuant > $quant) {
        print "$product: $quant -> $newQuant\n";
        $needed[$product] -= ($newQuant - $quant);
    }

    foreach ($el->inputs as $in) {
        if ($in->element != 'ORE') {

        // if (in_array($in->element, $bblocks)) {
            if (!isset($needed[$in->element])) {
                $needed[$in->element] = $in->count * $scale;
            } else {
                $needed[$in->element] += $in->count * $scale;
            }
        //}

            dfs($in->element);
        }
    }
}

parseLines($lines);

dfs('FUEL');

print "Amount of each building block needed:\n";
print_r($needed);

$ore = 0;
foreach ($needed as $n => &$count) {
    if ($n == 'ORE') {
        continue;
    }
    
    $r = findElement($n);    
    if ($r->isMadeFromOre()) {
        // Increase count to next multiple if necessary
        $count = findLeastMultiple($r->result->count, $count);

        $orePerBatch = $r->inputs[0]->count;
        $batches = $count / $r->result->count;
        print "$batches\n";
        $cost = $orePerBatch * $batches;
        $ore += $orePerBatch * $batches;

        print "$n needs $batches groups of {$r->result->count} costing $cost ore.\n";
    }
}

print "Amount of each building block needed (rounded up):\n";
print_r($needed);


print "Amount of ore needed: $ore\n";


exit;

// Track each element back to ore.  Breadth-first search.  Sum up elements required at each level.
function examineLevel(array $levelReactions) {
    $nextLevelReactions = array();
    $levelNeeded = array();

    foreach ($levelReactions as $r) {
        foreach ($r->inputs as $i) {
            if (isset($levelNeeded[$i->element])) {
                $levelNeeded[$i->element] += $i->count;
            } else {
                $levelNeeded[$i->element] = $i->count;
            }

            if ($i->element != 'ORE') {
                $nextLevelReactions[] = findElement($i->element);
            }
        }
    }

    print_r($levelNeeded);

    if (!empty($nextLevelReactions)) {
        examineLevel($nextLevelReactions);
    }
}

function findLeastMultiple(int $num, int $min) : int
{
    for ($i = 1;; $i++) {
        if ($num * $i >= $min) {
            return $num * $i;
        }
    }
}

function examineLevel2(array $levelNeeded) 
{
    // $nextLevelReactions = array();
    $nextLevelNeeded = array();

    foreach ($levelNeeded as $name => $quant) {
        if ($name == 'ORE') {
            print "Ore found.\n";
            continue;
        }

        $r = findElement($name);        
        $quant = findLeastMultiple($r->result->count, $quant);
        print "Least multiple for $name: $quant\n";

        foreach ($r->inputs as $i) {
            if (!isset($nextLevelNeeded[$i->element])) {
                $nextLevelNeeded[$i->element] = 0;
            }
            // print "{$i->count} * $quant / {$r->result->count} = " . $i->count * $quant / $r->result->count . "\n";
            if ($i->count * $quant % $r->result->count != 0) {
                print "{$i->count} * $quant / {$r->result->count} = " . $i->count * $quant / $r->result->count . "\n";    
            }
            
            $nextLevelNeeded[$i->element] += ($i->count * $quant / $r->result->count);    
        }

        // Now we know the total of each element is needed from the next (lower) level.
        // But elements can only be produced in whole number increments, so there may
        // be some waste.  And reactions can produce more than one result.

        // result = 2A
        // input = 9 ore
        // So we need A to be a multiple of 2.  Find that, then we need 9 * that of ore


        // Round up next level needed to multiple of reaction

        // Factor in reaction that produces more than one
        $r->result->count;
    }

    if (!empty($nextLevelNeeded)) {
        print_r($nextLevelNeeded);
        examineLevel2($nextLevelNeeded);
    }
}

examineLevel2(array('FUEL' => 1));

