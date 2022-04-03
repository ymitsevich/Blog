<?php

namespace App\Tests\Unit\Service;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\Tag;
use App\Entity\User;
use App\Repository\CommentRepository;
use App\Repository\TagRepository;
use App\Service\Auth;
use App\Service\CommentService;
use App\Service\TagService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityNotFoundException;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TagServiceTest extends TestCase
{
    use ProphecyTrait;

    private TagRepository|ObjectProphecy $repository;
    private ValidatorInterface|ObjectProphecy $validator;
    private DenormalizerInterface|ObjectProphecy $denormalizer;

    private TagService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->prophesize(TagRepository::class);
        $this->validator = $this->prophesize(ValidatorInterface::class);
        $this->denormalizer = $this->prophesize(DenormalizerInterface::class);

        $this->service = new TagService(
            $this->repository->reveal(),
            $this->validator->reveal(),
            $this->denormalizer->reveal(),
        );
    }

    public function testGetById_id_entity()
    {
        $id = 11;
        $referencedEntity = new Tag();
        $this->repository->find($id)->shouldBeCalledOnce()->willReturn($referencedEntity);

        $assertingEntity = $this->service->getById($id);
        $this->assertEquals($referencedEntity, $assertingEntity);
    }

    public function testGetById_noEntity_exception()
    {
        $id = 11;
        $this->repository->find($id)->shouldBeCalledOnce()->willReturn(null);

        $this->expectException(EntityNotFoundException::class);
        $this->service->getById($id);
    }

    public function testGetAll_list_collection()
    {
        $entity1 = new Tag();
        $entity2 = new Tag();
        $referencedArray = [$entity1, $entity2];
        $referenceCollection = new ArrayCollection($referencedArray);

        $this->repository->findAll()
            ->shouldBeCalledOnce()
            ->willReturn($referencedArray);

        $assertingCollection = $this->service->getAll();
        $this->assertEquals($referenceCollection, $assertingCollection);
    }

    public function testCreate_validData_entity()
    {
        $rawData = ['name' => 'blah blah...'];
        $referencedEntity = new Tag();

        $this->denormalizer->denormalize(
            $rawData,
            Tag::class,
            null,
            [
                AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
                'groups' => [
                    'tag_create',
                ],
            ]
        )->shouldBeCalledOnce()
            ->willReturn($referencedEntity);

        $violations = new ConstraintViolationList();
        $this->validator->validate($referencedEntity)->shouldBeCalledOnce()
            ->willReturn($violations);

        $this->repository->add($referencedEntity)->shouldBeCalledOnce();

        $assertingEntity = $this->service->create($rawData);
        $this->assertEquals($referencedEntity, $assertingEntity);
    }

    public function testCreate_inValidData_exception()
    {
        $rawData = ['content' => 'blah blah...'];
        $referencedEntity = new Tag();

        $this->denormalizer->denormalize(
            $rawData,
            Tag::class,
            null,
            [
                AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
                'groups' => [
                    'tag_create',
                ],
            ]
        )->shouldBeCalledOnce()
            ->willReturn($referencedEntity);


        $violations = new ConstraintViolationList([
            new ConstraintViolation(
                'wrong blah',
                null,
                [],
                null,
                null,
                null,
            ),
        ]);
        $this->validator->validate($referencedEntity)->shouldBeCalledOnce()
            ->willReturn($violations);

        $this->repository->add($referencedEntity)->shouldNotBeCalled();

        $this->expectException(ValidationFailedException::class);
        $this->service->create($rawData);
    }
}
