<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array connect(string $channelId)
 * @method static array status(string $channelId)
 * @method static array sendMessage(string $channelId, string $recipient, string $message)
 * @method static array disconnect(string $channelId)
 *
 * @see \App\Services\WhatsApp\WhatsAppService
 */
class WhatsApp extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'whatsapp';
    }
}
