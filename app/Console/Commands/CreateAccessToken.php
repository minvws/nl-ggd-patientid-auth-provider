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

        if (stream_isatty(STDOUT)) {
            print $this->entrypointUri($token) . "\n";
        } else {
            print $token . "\n";
        }

        return 0;
    }

    private function entrypointUri(string $token): string
    {
        $redirect_uris = config('app.redirect_uris');
        $redirect_uri = is_array($redirect_uris) ? $redirect_uris[0] : '';
        $params = ['access_token' => $token, 'redirect_uri' => $redirect_uri];
        return route('entrypoint', $params);
    }
}
