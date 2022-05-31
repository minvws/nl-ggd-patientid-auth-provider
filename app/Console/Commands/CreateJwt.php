<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\CodeGeneratorService;
use App\Services\InfoRetrievalGateway\Yenlo;
use App\Services\JwtService;
use Illuminate\Console\Command;

class CreateJwt extends Command
{
    protected JwtService $jwtService;
    protected CodeGeneratorService $codeGeneratorService;
    protected Yenlo $yenlo;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create:jwt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new token in the cache';


    public function __construct(
        JwtService $jwtService,
        CodeGeneratorService $codeGeneratorService,
        Yenlo $yenlo
    ) {
        parent::__construct();

        $this->jwtService = $jwtService;
        $this->codeGeneratorService = $codeGeneratorService;
        $this->yenlo = $yenlo;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $code = $this->codeGeneratorService->generate('12345678', '1980-01-01');
        $this->yenlo->retrieve($code->hash);

        return 0;
    }
}
