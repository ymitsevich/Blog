<?php

namespace App\Tests\Functional\Comment\Delete;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\HttpFoundation\Response;
use Ymitsevich\Funker\FunkerTestCaseBase;

class CommentDeleteTest extends FunkerTestCaseBase
{
    private UserRepository|ObjectRepository $userRepository;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->entityManager->getRepository(User::class);
        $this->user = $this->userRepository->findOneBy(['username' => 'alexbobson']);
    }

    public function testCommentDelete_entity_success(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request('DELETE', '/api/comment/1001');
        $this->assertResponseStatusCode(Response::HTTP_NO_CONTENT);
        $this->assertContentEqualsToSnapshot();
    }

    public function testCommentDelete_nonExistingEntity_notFound(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request('DELETE', '/api/comment/1013');
        $this->assertResponseStatusCode(Response::HTTP_NOT_FOUND);
        $this->assertContentEqualsToSnapshot();
    }

    public function testCommentCreate_noLogin_unauthorized(): void
    {
        $this->client->request('DELETE', '/api/comment/1001');
        $this->assertResponseStatusCode(Response::HTTP_UNAUTHORIZED);
        $this->assertContentEqualsToSnapshot();
    }

    public function testCommentDelete_foreignPost_unauthorized(): void
    {
        $this->user = $this->userRepository->findOneBy(['username' => 'johndoe']);
        $this->client->loginUser($this->user);
        $this->client->request('DELETE', '/api/comment/1001');
        $this->assertResponseStatusCode(Response::HTTP_UNAUTHORIZED);
        $this->assertContentEqualsToSnapshot();
    }
}
