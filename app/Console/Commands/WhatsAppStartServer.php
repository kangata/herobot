<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class WhatsAppStartServer extends Command
{
    protected $signature = 'whatsapp:start';
    protected $description = 'Start the WhatsApp server';

    public function handle()
    {
        $storagePath = storage_path('app/whatsapp-auth');
        
        Process::forever()
            ->tty()
            ->run(['node', base_path('whatsapp-server/server.js'), $storagePath]);
    }
}