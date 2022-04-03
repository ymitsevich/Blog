<?php

namespace App\Service;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityNotFoundException;
use LogicException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TagService
{
    public function __construct(
        private TagRepository $repository,
        private ValidatorInterface $validator,
        private DenormalizerInterface $denormalizer,
    ) {
    }

    public function getAll(): Collection
    {
        return new ArrayCollection($this->repository->findAll());
    }

    /**
     * @throws EntityNotFoundException
     */
    public function getById(int $id): Tag
    {
        $entity = $this->repository->find($id);

        if (!$entity) {
            throw new EntityNotFoundException("Tag is not found [$id].");
        }

        return $entity;
    }

    /**
     * @throws ValidationFailedException
     */
    public function create(array $rawData): Tag
    {
        /** @var Tag $entity */
        try {
            $entity = $this->denormalizer->denormalize(
                $rawData,
                Tag::class,
                null,
                [
                    AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
                    'groups' => [
                        'tag_create',
                    ],
                ]
            );
        } catch (ExceptionInterface $e) {
            error_log($e->getMessage());
            throw new LogicException('Can not create a tag.');
        }

        $violations = $this->validator->validate($entity);
        if (count($violations)) {
            throw new ValidationFailedException(null, $violations);
        }

        $this->repository->add($entity);

        return $entity;
    }
}
