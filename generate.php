<?php

function randHex()
{
    $string = str_shuffle(str_repeat('0123456789abcdef', 4));
    return substr($string, 0, mt_rand(32, 64));
}

$filePath = __DIR__ . '/docker-compose.yml';


$fileString = file_get_contents($filePath);

$replace = [];
$replace[] = randHex();
$replace[] = randHex();

$outContent = str_replace(['{KEY}', '{SALT}'], $replace, $fileString);

file_put_contents($filePath, $outContent);

exit;