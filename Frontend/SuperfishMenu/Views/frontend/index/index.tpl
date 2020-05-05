{extends file="parent:frontend/index/index.tpl"}

{block name="frontend_index_navigation_categories_top_include"}

	{block name="frontend_index_navigation_categories_top_home"}
	{function name=subs level=0}
            {foreach $subcategories as $category}
                {if $category.hideTop}
                    {continue}
                {/if}

		{if ($category@index is div by 4) && ($level != 1)}
			<hr>
		{/if}

                {$categoryLink = $category.link}
                {if $category.external}
                    {$categoryLink = $category.external}
                {/if}

		<div class="sf-mega-section">
			<a href="{$categoryLink|escapeHtml}" aria-label="{$category.name|escape}" title="{$category.name|escape}"{if $category.external && $category.externalTarget} target="{$category.externalTarget}"{/if}>{$category.description}</a>

			{if $level == 0 && $category.sub}
				<hr>
				<ul class="menu--level-{$level}" style="width: 100%">
			                <li class="item--level-{$level}"{if $level === 0} style="width: 100%"{/if}>
		                        	{call name=subs subcategories=$category.sub level=$level+1}
        			        </li>
				</ul>
			{elseif $category.sub}
				<hr>
				{call name=subs subcategories=$category.sub level=$level+1}
			{/if}

		</div>

       	    {/foreach}
	{/function}
	{/block}

	{block name="frontend_index_navigation_categories"}
	        {block name="frontend_index_navigation_categories_navigation_list"}
		<div id="menu_top">
	            <ul class="sf-menu container" style="z-index: 3001;"  role="menubar" itemscope="itemscope" itemtype="http://schema.org/SiteNavigationElement">
	                {strip}

	                    {block name="frontend_index_navigation_categories_top_before"}
				{*debug*}
			    {/block}
	
	                    {foreach $sMainCategories as $sCategory}
	                        {block name="frontend_index_navigation_categories_top_entry"}

				{$hasCategories = $sCategory.activeCategories }
        			{$hasTeaser = (!empty($sCategory.media) || !empty($sCategory.cmsHeadline) || !empty($sCategory.cmsText))}
	
	                            {if !$sCategory.hideTop || $sCategory.active}
	                                <li {if $sCategory.id == $importantCat}style="background:{$highlightColor};"{/if} role="menuitem">
	                                    {block name="frontend_index_navigation_categories_top_link"}
						<a href="{$sCategory.link}" title="{$sCategory.description}" {if $sBreadcrumb.0.id == $sCategory.id}class="active" {/if} aria-label="{$sCategory.description}" itemprop="url"{if $sCategory.external && $sCategory.externalTarget} target="{$sCategory.externalTarget}"{/if}>
	                                            <span itemprop="name">{$sCategory.description}</span>
	                                        </a>
							{$submenubuilder=$sSuperfishMenu.{$sCategory@index}.sub}

							{if  $sCategory.childrenCount > 0}
							<div class="sf-mega {if $sCategory@index < $maxFirstRow}toprow{/if}">
								{if $teaserPos == '2' || $teaserPos == '0'}
									{call subs subcategories=$submenubuilder}
									<hr>
								{/if}

							        {if $hasTeaser && $teaserPos}
	                				            {if $hasCategories}
			        	                                <div class="menu--delimiter" style="right: 100%;"></div>
                				                    {/if}

			                                	    <div class="menu--teaser" style="width: 100%;">
	                	                        		    {if !empty($sCategory.cmsHeadline)}
				                                	            <div class="teaser--headline">{$sCategory.cmsHeadline}</div>
									    {/if}

				                                    	    {if !empty($sCategory.cmsText)}
					                                            <div class="teaser--text">
	        	        		                                    {$sCategory.cmsText|strip_tags|truncate:256:"..."}
        	        	                    			            <a class="teaser--text-link" href="{$link|escapeHtml}" aria-label="{s name="learnMoreLink" namespace="frontend/plugins/superfish_menu/superfish_menu"}{/s}" title="{s name="learnMoreLink" namespace="frontend/plugins/superfish_menu/superfish_menu"}{/s}">
			        	                        	                    {s name="learnMoreLink" namespace="frontend/plugins/superfish_menu/superfish_menu"}{/s}
                        					                    </a>
				                	                            </div>
                                				            {/if}
		                                        	    </div>
						    		{/if}

									{if $teaserPos == '1'}
										<hr>
										{call subs subcategories=$submenubuilder}
										<hr>
									{/if}
							</div>
							{/if}
	                                    {/block}
	                                </li>
							{if $sCategory@index == $maxFirstRow-1}
								</ul>
						        	<ul class="sf-menu container" style="z-index: 3000;" role="menubar" itemscope="itemscope" itemtype="http://schema.org/SiteNavigationElement">
							{/if}
				    {/if}
	                        {/block}
	                    {/foreach}

	                    {block name="frontend_index_navigation_categories_top_after"}
			    {/block}

	                {/strip}
	            </ul>
		</div>
	        {/block}
	{/block}

	{block name="frontend_plugins_superfish_menu_outer"}
		<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
		<script>
			$(document).ready(function(){
				$('ul.sf-menu').superfish({
					
				});
			});
		</script>
	{/block}

{/block}
