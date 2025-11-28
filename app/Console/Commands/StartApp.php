<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\Process as ComponentProcess;

class StartApp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'start:app';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the application services';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $f1 = $this->call('optimize:clear');

        $this->info('cache cleared...');
        Log::info('cache cleared...' . $f1);

        // $serve = Process::start('php artisan serve --host=127.0.0.1 --port=8000');
        // Log::info('cache cleared...' . $serve->getPid());

        // $serve = ComponentProcess::fromShellCommandline('php artisan serve --host=127.0.0.1 --port=8000', base_path());
        // $serve->start();
        $this->info('Server started...');
        $this->callSilently('serve');

        // Process::start('php artisan reverb:start');
        // $reverb = ComponentProcess::fromShellCommandline('php artisan reverb:start', base_path());
        // $reverb->start();
        $this->info('Reverb service started...');
        $this->callSilently('reverb:start');

        $this->callSilently('queue:listen');
        // $queue = ComponentProcess::fromShellCommandline('php artisan queue:work', base_path());
        // $queue->start();
        $this->info('Queue started...');
    }
}
