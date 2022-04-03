<?php

namespace App\Tests\Functional\Comment\Show;

use Symfony\Component\HttpFoundation\Response;
use Ymitsevich\Funker\FunkerTestCaseBase;

class CommentShowTest extends FunkerTestCaseBase
{
    public function testCommentShow_entity_success(): void
    {
        $this->client->request('GET', '/api/comment/1001');
        $this->assertResponseStatusCode(Response::HTTP_OK);
        $this->assertContentEqualsToSnapshot();
    }

    public function testCommentShow_nonExistingEntity_notFound(): void
    {
        $this->client->request('GET', '/api/comment/1003');
        $this->assertResponseStatusCode(Response::HTTP_NOT_FOUND);
        $this->assertContentEqualsToSnapshot();
    }
}
