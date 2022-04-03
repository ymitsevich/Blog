<?php

namespace App\Tests\Unit\Service;

use App\Entity\Post;
use App\Entity\User;
use App\Exception\UnauthorizedException;
use App\Service\Auth;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;

class AuthTest extends TestCase
{
    use ProphecyTrait;

    private Security|ObjectProphecy $security;
    private RequestStack|ObjectProphecy $requestStack;

    private Auth $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->security = $this->prophesize(Security::class);
        $this->requestStack = $this->prophesize(RequestStack::class);

        $this->service = new Auth($this->security->reveal());
    }

    public function testGetCurrentUser_entity_entity()
    {
        $referencedEntity = new User();
        $this->security->getUser()->shouldBeCalledOnce()->willReturn($referencedEntity);

        $assertingEntity = $this->service->getCurrentUser();

        $this->assertEquals($referencedEntity, $assertingEntity);
    }

    public function testGetCurrentUser_noEntity_exception()
    {
        $this->security->getUser()->shouldBeCalledOnce()->willReturn(null);

        $this->expectException(AuthenticationException::class);
        $this->service->getCurrentUser();
    }

    public function testValidateAuthor_entity_entity()
    {
        $currentUser = new User();
        $entity = (new Post())->setCreatedBy($currentUser);
        $this->security->getUser()->shouldBeCalledOnce()->willReturn($currentUser);

        $this->service->validateAuthor($entity);
    }

    public function testValidateAuthor_foreignUser_exception()
    {
        $currentUser = new User();
        $foreignUser = new User();
        $entity = (new Post())->setCreatedBy($currentUser);
        $this->security->getUser()->shouldBeCalledOnce()->willReturn($foreignUser);

        $this->expectException(UnauthorizedException::class);
        $this->service->validateAuthor($entity);
    }
}
