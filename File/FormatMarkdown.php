<?php

class FormatMarkdown
{
    private static $BASE_DIR = '/Users/zhangyaowen/notes/Languages/Java/Language/API/';
    private static $FAIL_LOG = 'fail.log';
    private static $NEW_FILE_SUFFIX = '.md';

    private string $originMarkdownFile;

    private string $newMdFile;

    private string $className;

    private array $iterator;

    private $newMarkdownFileHandler;

    public function __construct(string $originMarkdownFile)
    {
        $this->originMarkdownFile = $originMarkdownFile;
        $this->iterator = $this->readMd2Array();
        $this->className = $this->getClassName();
        $this->newMdFile = pathinfo($this->originMarkdownFile, PATHINFO_DIRNAME) .
            '/' . $this->className . self::$NEW_FILE_SUFFIX;
        $this->newMarkdownFileHandler = fopen($this->newMdFile, 'w+');
    }

    private function readMd2Array(): array
    {
        $md2Array = array_filter(file($this->originMarkdownFile));
        $result = [];
        foreach ($md2Array as $item) {
            if (strlen(trim($item)) <= 1) {
                continue;
            }
            $result[] = trim($item);
        }
        return $result;
    }

    private function recordLog(string $log)
    {
        file_put_contents(
            self::$BASE_DIR . self::$FAIL_LOG,
            json_encode(['file' => $this->originMarkdownFile, $log => $log])  . PHP_EOL,
            FILE_APPEND
        );
    }

    private function failClear(): void
    {
        if (is_resource($this->newMarkdownFileHandler)) {
            fclose($this->newMarkdownFileHandler);
        }
        if (file_exists($this->newMdFile)) {
            unlink($this->newMdFile);
        }
    }

    private function getClassName(): string
    {
        $className = array_shift($this->iterator);
        if (preg_match('/^#+/', $className, $matches)) {
            $className = trim(str_replace($matches, '', $className));
            if (preg_match('/`+/', $className)) {
                $className = trim($className, '`');
            }
        } else {
            $className = basename($this->originMarkdownFile);
        }
        return $className;
    }

    private function writeTitle()
    {
        fwrite($this->newMarkdownFileHandler, '*' . $this->className . '*' . PHP_EOL);
        fwrite($this->newMarkdownFileHandler, '```java' . PHP_EOL);
    }

    private function trimLine(): void
    {
        $clearLines = [];
        foreach ($this->iterator as $line) {
            if ($this->isMethod($line)) {
                $clearLines[] = $this->getLineMethodSign($line);
            } else {
                $clearLines[] = $this->getLineMethodComment($line);
            }
        }
        $this->iterator = $clearLines;
    }

    private function getLineMethodSign(string $line) : string
    {
        if (preg_match('/[a-zA-Z0-9].+\)/', $line, $matches)) {
            return $matches[0] . ';' . PHP_EOL;
        }
        return $line;
    }

    private function getLineMethodComment(string $line) : string
    {
        return '// ' . $line . PHP_EOL;
    }

    private function isMethod(string $line) : bool
    {
        if (preg_match("/[\x{4e00}-\x{9fa5}]/u", $line, $matches)) {
            return false;
        }
        return true;
    }

    public function parse(): void
    {
        $this->writeTitle();
        $this->trimLine();
        foreach ($this->iterator as $key => $line) {
            fwrite($this->newMarkdownFileHandler, $line . PHP_EOL);
        }
        fwrite($this->newMarkdownFileHandler, '```');
        fclose($this->newMarkdownFileHandler);
        unlink($this->originMarkdownFile);
    }

    public static function format()
    {
        $formatDir = dir(self::$BASE_DIR);
        while (($subDir = $formatDir->read()) != false) {
            if ($subDir == '.' || $subDir == '..') {
                continue;
            }
            if (is_dir($subDir)) {
                $subDirO = dir($subDir);
                while (($signFile = $subDirO->read()) != false) {
                    if ($signFile == '.' || $signFile == '..') {
                        continue;
                    }
                    if (count(explode('.', $signFile)) < 4) {
                        (new FormatMarkdown(self::$BASE_DIR . $subDir . DIRECTORY_SEPARATOR . $signFile))->parse();
                    }
                }
            }
        }
    }

}

FormatMarkdown::format();