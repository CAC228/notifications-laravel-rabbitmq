<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notification;

class NotificationSeeder extends Seeder
{
    public function run()
    {
        Notification::create([
            'recipient' => 'recipient1@example.com',
            'sender' => 'sender1@example.com',
            'message' => 'This is the first test message.'
        ]);

        Notification::create([
            'recipient' => 'recipient2@example.com',
            'sender' => 'sender2@example.com',
            'message' => 'This is the second test message.'
        ]);
    }
}
