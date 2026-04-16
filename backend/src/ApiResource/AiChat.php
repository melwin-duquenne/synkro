<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Dto\Ai\AiChatInput;
use App\Dto\Ai\AiChatOutput;
use App\State\AiChatProcessor;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/ai/chat',
            input: AiChatInput::class,
            output: AiChatOutput::class,
            processor: AiChatProcessor::class,
            security: "is_granted('ROLE_USER')",
        )
    ]
)]
class AiChat {}
