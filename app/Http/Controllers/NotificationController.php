<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\Log;
use App\Models\Notification;
use Exception;

class NotificationController extends Controller
{
    // Метод для создания уведомления и отправки его в очередь RabbitMQ
    public function create(Request $request)
    {
        $request->validate([
            'recipient' => 'required|email',
            'sender' => 'required|email',
            'message' => 'required|string',
        ]);

        $notification = $request->all();

        // Отправка уведомления в очередь RabbitMQ
        $this->sendToQueue($notification);

        return response()->json(['status' => 'Notification queued'], 201);
    }

    // Метод для отправки данных в очередь RabbitMQ
    protected function sendToQueue($data)
    {
        $config = config('rabbitmq');
        try {
            $connection = new AMQPStreamConnection(
                $config['host'], $config['port'], $config['user'], $config['password']
            );
            $channel = $connection->channel();

            $channel->queue_declare($config['queue'], false, true, false, false);

            $msg = new AMQPMessage(json_encode($data), [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
            ]);

            $channel->basic_publish($msg, '', $config['queue']);

            // Логирование отправки сообщения
            Log::info("Sent to queue: " . $msg->getBody());

            $channel->close();
            $connection->close();
        } catch (Exception $e) {
            Log::error('Error sending to queue: ' . $e->getMessage());
        }
    }

    // Метод для получения списка уведомлений из базы данных
    public function index()
    {
        $notifications = Notification::all();
        return response()->json($notifications);
    }
}
