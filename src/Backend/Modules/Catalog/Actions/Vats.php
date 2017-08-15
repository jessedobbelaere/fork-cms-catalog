<?php

namespace Backend\Modules\Catalog\Actions;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Backend\Core\Engine\Base\ActionIndex as BackendBaseActionIndex;
use Backend\Core\Language\Locale;
use Backend\Core\Engine\DataGrid as BackendDataGridDB;
use Backend\Modules\Catalog\Domain\Vat\DataGrid;

/**
 * This is the vats action, it will display the overview of vats
 *
 * @author Jacob van Dam <j.vandam@jvdict.nl>
 */
class Vats extends BackendBaseActionIndex
{
    /**
     * DataGrid
     *
     * @var	BackendDataGridDB
     */
    protected $dataGrid;

    /**
     * Execute the action
     */
    public function execute(): void
    {
        parent::execute();

	    $this->template->assign('dataGrid', DataGrid::getHtml(Locale::workingLocale()));

	    $this->parse();
	    $this->display();
    }
}
