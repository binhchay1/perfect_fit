<?php

declare(strict_types=1);

namespace App\Services;

use Google_Client;
use Google_Service_Gmail;
use Google_Service_Gmail_Message;
use Illuminate\Support\Facades\Log;

final class GmailApiService
{
    private Google_Client $client;
    private ?Google_Service_Gmail $service = null;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setClientId(config('services.gmail.client_id'));
        $this->client->setClientSecret(config('services.gmail.client_secret'));
        $this->client->setAccessType('offline');
        $this->client->setApprovalPrompt('force');
        
        // Set refresh token
        $refreshToken = config('services.gmail.refresh_token');
        if ($refreshToken) {
            $this->client->refreshToken($refreshToken);
        }
    }

    public function sendEmail(string $to, string $subject, string $htmlBody): bool
    {
        try {
            if (!$this->service) {
                $this->service = new Google_Service_Gmail($this->client);
            }

            // Create email message
            $message = $this->createMessage(
                config('services.gmail.from.address'),
                $to,
                $subject,
                $htmlBody
            );

            // Send via Gmail API
            $this->service->users_messages->send('me', $message);

            Log::channel('email_debug')->info('Gmail API: Email sent via API', [
                'to' => $to,
                'subject' => $subject,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::channel('email_debug')->error('Gmail API: Failed to send', [
                'to' => $to,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    private function createMessage(string $from, string $to, string $subject, string $htmlBody): Google_Service_Gmail_Message
    {
        $fromName = config('services.gmail.from.name', 'Perfect Fit');
        
        $rawMessage = "From: {$fromName} <{$from}>\r\n";
        $rawMessage .= "To: {$to}\r\n";
        $rawMessage .= "Subject: {$subject}\r\n";
        $rawMessage .= "MIME-Version: 1.0\r\n";
        $rawMessage .= "Content-Type: text/html; charset=utf-8\r\n";
        $rawMessage .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $rawMessage .= chunk_split(base64_encode($htmlBody));

        $message = new Google_Service_Gmail_Message();
        $message->setRaw(rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '='));

        return $message;
    }

    public function checkConnection(): bool
    {
        try {
            if (!$this->service) {
                $this->service = new Google_Service_Gmail($this->client);
            }

            // Test by getting profile
            $profile = $this->service->users->getProfile('me');
            
            Log::info('Gmail API connection successful', [
                'email' => $profile->getEmailAddress(),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Gmail API connection failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}

