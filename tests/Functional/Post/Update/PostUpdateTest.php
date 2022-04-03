<?php

namespace App\Tests\Functional\Post\Update;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\HttpFoundation\Response;
use Ymitsevich\Funker\FunkerTestCaseBase;

class PostUpdateTest extends FunkerTestCaseBase
{
    private UserRepository|ObjectRepository $userRepository;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->entityManager->getRepository(User::class);
        $this->user = $this->userRepository->findOneBy(['username' => 'alexbobson']);
    }

    public function testPostUpdate_entity_success(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request('PATCH', '/api/post/10', [
            'title' => 'New Title',
            'content' => 'New content...',
            'tags' => [
                ['id' => 102],
                ['id' => 103],
            ],
        ]);
        $this->assertResponseStatusCode(Response::HTTP_OK);
        $this->assertContentEqualsToSnapshot();
    }

    public function testPostUpdate_emptyData_noChange(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request('PATCH', '/api/post/10', []);
        $this->assertResponseStatusCode(Response::HTTP_OK);
        $this->assertContentEqualsToSnapshot();
    }

    public function testPostUpdate_noLogin_unauthorized(): void
    {
        $this->client->request('PATCH', '/api/post/10', [
            'title' => 'New Title',
            'content' => 'New content...',
        ]);
        $this->assertResponseStatusCode(Response::HTTP_UNAUTHORIZED);
        $this->assertContentEqualsToSnapshot();
    }

    public function testPostUpdate_foreignPost_unauthorized(): void
    {
        $this->user = $this->userRepository->findOneBy(['username' => 'johnsmith']);
        $this->client->loginUser($this->user);
        $this->client->request('PATCH', '/api/post/10', [
            'title' => 'New Title',
            'content' => 'New content...',
        ]);
        $this->assertResponseStatusCode(Response::HTTP_UNAUTHORIZED);
        $this->assertContentEqualsToSnapshot();
    }

    public function testPostUpdate_nonExistingEntity_notFound(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request('PATCH', '/api/post/13', [
            'title' => 'New Title',
            'content' => 'New content...',
        ]);
        $this->assertResponseStatusCode(Response::HTTP_NOT_FOUND);
        $this->assertContentEqualsToSnapshot();
    }
}
