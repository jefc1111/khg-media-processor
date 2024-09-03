<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Support\VisibilitySetter;

class SetVisibility extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'khg:set-visibility {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Preps a bunch of files for upload';

    /**
     * Execute the console command.
     */
    public function handle(VisibilitySetter $visibility_setter): void
    {
        $visibility_setter->set_visibility(!! $this->option('dry-run'));
    }
}
