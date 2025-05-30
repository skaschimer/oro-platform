<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationGmailType;
use Oro\Bundle\ImapBundle\Manager\ImapEmailGoogleOAuthManager;
use Oro\Bundle\ImapBundle\Provider\OAuthAccessTokenData;
use Oro\Bundle\ImapBundle\Provider\OAuthProviderInterface;
use Oro\Bundle\ImapBundle\Tests\Unit\Stub\TestUserEmailOrigin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImapEmailGoogleOAuthManagerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private OAuthProviderInterface&MockObject $oauthProvider;
    private ConfigManager&MockObject $configManager;
    private ImapEmailGoogleOAuthManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->oauthProvider = $this->createMock(OAuthProviderInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->manager = new ImapEmailGoogleOAuthManager(
            $this->doctrine,
            $this->oauthProvider,
            $this->configManager
        );
    }

    private function getDateObject(bool $isExpired): \DateTime
    {
        $date = new \DateTime();
        $multiplier = $isExpired ? -1 : 1;
        $timestamp = time() + (1000 * $multiplier);
        $date->setTimestamp($timestamp);

        return $date;
    }

    private function getExpirationOrigin(bool $isExpired): UserEmailOrigin
    {
        $userEmailOrigin = $this->createMock(UserEmailOrigin::class);
        $userEmailOrigin->expects($this->once())
            ->method('getAccessTokenExpiresAt')
            ->willReturnCallback(function () use ($isExpired) {
                return $this->getDateObject($isExpired);
            });

        return $userEmailOrigin;
    }

    public function testGetType(): void
    {
        $this->assertEquals('gmail', $this->manager->getType());
    }

    public function testGetConnectionFormTypeClass(): void
    {
        $this->assertEquals(ConfigurationGmailType::class, $this->manager->getConnectionFormTypeClass());
    }

    public function testSetOriginDefaults(): void
    {
        $userEmailOrigin = new UserEmailOrigin();
        $this->manager->setOriginDefaults($userEmailOrigin);

        self::assertEquals('imap.gmail.com', $userEmailOrigin->getImapHost());
        self::assertEquals('993', $userEmailOrigin->getImapPort());
        self::assertEquals('ssl', $userEmailOrigin->getImapEncryption());
        self::assertEquals('smtp.gmail.com', $userEmailOrigin->getSmtpHost());
        self::assertEquals('465', $userEmailOrigin->getSmtpPort());
        self::assertEquals('ssl', $userEmailOrigin->getSmtpEncryption());
        self::assertEquals('gmail', $userEmailOrigin->getAccountType());
    }

    public function testIsOAuthEnabled(): void
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_imap.enable_google_imap')
            ->willReturn(1);

        $this->assertTrue($this->manager->isOAuthEnabled());
    }

    public function testIsAccessTokenExpired(): void
    {
        $this->assertTrue($this->manager->isAccessTokenExpired($this->getExpirationOrigin(true)));
        $this->assertFalse($this->manager->isAccessTokenExpired($this->getExpirationOrigin(false)));
    }

    /**
     * @dataProvider getExpirationCheckData
     */
    public function testGetAccessTokenWithCheckingExpiration(
        UserEmailOrigin $origin,
        bool $isConfigEnabled,
        ?string $currentToken
    ): void {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $isExpired = $now > $origin->getAccessTokenExpiresAt();

        if ($isExpired) {
            $this->configManager->expects($this->once())
                ->method('get')
                ->with('oro_imap.enable_google_imap')
                ->willReturn((int)$isConfigEnabled);
        } else {
            $this->configManager->expects($this->never())
                ->method('get');
        }

        if ($isConfigEnabled && $isExpired) {
            $accessTokenData = new OAuthAccessTokenData('sampleAccessToken', 'sampleRefreshToken', 3600);
            $this->oauthProvider->expects($this->once())
                ->method('getAccessTokenByRefreshToken')
                ->with($origin->getRefreshToken())
                ->willReturn($accessTokenData);

            $em = $this->createMock(EntityManagerInterface::class);
            $em->expects($this->once())
                ->method('persist')
                ->with($origin);
            $em->expects($this->once())
                ->method('flush')
                ->with($origin);

            $this->doctrine->expects($this->once())
                ->method('getManagerForClass')
                ->willReturn($em);

            $resultToken = $this->manager->getAccessTokenWithCheckingExpiration($origin);
            $this->assertEquals('sampleAccessToken', $resultToken);
        } else {
            $this->oauthProvider->expects($this->never())
                ->method('getAccessTokenByRefreshToken');
            $this->doctrine->expects($this->never())
                ->method('getManagerForClass');

            $resultToken = $this->manager->getAccessTokenWithCheckingExpiration($origin);
            $this->assertEquals($currentToken, $resultToken);
        }
    }

    public function getExpirationCheckData(): array
    {
        $expiredOriginNoToken1 = new TestUserEmailOrigin();
        $expiredOriginNoToken1->setAccessTokenExpiresAt($this->getDateObject(true));
        $expiredOriginNoToken1->setRefreshToken('sampleRefreshToken');

        $expiredOriginNoToken2 = new TestUserEmailOrigin();
        $expiredOriginNoToken2->setAccessTokenExpiresAt($this->getDateObject(true));

        $notExpiredOriginToken = new TestUserEmailOrigin();
        $notExpiredOriginToken->setAccessTokenExpiresAt($this->getDateObject(false));
        $notExpiredOriginToken->setAccessToken('sampleTokenResult');

        return [
            'expiredOriginNoTokenEnabled'  => [
                $expiredOriginNoToken1,
                true,
                null
            ],
            'expiredOriginNoTokenDisabled' => [
                $expiredOriginNoToken2,
                false,
                null
            ],
            'nonexpiredOriginTokenEnabled' => [
                $notExpiredOriginToken,
                true,
                'sampleTokenResult'
            ]
        ];
    }
}
