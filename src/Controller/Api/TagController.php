<?php

namespace App\Controller\Api;

use App\Service\ResponseBuilder;
use App\Service\TagService;
use Doctrine\ORM\EntityNotFoundException;
use LogicException;
use OpenApi\Attributes\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ConstraintViolationListNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Nelmio\ApiDocBundle\Annotation\Operation;
use OpenApi\Annotations as OA;

class TagController extends AbstractController
{
    private Request $request;

    public function __construct(
        private TagService $tagService,
        private NormalizerInterface $normalizer,
        private ResponseBuilder $responseBuilder,
        RequestStack $requestStack,
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }

    #[Operation(properties: ['operationId' => 'ListTags'])]
    #[OA\Tag(properties: ['name' => 'Tag'])]
    #[Response(response: 200, description: 'List tags')]
    #[Route(path: '/tag', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $plainPassword = 'longpass';
        $encoded = password_hash($plainPassword, PASSWORD_BCRYPT);
        error_log($encoded);
        $entities = $this->tagService->getAll();
        $data = $this->normalizer->normalize($entities, JsonEncoder::FORMAT, [
            'groups' => ['id', 'tag_list'],
        ]);

        return $this->responseBuilder->buildSuccess($data);
    }

    #[Operation(properties: ['operationId' => 'ShowTag'])]
    #[OA\Tag(properties: ['name' => 'Tag'])]
    #[Response(response: 200, description: 'Show tag')]
    #[Response(response: 404, description: 'Tag not found')]
    #[Route(path: '/tag/{id}', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $entity = $this->tagService->getById($id);
        } catch (EntityNotFoundException $e) {
            return $this->responseBuilder->buildNotFound($e->getMessage());
        }

        $data = $this->normalizer->normalize($entity, JsonEncoder::FORMAT, [
            'groups' => ['id', 'tag_show'],
        ]);

        return $this->responseBuilder->buildSuccess($data);
    }

    #[Operation(properties: ['operationId' => 'CreateTag'])]
    #[OA\Tag(properties: ['name' => 'Tag'])]
    #[Response(response: 201, description: 'Create tag')]
    #[Response(response: 400, description: 'Invalid input data')]
    #[Route(path: '/tag', methods: ['POST'])]
    public function create(): JsonResponse
    {
        $rawData = $this->request->request->all();

        try {
            $entity = $this->tagService->create($rawData);
            $data = $this->normalizer->normalize($entity, JsonEncoder::FORMAT, [
                'groups' => ['id', 'tag_create'],
            ]);
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
}
