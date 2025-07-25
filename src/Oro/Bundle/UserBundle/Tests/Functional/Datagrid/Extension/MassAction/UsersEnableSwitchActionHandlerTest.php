<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResult;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Ajax\AjaxMassAction;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;

class UsersEnableSwitchActionHandlerTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures(
            ['@OroUserBundle/Tests/Functional/DataFixtures/Alice/users_enable_switch_action_handler_users.yml']
        );
    }

    public function testHandle()
    {
        $userReference = 'user.1';
        $this->updateUserSecurityToken($this->getReference($userReference)->getEmail());
        $userRepository = $this->getUserRepo();
        $query = $userRepository->createQueryBuilder('u')->getQuery();//select all
        $resultIterator = new IterableResult($query);
        $handler = self::getContainer()->get('oro_datagrid.mass_action.users_enable_switch.handler.disable');
        /** @var DatagridInterface $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);

        $response = $handler->handle(new MassActionHandlerArgs(new AjaxMassAction(), $datagrid, $resultIterator, []));

        $users = $userRepository->findAll();
        /** @var User $currentUser */
        $currentUser = $this->getReference($userReference);

        /** @var User $user */
        foreach ($users as $user) {
            // Admin user should not processed because he was created at another organization.
            if ($user->getId() !== $currentUser->getId() && $user->getUserIdentifier() !== 'admin') {
                self::assertFalse($user->isEnabled());
            }
        }

        $all = $userRepository->createQueryBuilder('u')
            ->select('COUNT(u)')
            ->getQuery()
            ->getSingleScalarResult();
        $expectedMessage = sprintf('%s user(s) were disabled', $all - 2/* except current and admin*/);
        self::assertEquals($expectedMessage, $response->getMessage());
    }

    /**
     * @return UserRepository
     */
    private function getUserRepo()
    {
        return self::getContainer()->get('doctrine')->getRepository(User::class);
    }
}
