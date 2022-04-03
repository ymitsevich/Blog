<?php

namespace App\Controller\Api;

use App\Exception\UnauthorizedException;
use App\Service\CommentService;
use App\Service\PostService;
use App\Service\ResponseBuilder;
use Doctrine\ORM\EntityNotFoundException;
use LogicException;
use OpenApi\Attributes\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ConstraintViolationListNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Nelmio\ApiDocBundle\Annotation\Operation;

class CommentController extends AbstractController
{
    private Request $request;

    public function __construct(
        private CommentService $commentService,
        private PostService $postService,
        private NormalizerInterface $normalizer,
        private ResponseBuilder $responseBuilder,
        RequestStack $requestStack,
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }

    #[Operation(properties: ['operationId' => 'ListComments'])]
    #[Response(response: 200, description: 'List comments')]
    #[Route(path: '/comment', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $entities = $this->commentService->getAll();
        $data = $this->normalizer->normalize($entities, JsonEncoder::FORMAT, [
            'groups' => ['id', 'comment_list'],
        ]);

        return $this->responseBuilder->buildSuccess($data);
    }

    #[Operation(properties: ['operationId' => 'ListCommentsForPost'])]
    #[Response(response: 200, description: 'List comments for Post')]
    #[Route(path: '/post/{postId}/comment', methods: ['GET'])]
    public function indexForPost(int $postId): JsonResponse
    {
        try {
            $entities = $this->postService->getById($postId)->getComments();
        } catch (EntityNotFoundException $e) {
            return $this->responseBuilder->buildNotFound($e->getMessage());
        }

        $data = $this->normalizer->normalize($entities, JsonEncoder::FORMAT, [
            'groups' => ['id', 'comment_list'],
        ]);

        return $this->responseBuilder->buildSuccess($data);
    }

    #[Operation(properties: ['operationId' => 'ShowComment'])]
    #[Response(response: 200, description: 'Show comment')]
    #[Response(response: 404, description: 'Comment not found')]
    #[Route(path: '/comment/{id}', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $entity = $this->commentService->getById($id);
        } catch (EntityNotFoundException $e) {
            return $this->responseBuilder->buildNotFound($e->getMessage());
        }

        $data = $this->normalizer->normalize($entity, JsonEncoder::FORMAT, [
            'groups' => ['id', 'comment_show'],
        ]);

        return $this->responseBuilder->buildSuccess($data);
    }

    #[Operation(properties: ['operationId' => 'CreateCommentForPost'])]
    #[Response(response: 201, description: 'Create comment for post')]
    #[Response(response: 400, description: 'Invalid input data')]
    #[Route(path: '/post/{postId}/comment', methods: ['POST'])]
    public function createForPost(int $postId): JsonResponse
    {
        try {
            $post = $this->postService->getById($postId);
        } catch (EntityNotFoundException $e) {
            return $this->responseBuilder->buildNotFound($e->getMessage());
        }

        $rawData = $this->request->request->all();
        try {
            $entity = $this->commentService->create($rawData, $post);
            $data = $this->normalizer->normalize($entity, JsonEncoder::FORMAT, [
                'groups' => ['id', 'comment_create'],
            ]);
        } catch (AuthenticationException) {
            return $this->responseBuilder->buildAuthError();
        } catch (ValidationFailedException $e) {
            $errors = $this->normalizer->normalize(
                $e->getViolations(),
                context: [
                    ConstraintViolationListNormalizer::TYPE => 'Input error.',
                ]
            );

            return $this->responseBuilder->buildBad($errors);
        } catch (LogicException|ExceptionInterface $e) {
            return $this->responseBuilder->buildBad($e->getMessage());
        }

        return $this->responseBuilder->buildCreated($data);
    }

    #[Operation(properties: ['operationId' => 'DeleteComment'])]
    #[Response(response: 204, description: 'Delete comment')]
    #[Route(path: '/comment/{id}', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->commentService->delete($id);
        } catch (AuthenticationException|UnauthorizedException) {
            return $this->responseBuilder->buildAuthError();
        } catch (EntityNotFoundException $e) {
            return $this->responseBuilder->buildNotFound($e->getMessage());
        }

        return $this->responseBuilder->buildSuccessEmpty();
    }
}
