<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\EmailService;
use Illuminate\Console\Command;

class TestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email {email} {code}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email';

    protected EmailService $emailService;

    public function __construct(EmailService $emailService) {
        parent::__construct();

        $this->emailService = $emailService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return (int) $this->emailService->send(
            strval($this->argument('email')),
            'template',
            ['code' => $this->argument('code')]
        );
    }
}
