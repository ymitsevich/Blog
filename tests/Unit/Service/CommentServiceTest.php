<?php

namespace App\Tests\Unit\Service;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
use App\Exception\UnauthorizedException;
use App\Repository\CommentRepository;
use App\Service\Auth;
use App\Service\CommentService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityNotFoundException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CommentServiceTest extends TestCase
{
    use ProphecyTrait;

    private CommentRepository|ObjectProphecy $repository;
    private ValidatorInterface|ObjectProphecy $validator;
    private DenormalizerInterface|ObjectProphecy $denormalizer;
    private Auth|ObjectProphecy $auth;

    private CommentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->prophesize(CommentRepository::class);
        $this->validator = $this->prophesize(ValidatorInterface::class);
        $this->denormalizer = $this->prophesize(DenormalizerInterface::class);
        $this->auth = $this->prophesize(Auth::class);

        $this->service = new CommentService(
            $this->repository->reveal(),
            $this->validator->reveal(),
            $this->denormalizer->reveal(),
            $this->auth->reveal(),
        );
    }

    public function testGetAll_list_collection()
    {
        $comment1 = new Comment();
        $comment2 = new Comment();
        $referencedArray = [$comment1, $comment2];
        $referenceCollection = new ArrayCollection($referencedArray);

        $this->repository->findAll()
            ->shouldBeCalledOnce()
            ->willReturn($referencedArray);

        $assertingCollection = $this->service->getAll();
        $this->assertEquals($referenceCollection, $assertingCollection);
    }

    public function testDelete_id_void()
    {
        $id = 11;
        $entity = new Comment();

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
        $entity = new Comment();

        $this->repository->find($id)
            ->shouldBeCalledOnce()
            ->willReturn($entity);

        $this->auth->validateAuthor($entity)->shouldBeCalledOnce()->willThrow(UnauthorizedException::class);
        $this->repository->remove(Argument::cetera())->shouldNotBeCalled();

        $this->expectException(UnauthorizedException::class);
        $this->service->delete($id);
    }

    public function testCreate_validData_entity()
    {
        $post = new Post();
        $user = new User();

        $rawData = ['content' => 'blah blah...'];
        $referencedEntity = new Comment();

        $this->denormalizer->denormalize(
            $rawData,
            Comment::class,
            null,
            [
                AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
                'groups' => [
                    'comment_create',
                ],
            ]
        )->shouldBeCalledOnce()
            ->willReturn($referencedEntity);

        $this->auth->getCurrentUser()->shouldBeCalledOnce()
            ->willReturn($user);

        $referencedEntity->setCreatedBy($user);
        $referencedEntity->setPost($post);

        $violations = new ConstraintViolationList();
        $this->validator->validate($referencedEntity)->shouldBeCalledOnce()
            ->willReturn($violations);

        $this->repository->add($referencedEntity)->shouldBeCalledOnce();

        $assertingEntity = $this->service->create($rawData, $post);
        $this->assertEquals($referencedEntity, $assertingEntity);
    }

    public function testCreate_inValidData_exception()
    {
        $post = new Post();
        $user = new User();

        $rawData = ['content' => 'blah blah...'];
        $referencedEntity = new Comment();

        $this->denormalizer->denormalize(
            $rawData,
            Comment::class,
            null,
            [
                AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
                'groups' => [
                    'comment_create',
                ],
            ]
        )->shouldBeCalledOnce()
            ->willReturn($referencedEntity);

        $this->auth->getCurrentUser()->shouldBeCalledOnce()
            ->willReturn($user);

        $referencedEntity->setCreatedBy($user);
        $referencedEntity->setPost($post);

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
        $this->service->create($rawData, $post);
    }

    public function testGetById_id_entity()
    {
        $id = 11;
        $referencedEntity = new Comment();
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
}
