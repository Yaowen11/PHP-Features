<?php

class ReadFile
{
    public function csv2Array(string $file)
    {
        $csvArray = [];
        if (file_exists($file)) {
            $readCSVFile = function ($fileHandle) {
                if ($fileHandle !== false) {
                    while ($csvLine = fgetcsv($fileHandle, 1024, ',')) {
                        yield $csvLine;
                    }
                }
            };
            $tempFileHandle = fopen($file, 'r');
            foreach ($readCSVFile($tempFileHandle) as $csvLine) {
                $tempLineArray = [];
                foreach ($csvLine as $item) {
                    $tempLineArray[] = trim(iconv('gb2312', 'utf-8', $item));
                }
                $csvArray[] = $tempLineArray;
            }
            fclose($tempFileHandle);
        }
        return $csvArray;
    }

    public function readFileOnePage(string $file, int $page = 1, int $perPage = 0)
    {
        $fileLineValueArray = [];
        if (file_exists($file)) {
            $fileHandler = fopen($file, 'r');
            $readOnePage = function ($file) {
                while (($buffer = fgets($file)) !== false) {
                    yield $buffer;
                }
            };
            $start = ($page -1) * $perPage;
            $end = $start + $perPage;
            foreach ($readOnePage($fileHandler) as $key => $item) {
                if ($key >= $start && $key < $end) {
                    $fileLineValueArray[] = json_decode($item, true);
                }
            }
        }
        return $fileLineValueArray;
    }

    public function readIniFile($file)
    {
        return parse_ini_file($file);
    }
}