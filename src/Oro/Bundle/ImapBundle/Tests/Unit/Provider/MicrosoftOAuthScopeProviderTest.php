<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Provider;

use Oro\Bundle\ImapBundle\Provider\MicrosoftOAuthScopeProvider;
use PHPUnit\Framework\TestCase;

class MicrosoftOAuthScopeProviderTest extends TestCase
{
    public function testGetAccessTokenScopes(): void
    {
        $provider = new MicrosoftOAuthScopeProvider();
        self::assertEquals(
            [
                'offline_access',
                'https://outlook.office.com/IMAP.AccessAsUser.All',
                'https://outlook.office.com/POP.AccessAsUser.All',
                'https://outlook.office.com/SMTP.Send'
            ],
            $provider->getAccessTokenScopes('')
        );
    }
}
