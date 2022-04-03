<?php

namespace App\Tests\Functional\Comment\List;

use Symfony\Component\HttpFoundation\Response;
use Ymitsevich\Funker\FunkerTestCaseBase;

class CommentListTest extends FunkerTestCaseBase
{
    public function testCommentList_collection_success(): void
    {
        $this->client->request('GET', '/api/comment');
        $this->assertResponseStatusCode(Response::HTTP_OK);
        $this->assertContentEqualsToSnapshot();
    }
}
