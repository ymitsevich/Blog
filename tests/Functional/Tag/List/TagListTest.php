<?php

namespace App\Tests\Functional\Tag\List;

use Symfony\Component\HttpFoundation\Response;
use Ymitsevich\Funker\FunkerTestCaseBase;

class TagListTest extends FunkerTestCaseBase
{
    public function testTagList_collection_success(): void
    {
        $this->client->request('GET', '/api/tag');
        $this->assertResponseStatusCode(Response::HTTP_OK);
        $this->assertContentEqualsToSnapshot();
    }
}
