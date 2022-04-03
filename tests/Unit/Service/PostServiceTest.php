<?php

namespace App\Tests\Unit\Service;

use App\Entity\Post;
use App\Entity\User;
use App\Exception\UnauthorizedException;
use App\Normalizer\ReferenceEntityDenormalizer;
use App\Repository\PostRepository;
use App\Service\Auth;
use App\Service\PostService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityNotFoundException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PostServiceTest extends TestCase
{
    use ProphecyTrait;

    private PostRepository|ObjectProphecy $repository;
    private ValidatorInterface|ObjectProphecy $validator;
    private DenormalizerInterface|ObjectProphecy $denormalizer;
    private Auth|ObjectProphecy $auth;

    private PostService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->prophesize(PostRepository::class);
        $this->validator = $this->prophesize(ValidatorInterface::class);
        $this->denormalizer = $this->prophesize(DenormalizerInterface::class);
        $this->auth = $this->prophesize(Auth::class);

        $this->service = new PostService(
            $this->repository->reveal(),
            $this->validator->reveal(),
            $this->denormalizer->reveal(),
            $this->auth->reveal(),
        );
    }

    public function testGetAll_list_collection()
    {
        $entity1 = new Post();
        $entity2 = new Post();
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
        $user = new User();

        $rawData = ['title' => 'blah blah...', 'content' => 'blah blah...'];
        $referencedEntity = new Post();

        $this->denormalizer->denormalize(
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
        )->shouldBeCalledOnce()
            ->willReturn($referencedEntity);

        $this->auth->getCurrentUser()->shouldBeCalledOnce()
            ->willReturn($user);

        $referencedEntity->setCreatedBy($user);

        $violations = new ConstraintViolationList();
        $this->validator->validate($referencedEntity)->shouldBeCalledOnce()
            ->willReturn($violations);

        $this->repository->add($referencedEntity)->shouldBeCalledOnce();

        $assertingEntity = $this->service->create($rawData);
        $this->assertEquals($referencedEntity, $assertingEntity);
    }

    public function testCreate_inValidData_exception()
    {
        $user = new User();

        $rawData = ['title' => 'blah blah...', 'content' => 'blah blah...'];
        $referencedEntity = new Post();

        $this->denormalizer->denormalize(
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
        )->shouldBeCalledOnce()
            ->willReturn($referencedEntity);

        $this->auth->getCurrentUser()->shouldBeCalledOnce()
            ->willReturn($user);

        $referencedEntity->setCreatedBy($user);

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

    public function testGetById_id_entity()
    {
        $id = 11;
        $referencedEntity = new Post();
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

    public function testUpdate_validData_entity()
    {
        $user = new User();
        $id = 77;
        $rawData = ['title' => 'blah blah...', 'content' => 'blah blah...'];
        $referencedEntity = new Post();

        $this->repository->find($id)->shouldBeCalledOnce()
            ->willReturn($referencedEntity);
        $this->auth->validateAuthor($referencedEntity)->shouldBeCalledOnce();

        $this->denormalizer->denormalize(
            $rawData,
            Post::class,
            null,
            [
                AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
                AbstractNormalizer::OBJECT_TO_POPULATE => $referencedEntity,
                'groups' => [
                    'post_create',
                ],
                ReferenceEntityDenormalizer::REFERENCE_ENTITIES_BY_ID => true,
            ]
        )->shouldBeCalledOnce()
            ->willReturn($referencedEntity);

        $referencedEntity->setCreatedBy($user);

        $violations = new ConstraintViolationList();
        $this->validator->validate($referencedEntity)->shouldBeCalledOnce()
            ->willReturn($violations);

        $this->repository->add($referencedEntity)->shouldBeCalledOnce();

        $assertingEntity = $this->service->update($rawData, $id);
        $this->assertEquals($referencedEntity, $assertingEntity);
    }

    public function testUpdate_inValidData_exception()
    {
        $user = new User();
        $id = 77;
        $rawData = ['title' => 'blah blah...', 'content' => 'blah blah...'];
        $referencedEntity = new Post();

        $this->repository->find($id)->shouldBeCalledOnce()
            ->willReturn($referencedEntity);
        $this->auth->validateAuthor($referencedEntity)->shouldBeCalledOnce();

        $this->denormalizer->denormalize(
            $rawData,
            Post::class,
            null,
            [
                AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
                AbstractNormalizer::OBJECT_TO_POPULATE => $referencedEntity,
                'groups' => [
                    'post_create',
                ],
                ReferenceEntityDenormalizer::REFERENCE_ENTITIES_BY_ID => true,
            ]
        )->shouldBeCalledOnce()
            ->willReturn($referencedEntity);

        $referencedEntity->setCreatedBy($user);

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

        $this->repository->add(Argument::cetera())->shouldNotBeCalled();

        $this->expectException(ValidationFailedException::class);
        $this->service->update($rawData, $id);
    }

    public function testUpdate_foreignPost_exception()
    {
        $id = 77;
        $rawData = ['title' => 'blah blah...', 'content' => 'blah blah...'];
        $referencedEntity = new Post();

        $this->repository->find($id)->shouldBeCalledOnce()
            ->willReturn($referencedEntity);
        $this->auth->validateAuthor($referencedEntity)->willThrow(UnauthorizedException::class);

        $this->denormalizer->denormalize(Argument::cetera())->shouldNotBeCalled();
        $this->validator->validate(Argument::cetera())->shouldNotBeCalled();
        $this->repository->add(Argument::cetera())->shouldNotBeCalled();

        $this->expectException(UnauthorizedException::class);
        $this->service->update($rawData, $id);
    }

    public function testUpdate_postNotFound_exception()
    {
        $id = 77;
        $rawData = ['title' => 'blah blah...', 'content' => 'blah blah...'];

        $this->repository->find($id)->shouldBeCalledOnce()
            ->willReturn(null);
        $this->auth->validateAuthor(Argument::cetera())->shouldNotBeCalled();
        $this->denormalizer->denormalize(Argument::cetera())->shouldNotBeCalled();
        $this->validator->validate(Argument::cetera())->shouldNotBeCalled();
        $this->repository->add(Argument::cetera())->shouldNotBeCalled();

        $this->expectException(EntityNotFoundException::class);
        $this->service->update($rawData, $id);
    }

    public function testDelete_id_void()
    {
        $id = 11;
        $entity = new Post();

        $this->repository->find($id)
            ->shouldBeCalledOnce()
            ->willReturn($entity);

        $this->auth->validateAuthor($entity)->shouldBeCalledOnce();
        $this->repository->remove($entity)->shouldBeCalledOnce();

        $this->service->delete($id);
    }

    public function testDelete_wrongId_exception()
    {
        $id = 11;
        $this->repository->find($id)
            ->shouldBeCalledOnce()
            ->willReturn(null);

        $this->auth->validateAuthor(Argument::cetera())->shouldNotBeCalled();
        $this->repository->remove(Argument::cetera())->shouldNotBeCalled();

        $this->expectException(EntityNotFoundException::class);
        $this->service->delete($id);
    }

    public function testDelete_foreignEntity_exception()
    {
        $id = 11;
        $entity = new Post();

        $this->repository->find($id)
            ->shouldBeCalledOnce()
            ->willReturn($entity);

        $this->auth->validateAuthor($entity)->shouldBeCalledOnce()->willThrow(UnauthorizedException::class);
        $this->repository->remove(Argument::cetera())->shouldNotBeCalled();

        $this->expectException(UnauthorizedException::class);
        $this->service->delete($id);
    }
}
