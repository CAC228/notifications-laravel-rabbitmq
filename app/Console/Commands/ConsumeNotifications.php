<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use App\Models\Notification;
use Exception;
use Illuminate\Support\Facades\Log;

error_reporting(E_ALL & ~E_DEPRECATED);

class ConsumeNotifications extends Command
{
    protected $signature = 'rabbitmq:consume';
    protected $description = 'Consume notifications from RabbitMQ';

    protected $shouldStop = false;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $config = config('rabbitmq');
        try {
            $connection = new AMQPStreamConnection(
                $config['host'], $config['port'], $config['user'], $config['password']
            );
            $channel = $connection->channel();

            $channel->queue_declare($config['queue'], false, true, false, false);

            $callback = function ($msg) use ($channel) {
                try {
                    $data = json_decode($msg->body, true);

                    // Логирование полученного сообщения
                    Log::info('Received from queue: ' . $msg->body);

                    // Сохраняем уведомление в базу данных
                    Notification::create([
                        'recipient' => $data['recipient'],
                        'sender' => $data['sender'],
                        'message' => $data['message'],
                    ]);

                    // Подтверждаем обработку сообщения
                    $channel->basic_ack($msg->delivery_info['delivery_tag']);
                } catch (Exception $e) {
                    Log::error('Error processing message: ' . $e->getMessage());
                    $channel->basic_nack($msg->delivery_info['delivery_tag'], false, true); // Сообщаем RabbitMQ о неудачной обработке и просим повторную доставку
                }
            };

            $channel->basic_consume($config['queue'], '', false, false, false, false, $callback);

            // Используем цикл с проверкой флага для остановки потребителя
            while (!$this->shouldStop) {
                $channel->wait(null, false, 5000); // Таймаут 5 секунд для проверки флага остановки
            }

            $channel->close();
            $connection->close();
        } catch (Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            Log::error('Error: ' . $e->getMessage());
        }
    }

    public function stop()
    {
        $this->shouldStop = true;
    }
}
