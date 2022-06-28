<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\CodeGeneratorService;
use App\Services\InfoRetrievalService;
use App\Services\JwtService;
use Illuminate\Console\Command;

class RetrieveUserInfo extends Command
{
    protected JwtService $jwtService;
    protected CodeGeneratorService $codeGeneratorService;
    protected InfoRetrievalService $infoRetrievalService;

    protected $signature = 'userinfo:retrieve {patient_id} {birthdate}';
    protected $description = 'Retrieve userinfo from Yenlo based on patient_id and birthdate';

    public function __construct(
        JwtService $jwtService,
        CodeGeneratorService $codeGeneratorService,
        InfoRetrievalService $infoRetrievalService
    ) {
        parent::__construct();

        $this->jwtService = $jwtService;
        $this->codeGeneratorService = $codeGeneratorService;
        $this->infoRetrievalService = $infoRetrievalService;
    }

    /**
     * @throws \JsonException
     */
    public function handle(): int
    {
        $patient_id = $this->argument("patient_id");
        $birthdate = $this->argument("birthdate");

        $hash = $this->codeGeneratorService->createHash(
            is_string($patient_id) ? $patient_id : "",
            is_string($birthdate) ? $birthdate : "",
        );

        $result = json_encode($this->infoRetrievalService->retrieve($hash), JSON_THROW_ON_ERROR);

        $this->line($result);

        return 0;
    }
}
