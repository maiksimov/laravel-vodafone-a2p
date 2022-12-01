<?php
namespace A2PVodafone\NotificationChannels;

use A2PVodafone\Exceptions\IncorrectMessageClass;
use A2PVodafone\NotificationMessages\A2PVodafoneMessage;
use A2PVodafone\VodafoneClient;
use A2PVodafone\Exceptions\MethodDoesNotExist;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Notifications\Notification;

class VodafoneChannel
{
    public function __construct(protected readonly VodafoneClient $client)
    {
    }

    /**
     * @throws IncorrectMessageClass
     * @throws GuzzleException
     * @throws MethodDoesNotExist
     */
    public function send($notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toVodafone')) {
            throw new MethodDoesNotExist('The toVodafone method does not exist in your notification class.');
        }

        $message = $notification->toVodafone($notifiable);

        if (!$message instanceof A2PVodafoneMessage) {
            throw new IncorrectMessageClass('Your notification message must be an instance of the A2PVodafoneMessage class.');
        }

        $this->client->send(
            $notifiable->smsNotificationFor(),
            $message->getContent()
        );
    }
}
