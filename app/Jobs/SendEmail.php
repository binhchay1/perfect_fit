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
    public function handle(\App\Services\GmailApiService $gmailService)
    {
        \Log::channel('email_debug')->info('=== SendEmail Job Started ===', [
            'job_id' => $this->job->getJobId(),
            'queue' => $this->job->getQueue(),
            'email' => $this->userMail,
            'attempts' => $this->attempts(),
            'timestamp' => now()->toDateTimeString(),
        ]);

        try {
            $mailer = config('mail.default');
            
            \Log::channel('email_debug')->info('Preparing to send email', [
                'email' => $this->userMail,
                'data' => $this->dataMail,
                'mailer' => $mailer,
            ]);

            if ($mailer === 'gmail') {
                // Use Gmail API (SMTP ports blocked)
                \Log::channel('email_debug')->info('Using Gmail API (SMTP blocked)', [
                    'email' => $this->userMail,
                ]);

                $mailable = new SendUserEmail($this->dataMail);
                $rendered = $mailable->render();
                
                $sent = $gmailService->sendEmail(
                    $this->userMail,
                    'Verify Your Account - Perfect Fit',
                    $rendered
                );

                if (!$sent) {
                    throw new \Exception('Gmail API failed to send email');
                }

            } else {
                // Use standard mailer (SMTP, etc)
                Mail::to($this->userMail)->send(new SendUserEmail($this->dataMail));
            }
            
            \Log::channel('email_debug')->info('✅ Email sent successfully!', [
                'email' => $this->userMail,
                'method' => $mailer === 'gmail' ? 'Gmail API' : 'Laravel Mailer',
                'timestamp' => now()->toDateTimeString(),
            ]);

            \Log::info('Verification email sent successfully', [
                'email' => $this->userMail,
                'user_data' => $this->dataMail
            ]);

        } catch (\Exception $ex) {
            \Log::channel('email_debug')->error('❌ Email sending FAILED', [
                'email' => $this->userMail,
                'error' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'trace' => $ex->getTraceAsString(),
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries,
            ]);

            \Log::error('Failed to send verification email', [
                'email' => $this->userMail,
                'error' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString()
            ]);

            // Re-throw to trigger retry
            throw $ex;
        } finally {
            \Log::channel('email_debug')->info('=== SendEmail Job Ended ===', [
                'email' => $this->userMail,
                'timestamp' => now()->toDateTimeString(),
            ]);
        }
    }
}
