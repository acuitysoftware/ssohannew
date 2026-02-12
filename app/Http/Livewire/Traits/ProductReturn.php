<?php
namespace App\Http\Livewire\Traits;

trait WithSorting
{
    public $sortBy = 'id';
    public $sortDirection = 'desc';

    public function returnProduct($product_id, $quantity)
    {
        dump($product_id);
        dd($quantity);
    }

  
}