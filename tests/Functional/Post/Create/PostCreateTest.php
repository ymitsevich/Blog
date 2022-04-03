<?php

namespace App\Tests\Functional\Post\Create;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\HttpFoundation\Response;
use Ymitsevich\Funker\FunkerTestCaseBase;

class PostCreateTest extends FunkerTestCaseBase
{
    private UserRepository|ObjectRepository $userRepository;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->entityManager->getRepository(User::class);
        $this->user = $this->userRepository->findOneBy(['username' => 'alexbobson']);
    }

    public function testPostCreate_rawData_success(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request('POST', '/api/post', [
            'title' => 'New Title',
            'content' => 'New content...',
            'tags' => [
                ['id' => 101],
                ['id' => 103],
            ],
        ]);
        $this->assertResponseStatusCode(Response::HTTP_CREATED);
        $this->assertContentEqualsToSnapshot();
    }

    public function testPostCreate_invalidData_badRequest(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request('POST', '/api/post', []);
        $this->assertResponseStatusCode(Response::HTTP_BAD_REQUEST);
        $this->assertContentEqualsToSnapshot();
    }

    public function testPostCreate_noLogin_unauthorized(): void
    {
        $this->client->request('POST', '/api/post', [
            'title' => 'New Title',
            'content' => 'New content...',
        ]);
        $this->assertResponseStatusCode(Response::HTTP_UNAUTHORIZED);
        $this->assertContentEqualsToSnapshot();
    }
}
