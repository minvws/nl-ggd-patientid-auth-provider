<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\CodeGeneratorService;
use Illuminate\Console\Command;

class CreateHash extends Command
{
    protected CodeGeneratorService $codeGeneratorService;

    protected $signature = 'create:hash {patient_id} {birthdate}';
    protected $description = 'Create hash based on patient id and birthdate';

    public function __construct(CodeGeneratorService $codeGeneratorService)
    {
        parent::__construct();
        $this->codeGeneratorService = $codeGeneratorService;
    }

    public function handle(): int
    {
        $patient_id = strval($this->argument("patient_id"));
        $birthdate = strval($this->argument("birthdate"));

        $this->line($this->codeGeneratorService->createHash($patient_id, $birthdate));

        return 0;
    }
}
