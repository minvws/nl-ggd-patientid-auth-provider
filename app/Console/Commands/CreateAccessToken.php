<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\OidcService;
use Illuminate\Console\Command;

class CreateAccessToken extends Command
{
    protected OidcService $oidcService;



    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create:token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new token in the cache';

    /**
     * @param OidcService $oidcService
     */
    public function __construct(OidcService $oidcService)
    {
        parent::__construct();

        $this->oidcService = $oidcService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {

        $token = $this->oidcService->generateToken();

        print "Token: " . $token . "\n";

        return 0;
    }
}
