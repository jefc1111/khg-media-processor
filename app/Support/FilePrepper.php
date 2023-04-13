<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;

class FilePrepper {
    public function prep() {
        $urns_tracked = [];

        foreach (Storage::allFiles('khg_media') as $file) {
            $file = new File($file);        

            if ($file->isWanted()) {
                // echo $file->urn.PHP_EOL;

                if (! array_key_exists($file->urn, $urns_tracked)) {
                    $urns_tracked[$file->urn] = 0;
                }

                $urns_tracked[$file->urn]++;
            }
        }

        foreach ($urns_tracked as $urn => $count) {
            if ($count > 2) {
                echo $urn." ".$count.PHP_EOL;
            }
        }
        /*
        $replacement_patterns = [
            [[" - ", "___", "__"], "_"],
            [[" ", "-", "&"], "_"],
            [["(", ")", "'", "'"], ""]
        ];

        $all = array_merge(Storage::allDirectories('khg_media'), Storage::allFiles('khg_media'));

        foreach ($all as $file) {
            $file2 = str_replace($replacement_patterns[0][0], $replacement_patterns[0][1], $file);

            Storage::move($file, $file2);

            $file3 = str_replace($replacement_patterns[1][0], $replacement_patterns[1][1], $file2);

            Storage::move($file2, $file3);

            $file4 = str_replace($replacement_patterns[2][0], $replacement_patterns[2][1], $file3);

            Storage::move($file3, $file4);
        }
        */
        // Resize PDFs

        // Move and rename all files that are actually going to be wanted for upload into a single folder, 
        // with filename containing only the relevant id


        // foreach (Storage::allFiles('khg_media') as $file) {
        //     echo $file.PHP_EOL;
        // }
    }
}

class File {
    public $mimetype;
    public $size;
    public $path_components;
    public $filename;
    public $urn;

    function __construct(
        public $full_path
    ) {
        $this->mimetype = Storage::mimeType($full_path);

        $this->size = Storage::size($full_path);

        $rubble = explode("/", $full_path);

        $this->path_components = array_slice($rubble, 0, -1);

        $this->filename = end($rubble);

        // "000203-02 - the name of the thing" -> "000203" 
        $this->urn = substr($this->filename, 0, 6);
    }

    function __toString() {
        return $this->full_path.PHP_EOL.$this->mimetype.PHP_EOL.$this->hSize();
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

    // function isNotInASet() {
    //     $charSeven = substr($this->filename, 6, 1);

    //     return ! in_array($charSeven, ["-", "_"]);
    // }

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