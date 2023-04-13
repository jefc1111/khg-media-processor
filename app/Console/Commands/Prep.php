<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Support\FilePrepper;

class Prep extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'khg:prep';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Preps a bunch of files for upload';

    /**
     * Execute the console command.
     */
    public function handle(FilePrepper $prepper): void
    {
        echo $prepper->prep();
    }
}
