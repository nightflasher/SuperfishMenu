{extends file="parent:frontend/index/index.tpl"}

{block name='frontend_index_navigation_categories_top'}
    <nav class="navigation-main">
        <div class="container" data-menu-scroller="true" data-listSelector=".navigation--list.container" data-viewPortSelector=".navigation--list-wrapper">
            {block name="frontend_index_navigation_categories_top_include"}
                {include file="frontend/plugins/superfish_menu/index.tpl"}
            {/block}
        </div>
    </nav>
{/block}
