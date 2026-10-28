<?php

namespace App\Services\Contracts;

use App\Models\ProjectImport;

interface ProjectImportHandlerInterface
{
    /**
     * Process the queued project import for this module.
     *
     * @param  \App\Models\ProjectImport  $import
     * @return void
     */
    public function process(ProjectImport $import): void;
}
