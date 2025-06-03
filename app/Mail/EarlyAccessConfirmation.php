<?php

namespace App\Mail;

use App\Models\EarlyAccess;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EarlyAccessConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $application;

    public function __construct(EarlyAccess $application)
    {
        $this->application = $application;
    }

    public function build()
    {
        return $this->subject('Welcome to '.config('app.name').' Early Access Program!')
            ->text('emails.early-access.confirmation-plain');
    }
}
