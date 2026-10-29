<?php

namespace App\Services\Contracts;

use App\Models\ProjectImport;

interface ProjectImportHandlerInterface
{
    /**
     * Process the queued project import for this module.
     * Supports both non-database tracked import identifier string and legacy ProjectImport model.
     *
     * @param  \App\Models\ProjectImport|string  $import
     * @return void
     */
    public function process(ProjectImport|string $import): void;
}
