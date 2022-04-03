<?php

namespace App\Tests\Functional\Comment\CreateForPost;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\HttpFoundation\Response;
use Ymitsevich\Funker\FunkerTestCaseBase;

class CommentCreateForPostTest extends FunkerTestCaseBase
{
    private UserRepository|ObjectRepository $userRepository;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->entityManager->getRepository(User::class);
        $this->user = $this->userRepository->findOneBy(['username' => 'alexbobson']);
    }

    public function testCommentCreateForPost_rawData_success(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request('POST', '/api/post/11/comment', [
            'content' => 'Ugly style!',
        ]);
        $this->assertResponseStatusCode(Response::HTTP_CREATED);
        $this->assertContentEqualsToSnapshot();
    }

    public function testCommentCreateForPost_invalidData_badRequest(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request('POST', '/api/post/11/comment', []);
        $this->assertResponseStatusCode(Response::HTTP_BAD_REQUEST);
        $this->assertContentEqualsToSnapshot();
    }

    public function testCommentCreateForPost_noLogin_unauthorized(): void
    {
        $this->client->request('POST', '/api/post/11/comment', [
            'title' => 'New Title',
            'content' => 'New content...',
        ]);
        $this->assertResponseStatusCode(Response::HTTP_UNAUTHORIZED);
        $this->assertContentEqualsToSnapshot();
    }

    public function testCommentCreateForPost_noMainEntityFound_notFound(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request('POST', '/api/post/13/comment', [
            'title' => 'New Title',
            'content' => 'New content...',
        ]);
        $this->assertResponseStatusCode(Response::HTTP_NOT_FOUND);
        $this->assertContentEqualsToSnapshot();
    }
}
