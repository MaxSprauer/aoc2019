<?php

// Copyright 2020 Max Sprauer

ini_set('xdebug.max_nesting_level', 2000);

class TreeNode
{
    public $children = array();
    public $directCount = 0;
    public $indirectCount = 0;
    public $orbitDepth = 0;
}


$lines = explode("\n", file_get_contents('input.txt'));
$map = array();
foreach ($lines as $line) {
    list($parent, $child) = explode(')', trim($line));
    if (!isset($map[$parent])) {
        $map[$parent] = new TreeNode();
    }
    if (!isset($map[$child])) {
        $map[$child] = new TreeNode();
    }

    $map[$parent]->children[] = $child;
}

// print_r($map);



function dfs(string $nodeName, int $depth = 0)
{
    global $map, $total;

    $node = &$map[$nodeName];
    if ($depth > 0) {
        $node->directCount++;
        $node->indirectCount = ($depth - 1);
        $node->orbitDepth = $depth;
        $total += $depth;
    }

    if (empty($node->children)) {
        // Leaf node
        return 0;
    } else {
        foreach ($node->children as $child) {
            dfs($child, $depth + 1);
        }
    }
}

$total = 0;
dfs('COM');
print "Part One: $total\n";


// Part Two

function findParent(string $child)
{
    global $map;

    foreach ($map as $name => $node) {
        if (in_array($child, $node->children)) {
            return $name;
        }
    }

    assert(false, "No parent found: $child");
}

function hasDescendant(string $parent, string $target)
{
    global $map;
    $hasChild = false;

    if ($parent == $target) {
        return true;
    }

    $parentNode = $map[$parent];

    if (empty($parentNode->children)) {
        // Leaf node
        return false;
    } else {
        foreach ($parentNode->children as $child) {
            $hasChild = $hasChild || hasDescendant($child, $target);
        }
    }

    return $hasChild;
}

$youOrbitDepth = $map['YOU']->orbitDepth;
$sanOrbitDepth = $map['SAN']->orbitDepth;

// Not using full Dijkstra, because we don't need the path, only the distance.
// Move "up" the tree (to lower orbit depths) until we find a node that 
// has SAN as a descendant.  The number of transfers is then the sum of the differences in orbit 
// depth minus two.  (280 + 284 - 2 = 562)  Not caring about the actual path makes it easier.

$transfers = 0;
$curNode = 'YOU';
do {
    if (hasDescendant($curNode, 'SAN')) {
        $treeNode = $map[$curNode];
        print "Up: " . ($youOrbitDepth - $treeNode->orbitDepth) . "\n";
        print "Down: " . ($sanOrbitDepth - $treeNode->orbitDepth) . "\n";

        exit;
    }

    $curNode = findParent($curNode);
} while(1);
