<?php

// Copyright 2019 Max Sprauer

$opcodes = explode(",", file_get_contents('input.txt'));

function main($opcodes, $op1, $op2)
{
    $pos = 0;

    // Restore state to before the computer caught fire.
    $opcodes[1] = $op1;
    $opcodes[2] = $op2;

    do {
        switch ($opcodes[$pos]) {
            case 1:     // add addr1 addr2 store
                $opcodes[$opcodes[$pos + 3]] = $opcodes[$opcodes[$pos + 1]] + $opcodes[$opcodes[$pos + 2]];
                break;

            case 2:     // mult addr1 addr2 store
                $opcodes[$opcodes[$pos + 3]] = $opcodes[$opcodes[$pos + 1]] * $opcodes[$opcodes[$pos + 2]];
                break;

            case 99:    // halt and catch fire
                break 2;

            default:
                assert(false, "Illegal opcode {$opcodes[$pos]} at $pos");
                break;
        }

        $pos += 4;
    } while (true);

    return $opcodes[0];
}

// Part 1
$ret = main($opcodes, 12, 2);
print "$ret\n";

// Part 2
for ($noun = 0; $noun <= 99; $noun++) {
    for ($verb = 0; $verb <= 99; $verb++) {
        $ret = main($opcodes, $noun, $verb);
        if (19690720 == $ret) {
            print "Part Two: " . (100 * $noun + $verb) . "\n";
            exit;
        }
    }
}

print "Part Two: No answer found.\n";
