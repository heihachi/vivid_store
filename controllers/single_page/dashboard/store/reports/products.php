<?php

namespace Concrete\Package\VividStore\Controller\SinglePage\Dashboard\Store\Reports;
use \Concrete\Core\Page\Controller\DashboardPageController;
use Core;
use Package;
use \Concrete\Package\VividStore\Src\VividStore\Orders\OrderStatus\OrderStatus;

use \Concrete\Package\VividStore\Src\VividStore\Orders\OrderList;
use \Concrete\Package\VividStore\Src\VividStore\Orders\Order as VividOrder;
use \Concrete\Package\VividStore\Src\VividStore\Report\ProductReport;

defined('C5_EXECUTE') or die("Access Denied.");
class Products extends DashboardPageController
{

    public function view()
    {
    	$pr = new ProductReport();
		$pr->sortByPopularity();
		$products = $pr->getProducts();
    	$this->set("products",$products);
	}
    
}