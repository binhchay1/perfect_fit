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

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array
     */
    public $backoff = [60, 120, 300]; // 1min, 2min, 5min

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public $timeout = 120;

    protected $userMail;
    protected $dataMail;

    /**
     * Create a new job instance.
     */
    public function __construct($userMail, $dataMail)
    {
        $this->userMail = $userMail;
        $this->dataMail = $dataMail;
        
        // Set queue using Queueable trait method
        $this->onQueue('emails');
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
