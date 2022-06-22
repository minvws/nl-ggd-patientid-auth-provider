<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Code;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendCode extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $code;

    public function __construct(Code $code)
    {
        $this->code = $code->code;
    }

    public function build(): self
    {
        return $this
            ->view('emails.code')
            ->text('emails.code_plain')
        ;
    }
}
