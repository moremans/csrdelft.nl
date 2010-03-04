<div class="ubb_block ubb_peiling" id="peiling{$peiling->getId()}">
{if $beheer AND $peiling->magBewerken()}
	<a href="peilingbeheer.php?action=verwijder&amp;id={$peiling->getId()}" class="knop beheer" >Verwijder</a>
{/if}
<h3>
	{if $peiling->magBewerken()}<a href="/tools/peilingbeheer.php">#{$peiling->getId()} {/if}
		{$peiling->getTitel()|escape:'html'}
	{if $peiling->magBewerken()}</a>{/if}
</h3>
{$peiling->getTekst()|ubb}

{if $peiling->magStemmen()}
	<form id="peiling{$peiling->getId()}" action="/tools/peilingbeheer.php?action=stem" method="post">
	<input type="hidden" name="id" value="{$peiling->getId()}"/>
{/if}
	<ul class="peilingopties">
	{foreach from=$peiling->getOpties() item=optie}
		<li>
			{if $peiling->magStemmen()}
				<input type="radio" name="optie" value="{$optie.id}"/> 
			{else}
				<div class="stemmen">{$optie.percentage|string_format:'%01.1f'}% ({$optie.stemmen})</div>
				<div class="grafisch"><div class="balk" style="width: {$optie.percentage*1.8|string_format:'%d'}px;">&nbsp;</div></div>
			{/if}
			{$optie.optie|ubb}
		</li>
	{/foreach}
	</ul>
{if $peiling->magStemmen()}
	<input type="submit" value="Verzend" />
</form>
{/if}

</div>
