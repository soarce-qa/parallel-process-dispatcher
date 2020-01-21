<?php

$iterations = $argv[1] ?? 10;

for ($i = 0; $i < $iterations; $i++) {
    echo "this is ";
    usleep(mt_rand(10000, 140000));
    echo "line $i\n";
    if (mt_rand(0, 2) === 0) {
        usleep(mt_rand(100000, 1400000));
    }
}
#echo "\n";