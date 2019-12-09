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

    public function __construct($memory, $inputArray = array(), $name = null)
    {
        $this->memory = array_pad($memory, self::MEMORY_SIZE, 0);
        $this->inputArray = $inputArray;
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

/*
$memory = explode(',', '109,1,204,-1,1001,100,1,100,1008,100,16,101,1006,101,0,99');
$memory = explode(',', '1102,34915192,34915192,7,4,7,99,0');
$memory = explode(',', '104,1125899906842624,99');
$m = new Machine($memory);
$ret = $m->run();
print "$ret\n";
exit;
*/

$memory = explode(',', trim(file_get_contents('input.txt')));

// Part One
print "Part One\n";
$m = new Machine($memory, array(1), "BOOST");
$ret = $m->run();
print "End: $ret\n";

print "Part Two\n";
$m = new Machine($memory, array(2), "Intcode");
$ret = $m->run();
print "End: $ret\n";

