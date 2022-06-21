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
        $patient_id = $this->argument("patient_id");
        $birthdate = $this->argument("birthdate");

        print $this->codeGeneratorService->createHash(
            is_string($patient_id) ? $patient_id : "",
            is_string($birthdate) ? $birthdate : "",
        ) . "\n";

        return 0;
    }
}
