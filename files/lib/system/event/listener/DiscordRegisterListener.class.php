<?php

namespace wcf\system\event\listener;

use wcf\data\user\avatar\UserAvatarAction;
use wcf\data\user\User;
use wcf\data\user\UserEditor;
use wcf\system\WCF;

/**
 * @author      Marco Daries
 * @copyright   2018-2021 Daries.info
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package     WoltLabSuite\Core\Action
 */
class DiscordRegisterListener implements IParameterizedEventListener
{

    protected string $avatarURL = '';

    /**
     * @inheritDoc
     */
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        if ($eventName == 'registerVia3rdParty') {
            if ($eventObj->isExternalAuthentication) {
                switch (WCF::getSession()->getVar('__3rdPartyProvider')) {
                    case 'discord':
                        // Discord
                        if (($oauthUser = WCF::getSession()->getVar('__oauthUser'))) {
                            $eventObj->additionalFields['authData'] = 'discord:' . $oauthUser->getId();
                        }

                        $this->avatarURL = isset($oauthUser->avatar) ? 'https://cdn.discordapp.com/avatars/' . $oauthUser->getId() . '/' . $oauthUser->avatar . '.png' : '';
                        break;
                }
            }
        }

        if ($eventName == 'saved') {
            // set avatar if provided
            if (!empty($this->avatarURL)) {
                $result = $eventObj->objectAction->getReturnValues();
                /** @var User $user */
                $user = $result['returnValues'];
                $userEditor = new UserEditor($user);

                $userAvatarAction = new UserAvatarAction([], 'fetchRemoteAvatar', [
                    'url' => $this->avatarURL,
                    'userEditor' => $userEditor
                ]);
                $userAvatarAction->executeAction();
            }
        }
    }

}