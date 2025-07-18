<?php

namespace Oro\Bundle\WindowsBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WindowsBundle\Entity\Repository\WindowsStateRepository;
use Oro\Bundle\WindowsBundle\Entity\WindowsState;
use Oro\Bundle\WindowsBundle\Manager\WindowsStateManager;
use Oro\Bundle\WindowsBundle\Manager\WindowsStateRequestManager;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class WindowsStateManagerTest extends TestCase
{
    use EntityTrait;

    private TokenStorageInterface&MockObject $tokenStorage;
    private EntityManager&MockObject $em;
    private WindowsStateRepository&MockObject $repo;
    private ClassMetadata&MockObject $classMetadata;
    private WindowsStateRequestManager&MockObject $requestStateManager;
    private WindowsStateManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->em = $this->createMock(EntityManager::class);
        $this->repo = $this->createMock(WindowsStateRepository::class);
        $this->classMetadata = $this->createMock(ClassMetadata::class);
        $this->requestStateManager = $this->createMock(WindowsStateRequestManager::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(WindowsState::class)
            ->willReturn($this->em);
        $this->em->expects($this->any())
            ->method('getRepository')
            ->with(WindowsState::class)
            ->willReturn($this->repo);
        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->with(WindowsState::class)
            ->willReturn($this->classMetadata);

        $this->manager = new WindowsStateManager(
            $this->tokenStorage,
            $doctrine,
            $this->requestStateManager,
            WindowsState::class
        );
    }

    private function createWindowState(array $data = [], ?int $id = 123): WindowsState
    {
        $state = $this->getEntity(WindowsState::class, ['id' => $id]);
        $state->setData($data);

        return $state;
    }

    public function testCreateWindowsState(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn(new User());
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $state = new WindowsState();
        $this->classMetadata->expects($this->once())
            ->method('newInstance')
            ->willReturn($state);

        $this->em->expects($this->once())
            ->method('persist')
            ->with($state);
        $this->em->expects($this->once())
            ->method('flush')
            ->with($state);

        $this->requestStateManager->expects($this->once())
            ->method('getData')
            ->willReturn([]);

        $this->manager->createWindowsState();
    }

    public function testUpdateWindowState(): void
    {
        $windowId = 42;
        $user = new User();
        $data = ['url' => '/test'];

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->requestStateManager->expects($this->once())
            ->method('getData')
            ->willReturn($data);

        $this->repo->expects($this->once())
            ->method('update')
            ->with($user, $windowId, $data);

        $this->manager->updateWindowsState($windowId);
    }

    public function testDeleteWindowsState(): void
    {
        $user = new User();
        $windowId = 42;

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->repo->expects($this->once())
            ->method('delete')
            ->with($user, $windowId);

        $this->requestStateManager->expects($this->never())
            ->method($this->anything());

        $this->manager->deleteWindowsState($windowId);
    }

    public function testGetWindowsStates(): void
    {
        $user = new User();

        $windowStateFoo = $this->createWindowState(['cleanUrl' => 'foo']);
        $windowStateBar = $this->createWindowState(['cleanUrl' => 'foo']);

        $windowStates = [$windowStateFoo, $windowStateBar];

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['user' => $user])
            ->willReturn($windowStates);

        $this->requestStateManager->expects($this->never())
            ->method($this->anything());

        $this->assertSame($windowStates, $this->manager->getWindowsStates());
    }

    public function testGetWindowsState(): void
    {
        $user = new User();
        $windowStateId = 42;

        $windowState = $this->createWindowState(['cleanUrl' => 'foo']);

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['user' => $user, 'id' => $windowStateId])
            ->willReturn($windowState);

        $this->requestStateManager->expects($this->never())
            ->method($this->anything());

        $this->assertSame($windowState, $this->manager->getWindowsState($windowStateId));
    }

    public function testFilterId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Wrong $windowId type');

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn(new User());
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->manager->getWindowsState('bbb');
    }

    public function testUserEmptyToken(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(null);

        $this->manager->getWindowsState(42);
    }

    public function testUserEmptyUser(): void
    {
        $this->expectException(AccessDeniedException::class);

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn(null);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->manager->getWindowsState(42);
    }
}
