<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean:codes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleans up expired codes';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $count = DB::table('codes')->where('expires_at', '<', time())->delete();
        print "Deleted $count expired codes\n";

        return 0;
    }
}
