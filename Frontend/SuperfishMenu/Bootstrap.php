<?php

use Doctrine\Common\Collections\ArrayCollection;
use Shopware\Bundle\StoreFrontBundle\Struct\Category;
use Shopware\Components\Plugin;

/**
 * Shopware Advanced Superfish Menu Plugin
 */
class Shopware_Plugins_Frontend_SuperfishMenu_Bootstrap extends Shopware_Components_Plugin_Bootstrap 
{
    /**
     * @return string
     */
    public function getVersion()
    {
        return '1.2.9';
    }

    /**
     * Install plugin method
     *
     * @return bool
     */
    public function install()
    {
        $this->subscribeEvents();
        $this->createForm();

        return true;
    }

    /**
     * @return array
     */
    public function enable()
    {
        return [
            'success' => true,
            'invalidateCache' => ['proxy', 'frontend', 'backend', 'theme'],
        ];
    }

    /**
     * @return array
     */
    public function disable()
    {
        return [
            'success' => true,
            'invalidateCache' => ['proxy', 'frontend', 'backend', 'theme'],
        ];
    }

    /**
     * @return array
     */
    public function getInfo()
    {
        return array(
	    'version' => $this->getVersion(),
            'label' => $this->getLabel(),
	    'link' => 'http://www.glaeserundflaschen.de',
	    'description' => 'jQuery Superfish Menü (Superfish v1.7.10 - The jQuery menu plugin by Joel Birch)<br/>für Shopware5 mit "Kategorie - highlighter" und "Teaser - Konfigurator"',
	    'author' => 'KMATTERN'
        );
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return 'Erweitertes Superfish-Menü';
    }

    /**
     * @return ArrayCollection
     */
    public function onCollectLessFiles()
    {
        $lessDir = __DIR__ . '/Views/frontend/_public/src/less/';

        $less = new \Shopware\Components\Theme\LessDefinition(
            [],
            [
                $lessDir . 'superfish-menu.less',
            ]
        );

        return new ArrayCollection([$less]);
    }

    /**
     * @return ArrayCollection
     */
    public function onCollectJavascriptFiles()
    {
        $jsDir = __DIR__ . '/Views/frontend/_public/src/js/';

        return new ArrayCollection([
            $jsDir . 'jquery.superfish-menu.js',
            $jsDir . 'jquery.hoverIntent.js',
        ]);
    }

    /**
     * Event listener method
     */
    public function onPostDispatch(Enlight_Controller_ActionEventArgs $args)
    {
        $config = $this->Config();

        if (!$config->show) {
            return;
        }

        $view = $args->getSubject()->View();
        $parent = Shopware()->Shop()->get('parentID');
        $categoryId = $args->getRequest()->getParam('sCategory', $parent);

        $menu = $this->getSuperfishMenu($parent, $categoryId, (int) $config->levels);

        $view->assign('sSuperfishMenu', $menu);
        $view->assign('teaserPos', $config->teaserPos);

        $view->assign('importantCat', $config->importantCat);
        $view->assign('highlightColor', $config->highlightColor);
        $view->assign('maxFirstRow', $config->maxFirstRow);

        $view->addTemplateDir($this->Path() . 'Views');
    }

    /**
     * Returns the complete menu with category path.
     *
     * @param int $category
     * @param int $activeCategoryId
     * @param int $depth
     *
     * @return array
     */
    public function getSuperfishMenu($category, $activeCategoryId, $depth = null)
    {
        $context = Shopware()->Container()->get('shopware_storefront.context_service')->getShopContext();

        $cacheKey = sprintf('Shopware_SuperfishMenu_Tree_%s_%s_%s',
            $context->getShop()->getId(),
            $category,
            ($this->Config()->get('includeCustomergroup') ? $context->getCurrentCustomerGroup()->getId() : 'x')
        );

        $eventManager = $this->get('events');
        $cacheKey = $eventManager->filter('Shopware_Plugins_SuperfishMenu_CacheKey', $cacheKey, [
            'shopContext' => $context,
            'config' => $this->Config(),
        ]);

        $cache = Shopware()->Container()->get('cache');

        if ($this->Config()->get('caching') && $cache->test($cacheKey)) {
            $menu = $cache->load($cacheKey, true);
        } else {
            $ids = $this->getCategoryIdsOfDepth($category, $depth);
            $categories = Shopware()->Container()->get('shopware_storefront.category_service')->getList($ids, $context);
            $categoriesArray = $this->convertCategories($categories);
            $categoryTree = $this->getCategoriesOfParent($category, $categoriesArray);
            if ($this->Config()->get('caching')) {
                $cache->save($categoryTree, $cacheKey, ['Shopware_Plugin'], (int) $this->Config()->get('cachetime', 86400));
            }
            $menu = $categoryTree;
        }

        $categoryPath = $this->getCategoryPath($activeCategoryId);
        $menu = $this->setActiveFlags($menu, $categoryPath);

        return $menu;
    }

