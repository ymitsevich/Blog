<?php

namespace App\Service;

use App\Entity\Post;
use App\Normalizer\ReferenceEntityDenormalizer;
use App\Repository\PostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityNotFoundException;
use LogicException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PostService
{
    public function __construct(
        private PostRepository $repository,
        private ValidatorInterface $validator,
        private DenormalizerInterface $denormalizer,
        private Auth $auth,
    ) {
    }

    public function getAll(): Collection
    {
        return new ArrayCollection($this->repository->findAll());
    }

    /**
     * @throws EntityNotFoundException
     */
    public function getById(int $id): Post
    {
        $entity = $this->repository->find($id);

        if (!$entity) {
            throw new EntityNotFoundException("Post is not found [$id].");
        }

        return $entity;
    }

    /**
     * @throws ValidationFailedException
     */
    public function create(array $rawData): Post
    {
        /** @var Post $entity */
        try {
            $entity = $this->denormalizer->denormalize(
                $rawData,
                Post::class,
                null,
                [
                    AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
                    'groups' => [
                        'post_create',
                    ],
                    ReferenceEntityDenormalizer::REFERENCE_ENTITIES_BY_ID => true,
                ]
            );
        } catch (ExceptionInterface $e) {
            error_log($e->getMessage());
            throw new LogicException('Can not create the post.');
        }
        $entity->setCreatedBy($this->auth->getCurrentUser());

        $violations = $this->validator->validate($entity);
        if (count($violations)) {
            throw new ValidationFailedException(null, $violations);
        }

        $this->repository->add($entity);

        return $entity;
    }

    /**
     * @throws ValidationFailedException
     * @throws EntityNotFoundException
     */
    public function update(array $rawData, int $id): Post
    {
        $entity = $this->getById($id);
        $this->auth->validateAuthor($entity);

        /** @var Post $entity */
        try {
            $entity = $this->denormalizer->denormalize(
                $rawData,
                Post::class,
                null,
                [
                    AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
                    AbstractNormalizer::OBJECT_TO_POPULATE => $entity,
                    'groups' => [
                        'post_create',
                    ],
                    ReferenceEntityDenormalizer::REFERENCE_ENTITIES_BY_ID => true,
                ]
            );
        } catch (ExceptionInterface $e) {
            error_log($e->getMessage());
            throw new LogicException('Can not update the post.');
        }

        $violations = $this->validator->validate($entity);
        if (count($violations)) {
            throw new ValidationFailedException(null, $violations);
        }

        $this->repository->add($entity);

        return $entity;
    }

    /**
     * @throws EntityNotFoundException
     */
    public function delete(int $id): void
    {
        $entity = $this->getById($id);
        $this->auth->validateAuthor($entity);
        $this->repository->remove($entity);
    }
}
