<?php

namespace App\Services;

use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class OfficerAssignmentNotifier
{
    public function send(User $officer, Report $report): bool
    {
        return $this->sendToPhone(
            $officer->phone,
            "KALIKASAN WATCH: You have been assigned report #{$report->id}: {$report->title}"
        );
    }

    public function sendMessage(User $officer, string $message): bool
    {
        return $this->sendToPhone($officer->phone, $message);
    }

    public function sendToPhone(?string $phone, string $message): bool
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.from');

        if (! $sid || ! $token || ! $from || ! $phone) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth($sid, $token)
                ->timeout(10)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'To' => $phone,
                    'From' => $from,
                    'Body' => $message,
                ]);

            return $response->successful();
        } catch (ConnectionException $exception) {
            report($exception);

            return false;
        }
    }
}