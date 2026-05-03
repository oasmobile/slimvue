<?php

namespace Oasis\SlimVue\Demo;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoErrorHandler
{
    public function __invoke(\Throwable $e, Request $request, int $code): Response
    {
        $message = match ($code) {
            404     => 'Page not found',
            default => 'Internal server error: ' . $e->getMessage(),
        };

        return new Response(
            "<h1>Error {$code}</h1><p>{$message}</p>",
            $code,
        );
    }
}
