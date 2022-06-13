<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SmsService;
use Illuminate\Console\Command;

class TestSms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:sms {phoneNumber} {code}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test SMS';

    protected SmsService $smsService;

    public function __construct(
        SmsService $smsService,
    ) {
        parent::__construct();

        $this->smsService = $smsService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return (int) $this->smsService->send(
            $this->argument('phoneNumber'),
            'template',
            ['code' => $this->argument('code')]
        );
    }
}
