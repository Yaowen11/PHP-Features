<?php

// 安装软件 sudo apt install wv

$filename = 'preRead.doc';

$output = str_replace('.doc', '.txt', $filename);

shell_exec('/usr/bin/wvText ' . $filename . ' ' . $output);

$text = file_get_contents($output);

if (!mb_detect_encoding($text, 'UTF-8', true)) {
    $text = utf8_encode($text);
}

unlink($output);