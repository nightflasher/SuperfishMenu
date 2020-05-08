{extends file="parent:frontend/index/index.tpl"}

{block name='frontend_index_before_page'}
        <center>hier kann nachher was tolles zum Gro&szlig;handel oder so stehen...vielleicht aber auch ein Ticker im RSS Style?</center>
{/block}

{block name="frontend_index_navigation_categories_top_include"}
{$smarty.block.parent}
        {include file='frontend/plugins/superfish_menu/index.tpl'}
{/block}
