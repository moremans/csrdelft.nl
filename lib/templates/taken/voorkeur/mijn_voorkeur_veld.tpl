{*
	mijn_voorkeur_veld.tpl	|	P.W.G. Brussee (brussee@live.nl)
*}
<td id="voorkeur-row-{$crid}" {if isset($uid)}class="voorkeur-ingeschakeld">
	<a href="{$globals.taken_module}/uitschakelen/{$crid}" class="knop post voorkeur-ingeschakeld"><input type="checkbox" checked="checked" /> Ja</a>
{else}class="voorkeur-uitgeschakeld">
	<a href="{$globals.taken_module}/inschakelen/{$crid}" class="knop post voorkeur-uitgeschakeld"><input type="checkbox" /> Nee</a>	
{/if}
</td>