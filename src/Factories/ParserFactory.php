<?php

namespace Alshahari\AuthTracker\Factories;

use Alshahari\AuthTracker\Interfaces\UserAgentParser;
use Alshahari\AuthTracker\Parsers\Agent;
use Alshahari\AuthTracker\Parsers\WhichBrowser;
use InvalidArgumentException;

class ParserFactory
{
    /**
     * Build a new user-agent parser.
     *
     * @throws InvalidArgumentException
     */
    public static function build(?string $name): UserAgentParser
    {
        return match ($name) {
            'agent', null => new Agent,
            'whichbrowser' => class_exists(\WhichBrowser\Parser::class)
                ? new WhichBrowser
                : throw new InvalidArgumentException('Install whichbrowser/parser to use the "whichbrowser" parser.'),
            default => throw new InvalidArgumentException("Unsupported User-Agent parser [{$name}]."),
        };
    }
}
