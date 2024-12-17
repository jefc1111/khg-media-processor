<?php

namespace App\Support;

use Illuminate\Console\Concerns\InteractsWithIO;
use Symfony\Component\Console\Output\ConsoleOutput;

class VisibilitySetter {
    use InteractsWithIO;

    private int $urn_property_id = 10;
    // private int $khg_visibility_class_property_id = ?;
    private bool $dry_run = true;
    private array $is_public_class_to_setting_mapping = [
        1 => 0,
        2 => 1,
        3 => 1
    ];

    public function __construct()
    {
        $this->output = new ConsoleOutput();
    }

    public function set_visibility($is_dry_run = true) 
    {
        if ($this->dry_run) {
            $this->warn("DRY RUN");
        }

        $this->initial_summary();

        $this->current_resource_status();
    }

    private function set_is_public_for_item(\stdClass $item, int $visibility_class, $urn): void 
    {
        if (! in_array($visibility_class, array_keys($this->is_public_class_to_setting_mapping))) {            
            throw new \Exception("Received invalid visibility class ($visibility_class).");
        }

        $this->info("Handling `is_public` for item with URN $urn.");

        $this->set_is_public_for_resource($item, $visibility_class);

        // Now find associated Media
    }

    private function set_is_public_for_resource(\stdClass $resource, int $visibility_class): void 
    {
        $current = $resource->is_public;        

        $required = $this->is_public_class_to_setting_mapping[$visibility_class];

        $this->info("Current: $current");
        $this->info("Required: $required");

        if ($current !== $required) {
            $this->warn("DB update: Change is_public to $required.");

            if (! $this->dry_run) {
                // set is_public to $item_required for $item
            }
        }
    }

    private function current_resource_status(): void
    {
        $example_urns = [
            90 => 1, 
            91 => 2, 
            92 => 3,
            421 => 1,
            422 => 2
        ];

        foreach ($example_urns as $urn => $visibility_class) {
            $this->info("URN: $urn, Visibility Class: $visibility_class");
            
            $res = \DB::table("resource")
            ->select("resource.*")
            ->join('value', 'resource_id', '=', 'resource.id')
            ->where('value.property_id', $this->urn_property_id)
            ->where('value.value', $urn)
            ->get();
            
            $this->info("Result count ". $res->count());

            if ($res->count() > 1) {
                $this->warn("More than one result found for URN $urn.");
            }

            if ($res->count() === 0) {
                $this->warn("No results found for URN $urn.");
            }

            if ($res->count() === 1) {
                $first_result = $res[0];
            
                $this->info("First result type: ".$first_result->resource_type);

                if ($first_result->resource_type === "Omeka\Entity\Item") {
                    echo(print_r($first_result, true));

                    $this->set_is_public_for_item($first_result, $visibility_class, $urn);
                    // Check visibility of item is correct for $visibility_class 
                    // if not, set it as required and log it somewhere
    
                    // Check visibility of any associated media is correct for $visibility_class 
                    // if not, set it as required and log it somewhere
                } else if ($first_result->resource_type === "Omeka\Entity\ItemSet") {
                    // Should never get here 
                    $this->warn("Why do we have a URN ($urn) that refers directly to an Item Set resource?");
                } else if ($first_result->resource_type === "Omeka\Entity\Media") {
                    // Should never get here
                    $this->warn("Why do we have a URN ($urn) that refers directly to a Media resource?");
                } else {
                    // Should never get here
                    $this->warn("Unrecognised resource_type for URN $urn.");
                }
            }

            $this->comment("---");
        }
    }

    private function initial_summary(): void
    {
        $this->info("Qty all resources (includes items, items sets and media): ".\DB::table("resource")
        ->count());

        $this->info("Qty public resources (includes items, items sets and media): ".\DB::table("resource")
        ->where('is_public', 1)
        ->count());
        
        $this->info("Qty public items: ".\DB::table("resource")
        ->where([
            'is_public' => 1, 
            'resource_type' => 'Omeka\Entity\Item'
        ])
        ->count());

        $this->info("Qty public media: ".\DB::table("resource")
        ->where([
            'is_public' => 1, 
            'resource_type' => 'Omeka\Entity\Media'
        ])
        ->count());

        $this->comment("---");
    }
}