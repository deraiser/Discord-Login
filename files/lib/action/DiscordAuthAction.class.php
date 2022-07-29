<?php

namespace wcf\action;

use GuzzleHttp\Psr7\Request;
use wcf\data\user\User;
use wcf\form\RegisterForm;
use wcf\system\exception\NamedUserException;
use wcf\system\request\LinkHandler;
use wcf\system\user\authentication\oauth\User as OauthUser;
use wcf\system\WCF;
use wcf\util\HeaderUtil;
use wcf\util\JSON;
use wcf\util\StringUtil;

/**
 * Performs authentication against Discord.com
 *
 * @author      Marco Daries
 * @copyright   2018-2021 Daries.info
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package     WoltLabSuite\Core\System\Event\Listener
 */
class DiscordAuthAction extends AbstractOauth2Action
{

    /**
     * @inheritDoc
     */
    public $neededModules = ['DISCORD_CLIENT_ID', 'DISCORD_CLIENT_SECRET'];

    /**
     * @inheritDoc
     */
    protected function getAuthorizeUrl(): string
    {
        return 'https://discord.com/api/oauth2/authorize';
    }

    /**
     * @inheritDoc
     */
    protected function getCallbackUrl(): string
    {
        return LinkHandler::getInstance()->getControllerLink(self::class);
    }

    /**
     * @inheritDoc
     */
    protected function getClientId(): string
    {
        return StringUtil::trim(DISCORD_CLIENT_ID);
    }

    /**
     * @inheritDoc
     */
    protected function getClientSecret(): string
    {
        return StringUtil::trim(DISCORD_CLIENT_SECRET);
    }

    /**
     * @inheritDoc
     */
    protected function getScope(): string
    {
        return 'identify email';
    }

    /**
     * @inheritDoc
     */
    protected function getTokenEndpoint(): string
    {
        return 'https://discord.com/api/oauth2/token';
    }

    /**
     * @inheritDoc
     */
    protected function getUser(array $accessToken): OauthUser
    {
        $request = new Request('GET', 'https://discord.com/api/users/@me', [
            'accept' => 'application/json',
            'authorization' => \sprintf('Bearer %s', $accessToken['access_token']),
        ]);
        $response = $this->getHttpClient()->send($request);
        $parsed = JSON::decode((string) $response->getBody());

        $parsed['__id'] = $parsed['id'];
        $parsed['__username'] = $parsed['username'] . '#' . $parsed['discriminator'];
        $parsed['__email'] = $parsed['email'] ?? null;
        $parsed['accessToken'] = $accessToken;

        return new OauthUser($parsed);
    }

    /**
     * @inheritDoc
     */
    protected function processUser(OauthUser $oauthUser)
    {
        $user = User::getUserByAuthData('discord:' . $oauthUser->getId());

        if ($user->userID) {
            if (WCF::getUser()->userID) {
                // This account belongs to an existing user, but we are already logged in.
                // This can't be handled.

                throw new NamedUserException(
                        WCF::getLanguage()->getDynamicVariable('wcf.user.3rdparty.discord.connect.error.inuse')
                );
            } else {
                // This account belongs to an existing user, we are not logged in.
                // Perform the login.

                WCF::getSession()->changeUser($user);
                WCF::getSession()->update();
                HeaderUtil::redirect(LinkHandler::getInstance()->getLink());
                exit;
            }
        } else {
            WCF::getSession()->register('__3rdPartyProvider', 'discord');

            if (WCF::getUser()->userID) {
                // This account does not belong to anyone and we are already logged in.
                // Thus we want to connect this account.

                WCF::getSession()->register('__oauthUser', $oauthUser);

                HeaderUtil::redirect(LinkHandler::getInstance()->getLink('AccountManagement') . '#3rdParty');
                exit;
            } else {
                // This account does not belong to anyone and we are not logged in.
                // Thus we want to connect this account to a newly registered user.
                WCF::getSession()->register('__oauthUser', $oauthUser);
                WCF::getSession()->register('__username', $oauthUser->getUsername());
                WCF::getSession()->register('__email', $oauthUser->getEmail());

                // We assume that bots won't register an external account first, so
                // we skip the captcha.
                WCF::getSession()->register('noRegistrationCaptcha', true);

                WCF::getSession()->update();
                HeaderUtil::redirect(LinkHandler::getInstance()->getControllerLink(RegisterForm::class));
                exit;
            }
        }
    }

    /**
     * @inheritDoc
     */
    protected function supportsState(): bool
    {
        return false;
    }

}