<?php

namespace App\Tests\Functional\Comment\ListForPost;

use Symfony\Component\HttpFoundation\Response;
use Ymitsevich\Funker\FunkerTestCaseBase;

class CommentListForPostTest extends FunkerTestCaseBase
{
    public function testCommentListForPost_collection_success(): void
    {
        $this->client->request('GET', '/api/post/11/comment');
        $this->assertResponseStatusCode(Response::HTTP_OK);
        $this->assertContentEqualsToSnapshot();
    }

    public function testCommentListForPost_nonExistingEntity_notFound(): void
    {
        $this->client->request('GET', '/api/post/13/comment');
        $this->assertResponseStatusCode(Response::HTTP_NOT_FOUND);
        $this->assertContentEqualsToSnapshot();
    }
}
