<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;

use GravityMedia\Ghostscript\Ghostscript;
use Symfony\Component\Process\Process;

class FilePrepper {
    public function prep($isDryRun = true) {
        $destinationDir = "out";//time();

        if (! $isDryRun) { 
            Storage::makeDirectory("khg_media_processed/$destinationDir");
        }
        
        $wanted_files_per_urn = [];

        echo 'Dry run: '.($isDryRun ? 'true' : 'false').PHP_EOL.PHP_EOL;
        
        $allFilePaths = Storage::allFiles('khg_media');

        $allFiles = collect([]);

        foreach ($allFilePaths as $file) {
            $file = new File($file);        

            if ($file->isWanted) {
                // echo "_________________________".PHP_EOL;
                // echo $file->filename.PHP_EOL;
                // echo $file->hSize().PHP_EOL;
                if (! $isDryRun) {                                        
                    $this->compressAndCopyFile($file, $destinationDir);                    
                }

                $wanted_files_per_urn = $this->trackQtyWantedFilesPerUrn($wanted_files_per_urn, $file);
            }

            $allFiles->push($file);
        }

        $wantedFiles = $allFiles->filter(fn(File $f) => $f->isWanted);
        $unwantedFiles = $allFiles->filter(fn(File $f) => ! $f->isWanted);
        
        $unrepresentedUrns = $unwantedFiles
        ->pluck('urn')->unique()
        ->diff($wantedFiles->pluck('urn')->unique())
        ->values();

        $counts = [
            'wanted' => $wantedFiles->count(),
            'unwanted' => $unwantedFiles->count(),
            'all_files' => count($allFilePaths),
            'unrepresented_urns' => 0
        ];

        if (! $isDryRun) { 
            $this->makeCsv($wantedFiles, "khg_media_processed/$destinationDir/files.csv");
        }

        $this->reportOutliers($wanted_files_per_urn);
        $this->reportCounts($counts);
        $this->reportUnrepresentedUrns($unrepresentedUrns->toArray());        
    }

    private function makeCsv($files, string $filePath) {
        $content = "URN,filename".PHP_EOL;

        foreach ($files as $file) {
            $content .= "$file->urn,{$file->outputFilename()}".PHP_EOL;
        }

        Storage::put($filePath, $content);
    }

    function trackQtyWantedFilesPerUrn(array $wanted_files_per_urn, File $file): array {
        if (! array_key_exists($file->urn, $wanted_files_per_urn)) {
            $wanted_files_per_urn[$file->urn] = 0;
        }

        $wanted_files_per_urn[$file->urn]++;

        return $wanted_files_per_urn;
    }

    // Shows any URNs that have more than 2 'wanted files'
    // At this point we want 1 jpeg, 1 pdf, or one of each
    function reportOutliers(array $wanted_files_per_urn): void {
        foreach ($wanted_files_per_urn as $urn => $count) {
            if ($count > 2) {
                echo "OUTLIER URN (has more than two files)".PHP_EOL;
                echo $urn." ".$count.PHP_EOL;
            }
        }
    }

    function reportCounts(array $counts): void {
        foreach ($counts as $k => $v) {
            echo $k.": ".$v.PHP_EOL;
        }
    }

    function reportUnrepresentedUrns(array $unrepresentedUrns): void {
        echo "Unrepresented URNs".PHP_EOL;
        echo print_r($unrepresentedUrns, true);
    }

    function compressAndCopyFile(File $file, string $destinationDir): void {
        $in = storage_path()."/app/".$file->fullPath;
        $out = storage_path()."/app/khg_media_processed/$destinationDir/".$file->outputFilename();

        if ($file->isPdf()) {
            $this->compressAndCopyPdf($in, $out);
        }

        if ($file->isJpeg()) {
            $this->compressAndCopyJpeg($in, $out);
        }
    }

    function compressAndCopyPdf(string $inputFile, string $outputFile): void {
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

        $process->setTimeout(300);

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

    function compressAndCopyJpeg(string $inputFile, string $outputFile): void {
        // https://image.intervention.io/v2/api/resize
        // resize the image so that the largest side fits within the limit; the smaller
        // side will be scaled to maintain the original aspect ratio
        // Also prevent possible upsizing
        $img = \Intervention\Image\Facades\Image::make($inputFile)->resize(1024, 1024, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $img->save($outputFile, 60);
    }
}

class File {
    public $mimetype;
    public $size;
    public $pathComponents;
    public $filename;
    public $urn;
    public $wanted = false;

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

        $this->isWanted = $this->isWanted();
    }

    function __toString() {
        return $this->fullPath.PHP_EOL.$this->mimetype.PHP_EOL.$this->hSize();
    }

    function isPdf(): bool {
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
    
    function isInASet(): bool {
        $charSeven = substr($this->filename, 6, 1);
        $charSix = substr($this->filename, 5, 1);

        // Checking $charSix in case of `00020 - etc`
        if (in_array($charSeven, ["-", "_"]) && $charSix !== " ") {
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
    function lastBracketsContents(): string {
        $openBracketPos = strrpos($this->filename, "(");
        $closedBracketPos = strrpos($this->filename, ")");

        return substr($this->filename, $openBracketPos + 1, $closedBracketPos - $openBracketPos - 1);
    }

    function isFirstOfSet(): bool {
        // Looking for patterns like 005663-01 at the start of the filename
        // Also checking for (01) or similar at the end of the filename
        $charEightNine = substr($this->filename, 7, 2);

        if ($this->isInASet()) {
            return $charEightNine == "01" || $this->lastBracketsContents() == 1;
        }

        return false;
    }

    private function isWanted(): bool {
        if (! $this->urnIsValid($this->urn)) {
            return false;
        }

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

    private function urnIsValid(string $urn, int $max_urn = 2071): bool {
        return preg_match('/^\d{6}$/', $urn) && ((int) $urn) < $max_urn;
    } 

    function outputFilename(): string {
        if ($this->isJpeg()) {
            $extension = 'jpg';
        } else if ($this->isPdf()) {
            $extension = 'pdf';
        } else {
            $extension = '???';
        }

        return "$this->urn.$extension";
    }
}