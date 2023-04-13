<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Support\FilenameCleaner;

class CleanFilenames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'khg:clean-filenames';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes and replaces various problematic characters.';

    /**
     * Execute the console command.
     */
    public function handle(FilenameCleaner $cleaner): void
    {
        echo $cleaner->cunt();
    }
}
