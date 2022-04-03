<?php

namespace App\Service;

use App\Entity\Comment;
use App\Entity\Post;
use App\Repository\CommentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityNotFoundException;
use LogicException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CommentService
{
    public function __construct(
        private CommentRepository $repository,
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
    public function getById(int $id): Comment
    {
        $entity = $this->repository->find($id);

        if (!$entity) {
            throw new EntityNotFoundException("Comment is not found [$id].");
        }

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

    /**
     * @throws ValidationFailedException
     */
    public function create(array $rawData, Post $post): Comment
    {
        /** @var Comment $entity */
        try {
            $entity = $this->denormalizer->denormalize(
                $rawData,
                Comment::class,
                null,
                [
                    AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
                    'groups' => [
                        'comment_create',
                    ],
                ]
            );
        } catch (ExceptionInterface $e) {
            error_log($e->getMessage());
            throw new LogicException('Can not create the comment.');
        }
        $entity->setCreatedBy($this->auth->getCurrentUser());
        $entity->setPost($post);

        $violations = $this->validator->validate($entity);
        if (count($violations)) {
            throw new ValidationFailedException(null, $violations);
        }

        $this->repository->add($entity);

        return $entity;
    }
}
