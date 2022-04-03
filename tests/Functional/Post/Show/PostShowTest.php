<?php

namespace App\Tests\Functional\Post\Show;

use Symfony\Component\HttpFoundation\Response;
use Ymitsevich\Funker\FunkerTestCaseBase;

class PostShowTest extends FunkerTestCaseBase
{
    public function testPostShow_entity_success(): void
    {
        $this->client->request('GET', '/api/post/10');
        $this->assertResponseStatusCode(Response::HTTP_OK);
        $this->assertContentEqualsToSnapshot();
    }

    public function testPostShow_nonExistingEntity_notFound(): void
    {
        $this->client->request('GET', '/api/post/13');
        $this->assertResponseStatusCode(Response::HTTP_NOT_FOUND);
        $this->assertContentEqualsToSnapshot();
    }
}
