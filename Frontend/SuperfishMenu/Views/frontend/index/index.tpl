{extends file="parent:frontend/index/index.tpl"}

{block name="frontend_index_navigation_categories_top_include"}
{$smarty.block.parent}
        {include file='frontend/plugins/superfish_menu/index.tpl'}
{/block}
