<?php

// Copyright 2019 Max Sprauer

class Machine
{
    static $OPCODES = [
        '1' => [
            'function' => 'add',
            'input_params' => 2,
            'output_params' => 1,
        ],
        '2' => [
            'function' => 'multiply',
            'input_params' => 2,
            'output_params' => 1,
        ],
        '3' => [
            'function' => 'input',
            'input_params' => 0,
            'output_params' => 1,
        ],
        '4' => [
            'function' => 'output',
            'input_params' => 1,
            'output_params' => 0,
        ],
        '5' => [
            'function' => 'jump_if_true',
            'input_params' => 2,
            'output_params' => 0,
        ],
        '6' => [
            'function' => 'jump_if_false',
            'input_params' => 2,
            'output_params' => 0,
        ],
        '7' => [
            'function' => 'less_than',
            'input_params' => 2,
            'output_params' => 1,
        ],
        '8' => [
            'function' => 'equals',
            'input_params' => 2,
            'output_params' => 1,
        ],
        '99' => [
            'function' => 'halt',
            'input_params' => 0,
            'output_params' => 0,
        ],
    ];

    private $memory;
    private $inputArray;
    private $outputArray;
    private $ip = 0;        // Instruction pointer
    private $halt = false;
    private $sentOutput = false;
    private $lastOutput = null;
    const OPCODE_LENGTH = 2;

    public function __construct($memory, $inputArray = array(), $name = null)
    {
        $this->memory = $memory;
        $this->inputArray = $inputArray;
        $this->outputArray = array();
        $this->name = $name;
    }

    function add($a, $b)
    {
        return $a + $b;
    }

    function multiply($a, $b)
    {
        return $a * $b;
    }

    function input()
    {
        assert(count($this->inputArray) > 0);
        return array_shift($this->inputArray);
    }

    function addInput($n)
    {
        $this->inputArray[] = $n;
    }

    function output($a)
    {
        $this->lastOutput = $a;
        print "[Output {$this->name}] $a\n";
        $this->sentOutput = true;
    }

    function jump_if_true($a, $b)
    {
        if ($a != 0) {
            $this->ip = $b;
        }
    }

    function jump_if_false($a, $b)
    {
        if ($a == 0) {
            $this->ip = $b;
        }
    }

    function less_than($a, $b)
    {
        return ($a < $b) ? 1 : 0;
    }

    function equals($a, $b)
    {
        return ($a == $b) ? 1 : 0;
    }

    function halt()
    {
        print "[Halt {$this->name}]\n";
        $this->halt = true;
    }

    function isHalted()
    {
        return $this->halt;
    }

    function run()
    {
        $this->sentOutput = false;

        while (!$this->halt && !$this->sentOutput) {
            $fullOpcode = str_pad($this->memory[$this->ip], self::OPCODE_LENGTH, '0', STR_PAD_LEFT);
            
            // Grab last two chars to get opcode.
            // Zero-pad to remaining characters to length of parameters, and reverse to get position/immediate mode flags
            $opcode = (int) substr($fullOpcode, strlen($fullOpcode) - self::OPCODE_LENGTH, self::OPCODE_LENGTH);
            $this->ip++;

            assert(isset(self::$OPCODES[$opcode]), "Illegal instruction: $fullOpcode\n");
            $function = self::$OPCODES[$opcode]['function'];
            $inputCount = self::$OPCODES[$opcode]['input_params'];
            $outputCount = self::$OPCODES[$opcode]['output_params'];
            
            $flags = str_pad(strrev(substr($fullOpcode, 0, strlen($fullOpcode) - self::OPCODE_LENGTH)), $inputCount + $outputCount, '0');

            $params = array();
            // We ignore the mode flag for the output parameter since it must always be in position mode 
            for ($i = 0; $i < $inputCount; $i++) {
                // $flags[$i] should be the mode for each parameter.  1 is immediate mode, 0 is position mode
                $params[$i] = ((int) $flags[$i] == 1) ? $this->memory[$this->ip] : $this->memory[$this->memory[$this->ip]];
                $this->ip++;
            }
        
            // Call function with parameters.  The ... turns an array into variables.
            $ret = $this->$function(...$params);            
            if ($outputCount == 1) {    // jump functions do not have a return value, so the instruction pointer is OK.
                // print "$opcode {$params[0]} {$params[1]} => $ret\n";
                
                $this->memory[$this->memory[$this->ip]] = $ret;
                $this->ip++;
            }
        }

        return $this->lastOutput;
    }
}

