<?php
namespace App\View\Components;

use Illuminate\View\Component;

class ColumnManager extends Component
{
    public $columns;
    public $report;

    public function __construct($columns = [], $report = 'default')
    {
        $this->columns = $columns;
        $this->report = $report;
    }

    public function render()
    {
        return view('components.column-manager');
    }
}