<?php

// Copyright 2020 Max Sprauer

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
    private $robot = null;

    public function __construct($memory, $inputArray = array(), $name = null, $robot = null)
    {
        $this->memory = array_pad($memory, self::MEMORY_SIZE, 0);
        $this->inputArray = $inputArray;
        $this->name = $name;
        $this->robot = $robot;
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
        if ($this->robot) {
            $this->addInput($this->robot->cameraInput());
        }

        assert(count($this->inputArray) > 0);
        return array_shift($this->inputArray);
    }

    function addInput($n)
    {
        $this->inputArray[] = $n;
    }

    function output($a)
    {
        if ($this->robot) {
            $this->robot->robotOutput($a);
        }

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

class Robot
{
    private $x = 0, $y = 0;
    private $whiteMap = array();    // ["y,x"] = 1
    private $painted = array();     // ["y,x"] = 1
    private $dir = 'up';
    private $paint = true;

    function __construct($partTwo = false)
    {
        if ($partTwo) {
            $this->whiteMap["0,0"] = 1;
        }
    }

    // provide 0 if the robot is over a black panel or 1 if the robot is over a white panel
    function cameraInput()
    {
        return isset($this->whiteMap["{$this->y},{$this->x}"]) ? 1 : 0;
    }

    /*
    */
    function robotOutput($x)
    {
        if ($this->paint) {
            // First, it will output a value indicating the color to paint the panel the robot is over: 
            // 0 means to paint the panel black, and 1 means to paint the panel white.
            if ($x == 0) {
                unset($this->whiteMap["{$this->y},{$this->x}"]); 
            } else {
                $this->whiteMap["{$this->y},{$this->x}"] = 1;
            }

            $this->painted["{$this->y},{$this->x}"] = 1;
        } else {
            // Second, it will output a value indicating the direction the robot should turn: 
            // 0 means it should turn left 90 degrees, and 1 means it should turn right 90 degrees.
            $turnLeft = array(0 => 'up', 1 => 'left', 2 => 'down', 3 => 'right');
            $turnRight = array(0 => 'up', 1 => 'right', 2 => 'down', 3 => 'left');  
            
            if ($x == 0) {
                $newDirKey = array_search($this->dir, $turnLeft);
                assert($newDirKey !== false);
                $this->dir = $turnLeft[($newDirKey + 1) % 4];
            } else {
                $newDirKey = array_search($this->dir, $turnRight);
                assert($newDirKey !== false);
                $this->dir = $turnRight[($newDirKey + 1) % 4];
            }

            // After the robot turns, it should always move forward exactly one panel. The robot starts facing up.
            switch ($this->dir) 
            {
                case 'up':
                    $this->y--;
                break;

                case 'down':
                    $this->y++;
                break;

                case 'left':
                    $this->x--;
                break;

                case 'right':
                    $this->x++;
                break;

                default:
                    assert(false, "Direction is {$this->dir}");
                break;
            }
        }

        $this->paint = !$this->paint;
    }

    function done()
    {
        print "Robot: Printed " . count($this->painted) . "\n";
    }

    function printMap()
    {
        define('WIDTH', 400);
        define('HEIGHT', 400);
        define('X_OFFSET', 200);
        define('Y_OFFSET', 200);

        $im = imagecreate(WIDTH, HEIGHT)
            or die("Cannot Initialize new GD image stream");
        
        $background_color = imagecolorallocate($im, 0, 0, 0);
        $text_color = imagecolorallocate($im, 233, 14, 91);

        foreach (array_keys($this->whiteMap) as $k) {
            list($y, $x) = explode(',', $k);
            imagesetpixel($im, $x + X_OFFSET, $y + Y_OFFSET, $text_color);
        }

        // Save the image
        imagebmp($im, 'space_force.bmp');

        // Free up memory
        imagedestroy($im);
    }
}

$memory = explode(',', trim(file_get_contents('input.txt')));

// Part One
print "Part One\n";
$robot = new Robot();
$m = new Machine($memory, array(), "Part One", $robot);
$ret = $m->run();
$robot->done();
print "End: $ret\n";

// Part Two
print "Part Two\n";
$robot = new Robot(true);
$m = new Machine($memory, array(), "Part Two", $robot);
$ret = $m->run();
$robot->printMap();
print "End: $ret\n";

