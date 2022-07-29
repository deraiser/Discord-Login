<?php

namespace wcf\system\event\listener;

use wcf\data\user\UserAction;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * @author      Marco Daries
 * @copyright   2018-2021 Daries.info
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package     WoltLabSuite\Core\Action
 */
class DiscordAccountManagementListener implements IParameterizedEventListener
{

    /**
     * indicates if the user wants to connect discord
     */
    public int $discordConnect = 0;

    /**
     * indicates if the user wants to disconnect discord
     */
    public int $discordDisconnect = 0;

    /**
     * @inheritDoc
     */
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        if ($eventName == 'assignVariables') {
            WCF::getTPL()->assign([
                'discordConnect' => $this->discordConnect,
                'discordDisconnect' => $this->discordDisconnect,
            ]);
        }

        if ($eventName == 'readFormParameters') {
            if (isset($_POST['discordDisconnect'])) $this->discordDisconnect = \intval($_POST['discordDisconnect']);

            if (!WCF::getUser()->hasAdministrativeAccess()) {
                if (isset($_POST['discordConnect'])) $this->discordConnect = \intval($_POST['discordConnect']);
            }
        }

        if ($eventName == 'saved') {
            $success = [];
            $updateParameters = [];

            if (\DISCORD_CLIENT_ID !== '' && \DISCORD_CLIENT_SECRET !== '') {
                if ($this->discordConnect && WCF::getSession()->getVar('__3rdPartyProvider') == 'discord' && ($oauthUser = WCF::getSession()->getVar('__oauthUser'))) {
                    $updateParameters['authData'] = 'discord:' . $oauthUser->getId();
                    $updateParameters['password'] = null;
                    $success[] = 'wcf.user.3rdparty.discord.connect.success';

                    WCF::getSession()->unregister('__3rdPartyProvider');
                    WCF::getSession()->unregister('__oauthUser');
                }
            }
            if ($this->discordDisconnect && StringUtil::startsWith(WCF::getUser()->authData, 'discord:')) {
                $updateParameters['authData'] = '';
                $success[] = 'wcf.user.3rdparty.discord.disconnect.success';
            }

            if (!empty($updateParameters)) {
                $objectAction = new UserAction([WCF::getUser()], 'update', ['data' => $updateParameters]);
                $objectAction->executeAction();
            }

            WCF::getTPL()->assign('success', $success);
        }
    }

}