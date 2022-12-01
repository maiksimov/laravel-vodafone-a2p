<?php

namespace A2PVodafone\NotificationMessages;

class A2PVodafoneMessage
{
    protected mixed $content;

    public function __construct($content = null)
    {
        $this->content = $content;

        return $this;
    }

    public function setContent($content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getContent()
    {
        return $this->content;
    }
}
