<?php

namespace App\Jobs;

use App\Mail\SendUserEmail;
use App\Notifications\WelcomeMailNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;


class SendEmail implements ShouldQueue
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
            Mail::to($this->userMail)->send(new SendUserEmail($this->dataMail));
            \Log::info('Verification email sent successfully', [
                'email' => $this->userMail,
                'user_data' => $this->dataMail
            ]);
        } catch (\Exception $ex) {
            \Log::error('Failed to send verification email', [
                'email' => $this->userMail,
                'error' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString()
            ]);
            throw $ex; // Re-throw so the job fails properly
        }
    }
}
