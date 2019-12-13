<?php

// Copyright 2019 Max Sprauer

class Machine
{
    const MEMORY_SIZE = 1000000;
    const MODE_POSITION = 0;
    const MODE_IMMEDIATE = 1;
    const MODE_RELATIVE = 2;
    const OPCODE_LENGTH = 2;

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
        '9' => [
            'function' => 'adjust_rb',
            'input_params' => 1,
            'output_params' => 0,
        ],
        '99' => [
            'function' => 'halt',
            'input_params' => 0,
            'output_params' => 0,
        ],
    ];

    private $memory;
    private $inputArray;
    private $ip = 0;        // Instruction pointer
    private $rb = 0;        // Relative base
    private $halt = false;
    private $sentOutput = false;
    private $lastOutput = null;
    private $outputFn;
    private $inputFn;

    public function __construct($memory, $inputArray = array(), $name = null, $outputFn = null, $inputFn = null)
    {
        $this->memory = array_pad($memory, self::MEMORY_SIZE, 0);
        $this->inputArray = $inputArray;
        $this->name = $name;
        $this->outputFn = $outputFn;
        $this->inputFn = $inputFn;
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
        if (isset($this->inputFn)) {
            $f = $this->inputFn;
            return $f();
        } else {
            assert(count($this->inputArray) > 0);
            return array_shift($this->inputArray);
        }
    }

    function addInput($n)
    {
        $this->inputArray[] = $n;
    }

    function output($a)
    {
        $this->lastOutput = $a;
        // print "[Output {$this->name}] $a\n";
        $this->sentOutput = true;

        if (isset($this->outputFn)) {
            $f = $this->outputFn;   // Can't call it directly
            $f($a);
        }
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

    function adjust_rb($n)
    {
        $this->rb += $n;
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

    function getMemoryAt($addr)
    {
        assert($addr >= 0 && $addr < count($this->memory), "Illegal address: $addr");
        return $this->memory[$addr];
    }

    function setMemoryAt($addr, $n)
    {
        assert($addr >= 0 && $addr < count($this->memory), "Illegal address: $addr");
        $this->memory[$addr] = $n;
    }

    function getPhysicalAddress(int $mode)
    {
        switch ($mode) {
            case self::MODE_POSITION:
                $addr = $this->getMemoryAt($this->ip);
                break;

            case self::MODE_IMMEDIATE:
                $addr = $this->ip;
                break;

            case self::MODE_RELATIVE:
                $addr = $this->getMemoryAt($this->ip) + $this->rb;
                break;

            default:
                assert(0, "Illegal mode: $mode");
                exit;
        }

        return $addr;
    }

    function run()
    {
        $this->sentOutput = false;

        while (!$this->halt) {
            $fullOpcode = str_pad($this->getMemoryAt($this->ip), self::OPCODE_LENGTH, '0', STR_PAD_LEFT);
            
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
            for ($i = 0; $i < $inputCount; $i++) {
                // $flags[$i] should be the mode for each parameter.  1 is immediate mode, 0 is position mode                
                $addr = $this->getPhysicalAddress((int) $flags[$i]);
                $params[$i] = $this->getMemoryAt($addr);                
                $this->ip++;
            }
        
            // Call function with parameters.  The ... turns an array into variables.
            $ret = $this->$function(...$params);             
            // print "$function(" . implode(', ', $params) . ") = $ret\n";            

            if ($outputCount == 1) {
                $addr = $this->getPhysicalAddress((int) $flags[$inputCount]);
                $this->setMemoryAt($addr, $ret);
                $this->ip++;
            }
        }

        return $this->lastOutput;
    }
}


$memory = explode(',', trim(file_get_contents('input.txt')));

// Part One
print "Part One\n";
$screen = array();
$currentTuple = array();
$m = new Machine($memory, array(), "breakout", function($x) use (&$screen, &$currentTuple) {
    switch (count($currentTuple)) {
        case 0:
        case 1:
            $currentTuple[] = $x;
        break;

        case 2:
            $screen[$currentTuple[0]][$currentTuple[1]] = $x;
            $currentTuple = array();
        break;

        default:
            assert(false, "Something is wrong.");
        break;
    } 
});
$m->run();

// print_r($screen);
$count = 0;
foreach ($screen as $x => $col) {
    foreach ($col as $y => $val) {
        if (2 == $val) {
            $count++;
        }
    }
}
print "Total blocks: $count\n";


// Part Two
// Memory address 0 represents the number of quarters that have been inserted; set it to 2 to play for free.
$memory[0] = 2;

const JOYSTICK_LEFT = -1;
const JOYSTICK_MIDDLE = 0;
const JOYSTICK_RIGHT = 1;
const OBJ_BALL = 4;
const OBJ_PADDLE = 3;

function findObject(&$screen, $obj) {
    foreach ($screen as $x => $col) {
        foreach ($col as $y => $val) {
            if ($val == $obj) {
                return [$x, $y];
            }
        }
    }
    assert(false, "Object $obj not found");
}

$score = 0;

$m = new Machine($memory, array(), "breakout", function($n) use (&$screen, &$currentTuple, &$score) {
    // Output function callback
    switch (count($currentTuple)) {
        case 0:
        case 1:
            $currentTuple[] = $n;
        break;

        case 2:
            list($x, $y) = $currentTuple;

            if (-1 == $x && 0 == $y) {
                $score = $n;
            } else {
                $screen[$x][$y] = $n;
            }
            $currentTuple = array();
        break;

        default:
            assert(false, "Something is wrong.");
        break;
    } 
}, function() use (&$screen) {
    // Input function callback
    list($ballX, ) = findObject($screen, OBJ_BALL);
    list($paddleX, ) = findObject($screen, OBJ_PADDLE);

    if ($ballX < $paddleX) {
        return JOYSTICK_LEFT;
    } else if ($ballX > $paddleX) {
        return JOYSTICK_RIGHT;
    } else {
        return JOYSTICK_MIDDLE;
    }
});
$m->run();

print "Part Two score: $score\n";
