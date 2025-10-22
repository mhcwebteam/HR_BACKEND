<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmployeeMail extends Mailable
{
    use Queueable, SerializesModels;
    /**
     * Create a new message instance.
     */
   public $subjectText;
    public $bodyData;
    public $url;
    public function __construct($subjectText, $bodyData,$url)
    {
        $this->subjectText = $subjectText;
        $this->bodyData    = $bodyData;
        $this->url=$url;
    }
    public function build()
    {
        return $this->subject($this->subjectText)
                    ->view('emails.empEmail')
                    ->with(['bodyData' => $this->bodyData,'url'=>$this->url]);
    }

}
