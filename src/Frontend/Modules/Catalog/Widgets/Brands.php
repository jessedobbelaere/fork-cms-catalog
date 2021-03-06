<?php

namespace Frontend\Modules\Catalog\Widgets;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Backend\Modules\Catalog\Domain\Brand\BrandRepository;
use Frontend\Core\Engine\Base\Widget as FrontendBaseWidget;
use Frontend\Core\Engine\Navigation;
use Frontend\Core\Language\Locale;

/**
 * This is a widget with the Catalog-categories
 *
 * @author Waldo Cosman <waldo_cosman@hotmail.com>
 * @author Jacob van Dam <j.vandam@jvdict.nl>
 */
class Brands extends FrontendBaseWidget
{
    /**
     * Execute the extra
     */
    public function execute(): void
    {
        parent::execute();

        $this->loadTemplate();
        $this->parse();
    }

    /**
     * Parse
     */
    private function parse()
    {
        /**
         * @var BrandRepository
         */
        $brandRepository = $this->get('catalog.repository.brand');

        $this->template->assign('brands', $brandRepository->findByLocale(Locale::frontendLanguage()));
        $this->template->assign('baseUrl', Navigation::getURLForBlock('Catalog', 'Brand'));
    }
}
