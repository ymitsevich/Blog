<?php

namespace App\Tests\Functional\Tag\Show;

use Symfony\Component\HttpFoundation\Response;
use Ymitsevich\Funker\FunkerTestCaseBase;

class TagShowTest extends FunkerTestCaseBase
{
    public function testTagShow_entity_success(): void
    {
        $this->client->request('GET', '/api/tag/101');
        $this->assertResponseStatusCode(Response::HTTP_OK);
        $this->assertContentEqualsToSnapshot();
    }

    public function testTagShow_nonExistingEntity_notFound(): void
    {
        $this->client->request('GET', '/api/tag/103');
        $this->assertResponseStatusCode(Response::HTTP_NOT_FOUND);
        $this->assertContentEqualsToSnapshot();
    }
}