    private function subscribeEvents()
    {
        $this->subscribeEvent(
            'Theme_Compiler_Collect_Plugin_Less',
            'onCollectLessFiles'
        );

        $this->subscribeEvent(
            'Theme_Compiler_Collect_Plugin_Javascript',
            'onCollectJavascriptFiles'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatchSecure_Frontend',
            'onPostDispatch'
        );
    }

    private function createForm()
    {
        $form = $this->Form();

        $parent = $this->Forms()->findOneBy(['name' => 'Frontend']);
        $form->setParent($parent);

        $form->setElement('checkbox', 'show', [
            'label' => 'Superfish Menü anzeigen',
            'value' => 1,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
        ]);

        $form->setElement('number', 'levels', [
            'label' => 'Anzahl Ebenen',
            'minValue' => '2',
            'value' => 3,
            'description' => 'Anzahl an Unterkategorien kann hier auf die nächsten Unterkategorien verweisen, oder auch tiefer in die Menüstruktur zeigen.',
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
        ]);


        $form->setElement('text', 'maxFirstRow', [
            'label' => 'maximale Kategorien in erster Zeile',
            'value' => 8,
	    'description' => 'Das Menü kann bei zu vielen Hauptkategorien übersichtlicher gestaltet werden. 0 ergibt nur eine Menüzeile!',
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
        ]);

        $form->setElement('text', 'importantCat', [
            'label' => 'Kategorie hervorheben (System-ID)',
            'value' => 0,
	    'description' => 'Um eine besonders wichtige/interessante Kategorie hervorzuheben. Die System-ID steht jeweils bei den Kategorien (Allgemeine Einstellungen - Kategorie: *** (System-ID: ***))',
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
        ]);
	
        $form->setElement('color', 'highlightColor', [
            'label' => 'Highlight Farbe',
            'value' => '#FFC66E',
	    'description' => 'Es werden nur #RRGGBB Werte von Hand eingetragen unterstützt oder über den "color picker" eingestellt (Default-Farbwert #FFC66E)',
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
        ]);

        $form->setElement('select', 'teaserPos', [
                'label' => 'Teaser anzeigen',
                'store' => [
                    [0, 'Nein - ausblenden'],
                    [1, 'Ja - oben'],
                    [2, 'Ja - unten'],
                ],
                'value' => 2,
                'editable' => false,
		'description' => 'Zeigt den einleitenden/beschreibenden Text der Kategorie an, wahlweise über oder unter den Unterkategorien. Ist kein Text vorhanden, zeigt es nur Die Unterkategorien an.',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
            ]
        );

        $form->setElement('boolean', 'caching', [
            'label' => 'Caching aktivieren',
            'value' => 1,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
        ]);

        $form->setElement('number', 'cachetime', [
            'label' => 'Cachezeit',
            'value' => 86400,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
        ]);

        $form->setElement('boolean', 'includeCustomergroup', [
            'label' => 'Kundengruppen für Cache berücksichtigen:',
            'value' => 1,
            'description' => 'Falls aktiv, wird der Cache des Menüs für jede Kundengruppe separat aufgebaut. Nutzen Sie diese Option, falls Sie Kategorien für gewisse Kundengruppen ausgeschlossen haben.<br>Falls inaktiv, erhalten alle Kundengruppen das gleiche Menü aus dem Cache. Diese Einstellung ist zwar performanter, jedoch funktioniert der Kategorieausschluss nach Kundengruppen dann nicht mehr korrekt.',
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
        ]);

        $this->translateForm();
    }

