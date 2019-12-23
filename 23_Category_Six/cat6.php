<?php

// Copyright 2019 Max Sprauer

class Machine
{
    const MEMORY_SIZE = 10000;
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
    private $lastOutput = null;
    private $outputFn;
    private $inputFn;
    public $outputBuf = array();
    private $lastNetwork = '';

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
        $this->lastNetwork = 'input';

        if (count($this->inputArray) > 0) {
            return array_shift($this->inputArray);
        }

        $f = $this->inputFn;
        $val = $f($this->name);

        if ($val == -1) {
           $this->lastNetwork = 'input failed'; 
        }

        return $val;
    }

    function addInput($n)
    {
        $this->inputArray[] = $n;
    }

    function output($a)
    {
        $this->lastNetwork = 'output';
        $this->lastOutput = $a;
        // print "[Output {$this->name}] $a\n";

        if (isset($this->outputFn)) {
            $f = $this->outputFn;   // Can't call it directly
            $f($this, $a);
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
        // while (!$this->halt) {
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
        // }

        return $this->lastOutput;
    }

    function isNetworkIdle()
    {
        return ($this->lastNetwork == 'input failed');
    }
}

$memory = explode(',', trim(file_get_contents('input.txt')));

// Part One
print "Part One\n";

$machines = array();
$networkBuf = array();    // [0..49] => buffer array
const MACHINE_COUNT = 50;

for ($i = 0; $i < MACHINE_COUNT; $i++) {
    $machines[$i] = new Machine($memory, array($i), $i, 'output', 'input');
}

/* This will exit, so commented out for part 2
while (1) {
    for ($i = 0; $i < MACHINE_COUNT; $i++) {
        if (!$machines[$i]->isHalted()) {
            $machines[$i]->run();   // Run one instruction
        }
    }
}
*/

function input($id) {
    global $networkBuf;
    $val = -1;

    if (is_array($networkBuf[$id]) && count($networkBuf[$id])) {
        $val = array_shift($networkBuf[$id]);
    }
    
    if ($val != -1) {
        print "[$id] Input: $val\n";
    }

    return $val;
}

function output($m, $val) {
    global $networkBuf;
 
    if (count($m->outputBuf) == 2) {
        $id = array_shift($m->outputBuf);
        $x = array_shift($m->outputBuf);
        $y = $val;

        if ($id == 255) {
            print "Y Value: $y\n";
            exit;
        }

        $m->outputBuf = array();

        $networkBuf[$id][] = $x;
        $networkBuf[$id][] = $y;
    } else {
        $m->outputBuf[] = $val;
    }

    print "[{$m->name}] Outputs: $val\n";
}


// Part Two
print "Part Two\n";

$machines = array();
$networkBuf = array();    // [0..49] => buffer array
$nat = new NAT();

for ($i = 0; $i < MACHINE_COUNT; $i++) {
    $machines[$i] = new Machine($memory, array($i), $i, 'output2', 'input');
}

while (1) {
    for ($i = 0; $i < MACHINE_COUNT; $i++) {
        if (!$machines[$i]->isHalted()) {
            $machines[$i]->run();   // Run one instruction

        }
    }

    if ($nat->isNetworkIdle($machines, $networkBuf)) {
        $nat->resumeNetwork($networkBuf);
    }
}

class NAT
{
    private $lastY = -1;
    private $curX = -1;
    private $curY = -1;

    function isNetworkIdle(&$machines, &$networkBuf) 
    {
        for ($i = 0; $i < MACHINE_COUNT; $i++) {
            if (!$machines[$i]->isNetworkIdle()) {
                return false;
            }
     
            if (count($networkBuf[$i]) > 0) {
                return false;
            }
        }

        return true;
    }

    function queuePacket($x, $y) 
    {
        print "[nat] Queuing $x, $y\n";
        $this->curX = $x;
        $this->curY = $y;
    }

    function resumeNetwork(&$networkBuf) 
    {
        if ($this->curX == -1 && $this->curY == -1) {
            return;
        }

        print "[nat] Resuming network\n";

        if ($this->curY == $this->lastY) {
            print "First Y delivered twice in a row: {$this->curY}\n";
            exit;
        }

        $networkBuf[0][] = $this->curX;
        $networkBuf[0][] = $this->curY;
        $this->lastY = $this->curY;
    }
}

function output2($m, $val) {
    global $networkBuf, $nat;
 
    if (count($m->outputBuf) == 2) {
        $id = array_shift($m->outputBuf);
        $x = array_shift($m->outputBuf);
        $y = $val;
        $m->outputBuf = array();

        if ($id == 255) {
            $nat->queuePacket($x, $y);
        } else {
            $networkBuf[$id][] = $x;
            $networkBuf[$id][] = $y;
        }
    } else {
        $m->outputBuf[] = $val;
    }

    print "[{$m->name}] Outputs: $val\n";
}