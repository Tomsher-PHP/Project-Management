<?php

namespace App\View\Components;

use Illuminate\View\Component;

class TableSearch extends Component
{
    public $target;
    public $placeholder;

    public function __construct($target = '.searchable-table', $placeholder = 'Search...')
    {
        $this->target = $target;
        $this->placeholder = $placeholder;
    }

    public function render()
    {
        return view('components.table-search');
    }
}