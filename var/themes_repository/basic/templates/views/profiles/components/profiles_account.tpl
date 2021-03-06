{if !$nothing_extra}
    {include file="common/subheader.tpl" title=__("user_account_info")}
{/if}

{hook name="profiles:account_info"}

{if $nothing_extra || $runtime.checkout}
    <div class="control-group">
        <label for="email" class="cm-required cm-email cm-trim">{__("email")}</label>
        <input type="text" id="email" name="user_data[email]" size="32" maxlength="128" value="{$user_data.email}" class="input-text" />
    </div>
{/if}

<div class="control-group">
    <label for="password1" class="cm-required cm-password">{__("password")}</label>
    <input type="password" id="password1" name="user_data[password1]" size="32" maxlength="32" value="{if $runtime.mode == "update"}            {/if}" class="input-text cm-autocomplete-off" />
</div>

<div class="control-group">
    <label for="password2" class="cm-required cm-password">{__("confirm_password")}</label>
    <input type="password" id="password2" name="user_data[password2]" size="32" maxlength="32" value="{if $runtime.mode == "update"}            {/if}" class="input-text cm-autocomplete-off" />
</div>
{/hook}