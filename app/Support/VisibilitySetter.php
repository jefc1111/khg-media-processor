<?php

namespace App\Support;

class VisibilitySetter {
    private $urn_property_id = 10;
    //private $khg_visibility_class_property_id = ?;

    public function set_visibility($is_dry_run = true) 
    {
        $this->initial_summary();
        $this->current_resource_status();
    }

    private function current_resource_status(): void
    {
        $example_urns = [
            90 => 1, 
            91 => 2, 
            92 => 3
        ];

        foreach ($example_urns as $urn => $visibility_class) {
            echo "URN: $urn, Visibility Class: $visibility_class";
            
            $res = \DB::table("resource")
            ->join('value', 'resource_id', '=', 'resource.id')
            ->where('value.property_id', $this->urn_property_id)
            ->where('value.value', $urn)
            ->get();
            
            echo PHP_EOL;

            echo "Result count ". $res->count();

            echo PHP_EOL;
            
            if ($res->count() > 1) {
                echo "MORE THAN ONE RESULT FOUND! URN: $urn";

                echo PHP_EOL;

                // PROBABLY SHOULD BAIL
            }

            if ($res->count() === 0) {
                echo "NO RESULTS FOUND! URN: $urn";

                echo PHP_EOL;

                // PROBABLY SHOULD BAIL
            }

            $first_result = $res[0];
            
            echo "First result type: ".$first_result->resource_type;

            if ($first_result->resource_type === ITEM) {
                // Check visibility of item is correct for $visibility_class 
                // if not, set it as required and log it somewhere

                // Check visibility of any associated media is correct for $visibility_class 
                // if not, set it as required and log it somewhere
            }            

            echo PHP_EOL;
            echo PHP_EOL;

        }
    }

    private function initial_summary(): void
    {
        echo PHP_EOL;
        
        echo "Qty all resources (includes items, items sets and media): ".\DB::table("resource")
        ->count();

        echo PHP_EOL;

        echo "Qty public resources (includes items, items sets and media): ".\DB::table("resource")
        ->where('is_public', 1)
        ->count();        
        
        echo PHP_EOL;
        
        echo "Qty public items: ".\DB::table("resource")
        ->where([
            'is_public' => 1, 
            'resource_type' => 'Omeka\Entity\Item'
        ])
        ->count();
        
        echo PHP_EOL;

        echo "Qty public media: ".\DB::table("resource")
        ->where([
            'is_public' => 1, 
            'resource_type' => 'Omeka\Entity\Media'
        ])
        ->count();

        echo PHP_EOL;
        echo PHP_EOL;
    }
}