<?php

namespace App;

trait JSONResponseTrait
{
    protected function successResponse(string $message, $data, int $httpResponseCode = 200)
    {
        return response()->json([
            'success'    => true,
            'message'    => $message ?? null,
            'data'       => $data,
            'errors'     => null,
        ], $httpResponseCode);
    }

    protected function errorResponse(string $message, $data, ?array $errors = [], int $httpResponseCode = 400)
    {
        return response()->json([
            'success'    => false,
            'message'    => $message ?? null,
            'data'       => $data ?? null,
            'errors'     => $errors ?? null,
        ], $httpResponseCode);
    }
}
