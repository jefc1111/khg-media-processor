<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;

use GravityMedia\Ghostscript\Ghostscript;
use Symfony\Component\Process\Process;

class FilePrepper {
    public function prep() {
        $urns_tracked = [];

        foreach (Storage::allFiles('khg_media') as $file) {
            $file = new File($file);        

            if ($file->isWanted()) {
                // echo "_________________________".PHP_EOL;
                // echo $file->filename.PHP_EOL;
                // echo $file->hSize().PHP_EOL;

                
                
                
                if ($file->isPdf()) {
                    // Define input and output files
                    $inputFile = storage_path()."/app/".$file->fullPath;
                    $outputFile = "/tmp/khg-resize/".$file->filename;

                    // Create Ghostscript object
                    $ghostscript = new Ghostscript([
                        'quiet' => false
                    ]);

                    // Create and configure the device
                    $device = $ghostscript->createPdfDevice($outputFile);
                    $device->setCompatibilityLevel(1.4);
                    $device->setPdfSettings("screen");

                    // Create process
                    $process = $device->createProcess($inputFile);

                    // Print the command line
                    print '$ ' . $process->getCommandLine() . PHP_EOL;

                    // Run process
                    $process->run(function ($type, $buffer) {
                        if ($type === Process::ERR) {
                            throw new \RuntimeException($buffer);
                        }

                        print $buffer;
                    });
                }




                if (! array_key_exists($file->urn, $urns_tracked)) {
                    $urns_tracked[$file->urn] = 0;
                }

                $urns_tracked[$file->urn]++;
            }
        }

        foreach ($urns_tracked as $urn => $count) {
            if ($count > 2) {
                echo "OUTLIER URN".PHP_EOL;
                echo $urn." ".$count.PHP_EOL;
            }
        }
    }
}

class File {
    public $mimetype;
    public $size;
    public $pathComponents;
    public $filename;
    public $urn;

    function __construct(
        public $fullPath
    ) {
        $this->mimetype = Storage::mimeType($fullPath);

        $this->size = Storage::size($fullPath);

        $rubble = explode("/", $fullPath);

        $this->pathComponents = array_slice($rubble, 0, -1);

        $this->filename = end($rubble);

        // "000203-02 - the name of the thing" -> "000203" 
        $this->urn = substr($this->filename, 0, 6);
    }

    function __toString() {
        return $this->fullPath.PHP_EOL.$this->mimetype.PHP_EOL.$this->hSize();
    }

    function isPdf() {
        return $this->mimetype === "application/pdf";
    } 

    function isJpeg() {
        return $this->mimetype === "image/jpeg";
    }

    function hSize() {
        return $this->formatBytes($this->size);
    } 

    function formatBytes($size, $precision = 2) { 
        $base = log($size, 1024);
        $suffixes = array('', 'K', 'M', 'G', 'T');   
    
        return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
    } 
    
    function isInASet() {
        $charSeven = substr($this->filename, 6, 1);

        if (in_array($charSeven, ["-", "_"])) {
            return true;
        }

        if (str_contains($this->filename, ")") && str_contains($this->filename, "(")) {
            if (is_numeric($this->lastBracketsContents())) {
                return true;
            }                      
        } 

        return false;
    }

    // This specifically checks the last set of brackets
    function lastBracketsContents() {
        $openBracketPos = strrpos($this->filename, "(");
        $closedBracketPos = strrpos($this->filename, ")");

        return substr($this->filename, $openBracketPos + 1, $closedBracketPos - $openBracketPos - 1);
    }

    function isFirstOfSet() {
        // Looking for patterns like 005663-01 at the start of the filename
        // Also checking for (01) or similar at the end of the filename
        $charEightNine = substr($this->filename, 7, 2);

        if ($this->isInASet()) {
            return $charEightNine == "01" || $this->lastBracketsContents() == 1;
        }

        return false;
    }

    function isWanted() {
        if ($this->isPdf()) {
            return true;
        }

        if (
            $this->isJpeg()
            && ($this->isFirstOfSet() || ! $this->isInASet())
        ) {
            return true;
        }

        return false;
    }
}