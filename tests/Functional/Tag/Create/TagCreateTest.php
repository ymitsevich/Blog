<?php

namespace App\Tests\Functional\Tag\Create;

use Symfony\Component\HttpFoundation\Response;
use Ymitsevich\Funker\FunkerTestCaseBase;

class TagCreateTest extends FunkerTestCaseBase
{
    public function testTagCreate_rawData_success(): void
    {
        $this->client->request('POST', '/api/tag', [
            'name' => 'fancyTag'
        ]);
        $this->assertResponseStatusCode(Response::HTTP_CREATED);
        $this->assertContentEqualsToSnapshot();
    }

    public function testTagCreate_invalidData_badRequest(): void
    {
        $this->client->request('POST', '/api/tag', [
        ]);
        $this->assertResponseStatusCode(Response::HTTP_BAD_REQUEST);
        $this->assertContentEqualsToSnapshot();
    }

    public function testTagCreate_doubleName_badRequest(): void
    {
        $this->client->request('POST', '/api/tag', [
            'name' => 'goodToKnow'
        ]);
        $this->assertResponseStatusCode(Response::HTTP_BAD_REQUEST);
        $this->assertContentEqualsToSnapshot();
    }
}
