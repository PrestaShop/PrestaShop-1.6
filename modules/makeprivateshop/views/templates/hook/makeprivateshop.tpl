{if $content_only}
<style type="text/css">
	body#authentication,body#password{ldelim}width:{$width}px;margin:0 auto;background-image:none{rdelim}
	.logo_content{ldelim}margin:10px 0{rdelim}
</style>
{/if}
{if $content_logo || !$create || $content_tunnel || $content_breadcrumb || $center_column}
	<script type="text/javascript">
		$(document).ready(function(){ldelim}
			{if $content_logo && $content_only}
				$('body').prepend('<a href="{$content_dir}" title="{$shop_name|escape:'htmlall':'UTF-8'}"><img class="logo_content" src="{if $url_logo_private}{$url_logo_private}{else}{$img_ps_dir}logo.jpg?{$img_update_time}{/if}" alt="{$shop_name|escape:'htmlall':'UTF-8'}" /></a>');
			{/if}
			{if !$create}
				$('form#create-account_form').replaceWith('');
			{/if}
			{if $content_tunnel}
				$('ul#order_step').replaceWith('');
			{/if}
			{if $content_breadcrumb}
				$('div.breadcrumb').replaceWith('');
			{/if}
			{if $center_column}
				$('h1').after('<div id="center_column"></div>');
				$('#center_column').append($('div.row'));
			{/if}
		{rdelim});
	</script>
{/if}