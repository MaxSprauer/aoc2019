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
    private $ip = 0;        // Instruction pointer
    private $halt = false;
    const OPCODE_LENGTH = 2;

    public function __construct($memory, $inputArray)
    {
        $this->memory = $memory;
        $this->inputArray = $inputArray;
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
        return array_shift($this->inputArray);
    }

    function output($a)
    {
        print "[Output] $a\n";
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
        print "[Halt]\n";
        $this->halt = true;
    }

    function run()
    {
        while (!$this->halt) {
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
                $this->memory[$this->memory[$this->ip]] = $ret;
                $this->ip++;
            }
        }
    }
}

// Part One
$memory = explode(',', file_get_contents('input.txt'));
$machine = new Machine($memory, [1]);
$machine->run();

// Part Two
$machine = new Machine($memory, [5]);
$machine->run();
