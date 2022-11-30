<?php

namespace A2PVodafone\NotificationMessages;

use Illuminate\Notifications\Notification;

class A2PVodafoneMessage
{
    protected $from;
    protected $to;
    protected $text;

    public function __construct()
    {
        return $this;
    }

    public function from($from)
    {
        $this->from = $from;

        return $this;
    }

    public function to($to)
    {
        $this->to = $to;

        return $this;
    }

    public function text($text)
    {
        $this->text = $text;

        return $this;
    }

    public function send(){}
}
