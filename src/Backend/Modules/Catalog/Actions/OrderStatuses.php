<?php

namespace Backend\Modules\Catalog\Actions;

use Backend\Core\Engine\Base\ActionIndex as BackendBaseActionIndex;
use Backend\Core\Language\Locale;
use Backend\Core\Engine\DataGrid as BackendDataGridDB;
use Backend\Modules\Catalog\Domain\OrderStatus\DataGrid;

/**
 * This is the vats action, it will display the overview of vats
 *
 * @author Jacob van Dam <j.vandam@jvdict.nl>
 */
class OrderStatuses extends BackendBaseActionIndex
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
