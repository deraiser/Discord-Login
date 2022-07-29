{if DISCORD_CLIENT_ID !== '' && DISCORD_CLIENT_SECRET !== ''}
    <li id="discordAuth" class="thirdPartyLogin">
        <a href="{link controller='DiscordAuth'}{/link}" class="button thirdPartyLoginButton discordLoginButton"><span class="icon icon24 fa-discord"></span> <span>{lang}wcf.user.3rdparty.discord.login{/lang}</span></a>
    </li>
{/if}