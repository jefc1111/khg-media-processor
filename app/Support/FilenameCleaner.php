<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class FilenameCleaner {
    public function cunt() {
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

        // Resize PDFs

        // Move and rename all files that are actually going to be wanted for upload into a single folder, 
        // with filename containing only the relevant id


        // foreach (Storage::allFiles('khg_media') as $file) {
        //     echo $file.PHP_EOL;
        // }
    }
}