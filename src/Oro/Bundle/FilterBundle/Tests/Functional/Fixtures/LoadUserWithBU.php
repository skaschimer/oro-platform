<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\Entity\Email;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;

class LoadUserWithBU extends AbstractFixture
{
    /** @var array */
    protected static $users = [
        [
            'username' => 'u1',
            'email' => 'u1@example.com',
            'password' => 'u1',
            'additional_email' => 'test1@example.com',
            'business_unit' => true
        ],
        [
            'username' => 'u2',
            'email' => 'u2@example.com',
            'password' => 'u2',
            'additional_email' => 'test2@example.com',
            'business_unit' => true
        ],
        [
            'username' => 'u3',
            'email' => 'u3@example.com',
            'password' => 'u3',
            'additional_email' => 'test3@example.com',
            'business_unit' => false
        ],
    ];

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $businessUnit = $this->getBusinessUnit($manager);

        foreach (self::$users as $data) {
            $email = new Email();
            $email->setEmail($data['additional_email']);

            $user = new User();
            $user->setUsername($data['username'])
                ->setEmail($data['email'])
                ->setPassword($data['password'])
                ->addEmail($email);

            if ($data['business_unit']) {
                $user->addBusinessUnit($businessUnit);
            }

            $manager->persist($email);
            $manager->persist($user);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     *
     * @return BusinessUnit
     */
    private function getBusinessUnit(ObjectManager $manager)
    {
        $mainBusinessUnit = $manager
            ->getRepository('OroOrganizationBundle:BusinessUnit')
            ->getFirst();

        $this->setReference('mainBusinessUnit', $mainBusinessUnit);

        return $mainBusinessUnit;
    }
}
