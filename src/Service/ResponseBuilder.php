<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ResponseBuilder
{
    private const DATA_KEY = 'data';
    private const STATUS_KEY = 'status';
    private const MESSAGE_KEY = 'message';
    private const SUCCESS_STATUS = true;
    private const FAIL_STATUS = false;

    public function buildSuccess(array $data = null): JsonResponse
    {
        return new JsonResponse([
            self::STATUS_KEY => self::SUCCESS_STATUS,
            self::DATA_KEY => $data,
        ]);
    }

    public function buildSuccessEmpty(): JsonResponse
    {
        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }

    public function buildCreated(array $data): JsonResponse
    {
        return new JsonResponse(
            [
                self::STATUS_KEY => self::SUCCESS_STATUS,
                self::DATA_KEY => $data,
            ],
            status: Response::HTTP_CREATED
        );
    }

    public function buildNotFound(string $message): JsonResponse
    {
        return new JsonResponse(
            [
                self::STATUS_KEY => self::FAIL_STATUS,
                self::MESSAGE_KEY => $message,
            ],
            status: Response::HTTP_NOT_FOUND
        );
    }

    public function buildBad(string|array $message): JsonResponse
    {
        return new JsonResponse(
            [
                self::STATUS_KEY => self::FAIL_STATUS,
                self::MESSAGE_KEY => $message,
            ],
            status: Response::HTTP_BAD_REQUEST
        );
    }

    public function buildAuthError(string|array $message = null): JsonResponse
    {
        return new JsonResponse(
            [
                self::STATUS_KEY => self::FAIL_STATUS,
                self::MESSAGE_KEY => $message,
            ],
            status: Response::HTTP_UNAUTHORIZED
        );
    }
}