    private function translateForm()
    {
        $translations = [
            'en_GB' => [
                'show' => ['label' => 'Show superfish menu'],
                'levels' => ['label' => 'Category levels'],
		'importantCat' => ['label' => 'Marked Categorie (System-ID)'],
		'maxFirstRow' => ['label' => 'maximum Categories in first row'],
                'caching' => ['label' => 'Enable caching'],
                'cachetime' => ['label' => 'Caching time'],
                'teaserPos' => ['label' => 'enable Teaser and it\'s position'],
                'highlightColor' => ['label' => 'Highlight color for important category'],
                'includeCustomergroup' => ['label' => 'Consider customer groups for cache', 'description' => 'If active, the menu cache is created separately for each customer group. Use this option if you have excluded categories for certain customer groups. <br>If inactive, all customer groups receive the same menu from the cache. This setting is more performant, but the category exclusion by customer groups will then no longer work correctly.'],
            ],
        ];

        $this->addFormTranslations($translations);
    }

    /**
     * @param array[] $categories
     * @param int[]   $actives
     *
     * @return array[]
     */
    private function setActiveFlags($categories, $actives)
    {
        foreach ($categories as &$category) {
            $category['flag'] = in_array($category['id'], $actives);

            if (!empty($category['sub'])) {
                $category['sub'] = $this->setActiveFlags($category['sub'], $actives);
            }
        }

        return $categories;
    }

    /**
     * @param int $categoryId
     *
     * @throws Exception
     *
     * @return int[]
     */
    private function getCategoryPath($categoryId)
    {
        $query = Shopware()->Container()->get('dbal_connection')->createQueryBuilder();

        $query->select('category.path')
              ->from('s_categories', 'category')
              ->where('category.id = :id')
              ->setParameter(':id', $categoryId);

        $path = $query->execute()->fetch(PDO::FETCH_COLUMN);
        $path = explode('|', $path);
        $path = array_filter($path);
        $path[] = $categoryId;

        return $path;
    }

    /**
     * @param int $parentId
     * @param int $depth
     *
     * @throws Exception
     *
     * @return int[]
     */
    private function getCategoryIdsOfDepth($parentId, $depth)
    {
        $query = Shopware()->Container()->get('dbal_connection')->createQueryBuilder();
        $query->select('DISTINCT category.id')
              ->from('s_categories', 'category')
              ->where('category.path LIKE :path')
              ->andWhere('category.active = 1')
              ->andWhere('ROUND(LENGTH(path) - LENGTH(REPLACE (path, "|", "")) - 1) <= :depth')
              ->orderBy('category.position')
              ->setParameter(':depth', $depth)
              ->setParameter(':path', '%|' . $parentId . '|%');

        /** @var PDOStatement $statement */
        $statement = $query->execute();

        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * @param int   $parentId
     * @param array $categories
     *
     * @return array
     */
    private function getCategoriesOfParent($parentId, $categories)
    {
        $result = [];

        foreach ($categories as $index => $category) {
            if ($category['parentId'] != $parentId) {
                continue;
            }
            $children = $this->getCategoriesOfParent($category['id'], $categories);
            $category['sub'] = $children;
            $category['activeCategories'] = count($children);
            $result[] = $category;
        }

        return $result;
    }

    /**
     * @param Category[] $categories
     *
     * @return array
     */
    private function convertCategories($categories)
    {
        $converter = Shopware()->Container()->get('legacy_struct_converter');

        return array_map(function (Category $category) use ($converter) {
            $data = $converter->convertCategoryStruct($category);

            $data['flag'] = false;
            if ($category->getMedia()) {
                $data['media']['path'] = $category->getMedia()->getFile();
            }
            if (!empty($category->getExternalLink())) {
                $data['link'] = $category->getExternalLink();
            }

            return $data;
        }, $categories);
    }
}
