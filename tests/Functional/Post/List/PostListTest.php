<?php

namespace App\Tests\Functional\Post\List;

use Symfony\Component\HttpFoundation\Response;
use Ymitsevich\Funker\FunkerTestCaseBase;

class PostListTest extends FunkerTestCaseBase
{
    public function testPostList_collection_success(): void
    {
        $this->client->request('GET', '/api/post');
        $this->assertResponseStatusCode(Response::HTTP_OK);
        $this->assertContentEqualsToSnapshot();
    }
}
