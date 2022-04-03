<?php

namespace App\Tests\Functional\Post\Delete;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\HttpFoundation\Response;
use Ymitsevich\Funker\FunkerTestCaseBase;

class PostDeleteTest extends FunkerTestCaseBase
{
    private UserRepository|ObjectRepository $userRepository;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->entityManager->getRepository(User::class);
        $this->user = $this->userRepository->findOneBy(['username' => 'alexbobson']);
    }

    public function testPostDelete_entity_success(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request('DELETE', '/api/post/10');
        $this->assertResponseStatusCode(Response::HTTP_NO_CONTENT);
        $this->assertContentEqualsToSnapshot();
    }

    public function testPostDelete_nonExistingEntity_notFound(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request('DELETE', '/api/post/13');
        $this->assertResponseStatusCode(Response::HTTP_NOT_FOUND);
        $this->assertContentEqualsToSnapshot();
    }

    public function testPostCreate_noLogin_unauthorized(): void
    {
        $this->client->request('DELETE', '/api/post/10');
        $this->assertResponseStatusCode(Response::HTTP_UNAUTHORIZED);
        $this->assertContentEqualsToSnapshot();
    }

    public function testPostDelete_foreignPost_unauthorized(): void
    {
        $this->user = $this->userRepository->findOneBy(['username' => 'johnsmith']);
        $this->client->loginUser($this->user);
        $this->client->request('DELETE', '/api/post/10');
        $this->assertResponseStatusCode(Response::HTTP_UNAUTHORIZED);
        $this->assertContentEqualsToSnapshot();
    }
}
