<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PdfMail extends Mailable
{
    use Queueable, SerializesModels;

    public $_from;
    public $_subject;
    public $_body;
    public $pdfData;
    public $pdfFileName;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($from, $subject, $body, $pdfFileName, $pdfData)
    {
        $this->_from       = $from;
        $this->_subject    = $subject;
        $this->_body       = $body;
        $this->pdfFileName = $pdfFileName;
        $this->pdfData     = $pdfData;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        app('sentry')->captureMessage('Письмо отправлено из очереди', [],
            ['level' => 'info']);

        return $this->view('rawText', ['rawText' => $this->_body])
                    ->from($this->_from)
                    ->subject($this->_subject)
                    ->attachData(base64_decode($this->pdfData), $this->pdfFileName, [
                        'mime' => 'application/pdf',
                    ]);
    }
}
