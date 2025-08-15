<?php

namespace App\Jobs;

use App\Mail\MailResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmailResetPassword implements ShouldQueue
{
    
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected $userMail;
    protected $dataMail;
    /**
     * Create a new job instance.
     */
    public function __construct($userMail, $dataMail)
    {
        $this->userMail = $userMail;
        $this->dataMail = $dataMail;
    }
    
    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            Mail::to($this->userMail)->send(new MailResetPassword( $this->dataMail));
        
        } catch (\Exception $ex) {
      
        }
        
    }
}
