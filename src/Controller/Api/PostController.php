<?php

namespace App\Controller\Api;

use App\Exception\UnauthorizedException;
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
use OpenApi\Annotations as OA;

class PostController extends AbstractController
{
    private Request $request;

    public function __construct(
        private PostService $postService,
        private NormalizerInterface $normalizer,
        private ResponseBuilder $responseBuilder,
        RequestStack $requestStack,
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }

    #[Operation(properties: ['operationId' => 'ListPosts'])]
    #[OA\Tag(properties: ['name' => 'Post'])]
    #[Response(response: 200, description: 'List posts')]
    #[Route(path: '/post', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $entities = $this->postService->getAll();
        $data = $this->normalizer->normalize($entities, JsonEncoder::FORMAT, [
            'groups' => ['id', 'post_list'],
        ]);

        return $this->responseBuilder->buildSuccess($data);
    }

    #[Operation(properties: ['operationId' => 'ShowPost'])]
    #[OA\Tag(properties: ['name' => 'Post'])]
    #[Response(response: 200, description: 'Show post')]
    #[Response(response: 404, description: 'Post not found')]
    #[Route(path: '/post/{id}', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $entity = $this->postService->getById($id);
        } catch (EntityNotFoundException $e) {
            return $this->responseBuilder->buildNotFound($e->getMessage());
        }

        $data = $this->normalizer->normalize($entity, JsonEncoder::FORMAT, [
            'groups' => ['id', 'post_show'],
        ]);

        return $this->responseBuilder->buildSuccess($data);
    }

    #[Operation(properties: ['operationId' => 'CreatePost'])]
    #[OA\Tag(properties: ['name' => 'Post'])]
    #[Response(response: 201, description: 'Create post')]
    #[Response(response: 400, description: 'Invalid input data')]
    #[Route(path: '/post', methods: ['POST'])]
    public function create(): JsonResponse
    {
        $rawData = $this->request->request->all();

        try {
            $entity = $this->postService->create($rawData);
            $data = $this->normalizer->normalize($entity, JsonEncoder::FORMAT, [
                'groups' => ['id', 'post_create'],
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

    #[Operation(properties: ['operationId' => 'UpdatePost'])]
    #[OA\Tag(properties: ['name' => 'Post'])]
    #[Response(response: 200, description: 'Update post')]
    #[Response(response: 400, description: 'Invalid input data')]
    #[Response(response: 404, description: 'Post not found')]
    #[Route(path: '/post/{id}', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function update(int $id): JsonResponse
    {
        $rawData = $this->request->request->all();

        try {
            $entity = $this->postService->update($rawData, $id);
            $data = $this->normalizer->normalize($entity, JsonEncoder::FORMAT, [
                'groups' => ['id', 'post_create'],
            ]);
        } catch (AuthenticationException|UnauthorizedException) {
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
        } catch (EntityNotFoundException $e) {
            return $this->responseBuilder->buildNotFound($e->getMessage());
        }

        return $this->responseBuilder->buildSuccess($data);
    }

    #[Operation(properties: ['operationId' => 'DeletePost'])]
    #[OA\Tag(properties: ['name' => 'Post'])]
    #[Response(response: 204, description: 'Delete post')]
    #[Response(response: 404, description: 'Post not found')]
    #[Route(path: '/post/{id}', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->postService->delete($id);
        } catch (AuthenticationException|UnauthorizedException) {
            return $this->responseBuilder->buildAuthError();
        } catch (EntityNotFoundException $e) {
            return $this->responseBuilder->buildNotFound($e->getMessage());
        }

        return $this->responseBuilder->buildSuccessEmpty();
    }
}