function generatePhases($min, $max)
{
    $phases = array();

    for ($i = $min; $i < $max; $i++) {
        for ($j = $min; $j < $max; $j++) {
            if ($j == $i) {
                continue;
            }
            for ($k = $min; $k < $max; $k++) {
                if (($k == $j) || ($k == $i)) {
                    continue;
                }
                for ($l = $min; $l < $max; $l++) {
                    if (($l == $k) || ($l == $j) || ($l == $i)) {
                        continue;
                    }
                    for ($m = $min; $m < $max; $m++) {
                        if (($m == $l) || ($m == $k) || ($m == $j) || ($m == $i)) {
                            continue;
                        }
    
                        $phases[] = array($i, $j, $k, $l, $m);
                    }
                }
            }
        }
    }    

    return $phases;
}

function run($memory, $phases)
{
    $ret = 0;
    for ($i = 0; $i < 5; $i++) {
        $machine = new Machine($memory, array($phases[$i], $ret));
        $ret = $machine->run();
    }
    return $ret;
}

/*
$memory = explode(',', '3,15,3,16,1002,16,10,16,1,16,15,15,4,15,99,0,0');
run($memory, [4,3,2,1,0]);
*/

// Part One
// Phase Setting, Input Signal
$memory = explode(',', trim(file_get_contents('input.txt')));
$winner = 0;
$winningPhases = null;
$phases = generatePhases(0, 5);

foreach ($phases as $p) {
    $ret = run($memory, $p);   

    if ($ret > $winner) {
        $winner = $ret;
        $winningPhases = $p;
    }
}

print "Winner: $winner\n";
print_r($p);





// Part Two

function run2($memory, $phases)
{
    $winner = 0;
    $winningPhases = array();

    foreach ($phases as $p) {  
        $machines = array();
        $curr = 0;
        $ret = 0;

        print "** Start of phase {$p[0]}{$p[1]}{$p[2]}{$p[3]}{$p[4]}\n";
        do {
            if (!isset($machines[$curr])) {
                $machines[$curr] = new Machine($memory, array($p[$curr]), "Machine $curr");
            }

            $machines[$curr]->addInput($ret);
            $ret = $machines[$curr]->run();
            $curr = ($curr + 1) % 5;
        } while (!(count($machines) == 5 && $machines[4]->isHalted()));

        if ($ret !== null && $ret > $winner) {
            $winner = $ret;
            $winningPhases = $p;
        }    
    }

    return array($winner, $winningPhases);
}

/*
$memory = explode(',', "3,26,1001,26,-4,26,3,27,1002,27,2,27,1,27,26,27,4,27,1001,28,-1,28,1005,28,6,99,0,0,5");
$phases = generatePhases(5, 10);
list($winner, $winningPhases) = run2($memory, $phases);
print "Winner: $winner\n";
print_r($winningPhases);

$memory = explode(',', "3,52,1001,52,-5,52,3,53,1,52,56,54,1007,54,5,55,1005,55,26,1001,54,-5,54,1105,1,12,1,53,54,53,1008,54,0,55,1001,55,1,55,2,53,55,53,4,53,1001,56,-1,56,1005,56,6,99,0,0,0,0,10");
list($winner, $winningPhases) = run2($memory, $phases);
print "Winner: $winner\n";
print_r($winningPhases);
exit;
*/

print "\nPart Two\n";
$winner = 0;
$phases = generatePhases(5, 10);
list($winner, $winningPhases) = run2($memory, $phases);

print "Winner: $winner\n";
print_r($winningPhases);
