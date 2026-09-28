<?php

namespace SageCounseling\Helpers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Debug-level logging that only writes when config('app.debug') is on. Replaces rps's SageDebug trait
 * ($this->debug() becomes Debug::log(), $this->debugAt() becomes Debug::here()).
 */
class Debug
{
    public static function log(string $message): void
    {
        if (Config::get('app.debug')) {
            Log::debug($message);
        }
    }

    /**
     * Logs "In: <class>::<method>" for whichever method called here().
     */
    public static function here(): void
    {
        if (! Config::get('app.debug')) {
            return;
        }

        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? [];
        $class = isset($caller['class']) ? $caller['class'].'::' : '';

        Log::debug('In: '.$class.($caller['function'] ?? '{main}'));
    }
}
