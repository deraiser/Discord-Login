{if DISCORD_CLIENT_ID !== '' && DISCORD_CLIENT_SECRET !== ''}
    <dl>
        <dt>{lang}wcf.user.3rdparty.discord{/lang}</dt>
        <dd>
            {if $__wcf->getSession()->getVar('__3rdPartyProvider') === 'discord' && $__wcf->session->getVar('__oauthUser')}
                <label><input type="checkbox" name="discordConnect" value="1"{if $discordConnect} checked{/if}> {lang}wcf.user.3rdparty.discord.connect{/lang}</label>
            {else}
                <a href="{link controller='DiscordAuth'}{/link}" class="thirdPartyLoginButton discordLoginButton button"><span class="icon icon24 fa-discord"></span> <span>{lang}wcf.user.3rdparty.discord.connect{/lang}</span></a>
            {/if}
        </dd>
    </dl>
{/if}