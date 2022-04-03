<?php

namespace App\Tests\Unit\Service;

use App\Service\ResponseBuilder;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ResponseBuilderTest extends TestCase
{
    private ResponseBuilder $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ResponseBuilder();
    }

    public function testBuildBad()
    {
        $referencedResponse = new JsonResponse(
            [
                'status' => false,
                'message' => 'text',
            ],
            status: Response::HTTP_BAD_REQUEST
        );

        $assertingResponse = $this->service->buildBad('text');
        $this->assertEquals($referencedResponse, $assertingResponse);
    }

    public function testBuildNotFound()
    {
        $referencedResponse = new JsonResponse(
            [
                'status' => false,
                'message' => 'text',
            ],
            status: Response::HTTP_NOT_FOUND
        );

        $assertingResponse = $this->service->buildNotFound('text');
        $this->assertEquals($referencedResponse, $assertingResponse);
    }

    public function testBuildSuccessEmpty()
    {
        $referencedResponse = new JsonResponse(
            new stdClass(),
            status: Response::HTTP_NO_CONTENT
        );

        $assertingResponse = $this->service->buildSuccessEmpty();
        $this->assertEquals($referencedResponse, $assertingResponse);
    }

    public function testBuildAuthError()
    {
        $referencedResponse = new JsonResponse(
            [
                'status' => false,
                'message' => 'text',
            ],
            status: Response::HTTP_UNAUTHORIZED
        );

        $assertingResponse = $this->service->buildAuthError('text');
        $this->assertEquals($referencedResponse, $assertingResponse);
    }

    public function testBuildCreated()
    {
        $referencedResponse = new JsonResponse(
            [
                'status' => true,
                'data' => ['text'],
            ],
            status: Response::HTTP_CREATED
        );

        $assertingResponse = $this->service->buildCreated(['text']);
        $this->assertEquals($referencedResponse, $assertingResponse);
    }

    public function testBuildSuccess()
    {
        $referencedResponse = new JsonResponse(
            [
                'status' => true,
                'data' => ['text'],
            ],
            status: Response::HTTP_OK
        );

        $assertingResponse = $this->service->buildSuccess(['text']);
        $this->assertEquals($referencedResponse, $assertingResponse);
    }
}
