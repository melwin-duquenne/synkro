<?php

namespace App\Dto\Ai;

class AiChatOutput
{
    public string $response;

    public function __construct(string $response)
    {
        $this->response = $response;
    }
}
