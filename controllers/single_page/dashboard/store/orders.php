<?php

namespace Concrete\Package\VividStore\Controller\SinglePage\Dashboard\Store;

use \Concrete\Core\Page\Controller\DashboardPageController;

use \Concrete\Package\VividStore\Src\VividStore\Order\OrderStatus\OrderStatus as StoreOrderStatus;
use \Concrete\Package\VividStore\Src\VividStore\Order\OrderList as StoreOrderList;
use \Concrete\Package\VividStore\Src\VividStore\Order\Order as StoreOrder;

use \Concrete\Package\VividStore\Src\VividStore\Utilities\Price as Price;

use Core;

class Orders extends DashboardPageController
{

    public function view($status = '')
    {
        $orderList = new StoreOrderList();

        if ($this->get('keywords')) {
            $orderList->setSearch($this->get('keywords'));
        }

        if ($status) {
            $orderList->setStatus($status);
        }

        $orderList->setItemsPerPage(20);

        $paginator = $orderList->getPagination();
        $pagination = $paginator->renderDefaultView();
        $this->set('orderList',$paginator->getCurrentPageResults());
        $this->set('pagination',$pagination);
        $this->set('paginator', $paginator);
        $this->set('orderStatuses', StoreOrderStatus::getList());
        $this->requireAsset('css', 'vividStoreDashboard');
        $this->requireAsset('javascript', 'vividStoreFunctions');
        $this->set('statuses', StoreOrderStatus::getAll());

    }
    public function order($oID)
    {
        $order = StoreOrder::getByID($oID);
        $this->set("order",$order);
        $this->set('orderStatuses', StoreOrderStatus::getList());
        $this->requireAsset('javascript', 'vividStoreFunctions');
    }
    public function removed()
    {
        $this->set("success",t("Order Removed"));
        $this->view();
    }
    public function updatestatus($oID)
    {
        $data = $this->post();
        StoreOrder::getByID($oID)->updateStatus($data['orderStatus']);
        $this->redirect('/dashboard/store/orders/order',$oID);
    }
    public function remove($oID)
    {
        StoreOrder::getByID($oID)->remove();
        $this->redirect('/dashboard/store/orders/removed');
    }
    public function csv($token = '')
    {
        // Function copied and modified from dashboard/reports/logs
        $valt = Core::make('helper/validation/token');
        if (!$valt->validate('', $token)) {
            $this->redirect('/dashboard/store/orders');
        } else {
            // get the list of orders
            $orderList = new StoreOrderList();
            $orderList = $orderList->getPagination()->getCurrentPageResults();

            $fileName = "Vivid Store Order History";

            header("Content-Type: text/csv");
            header("Cache-control: private");
            header("Pragma: public");
            $date = date('Ymd');
            header("Content-Disposition: attachment; filename=" . $fileName . "_form_data_{$date}.csv");

            $fp = fopen('php://output', 'w');

            // write the columns
            $row = array(
                t('Order Number'),
                t('Order Date'),
                t('Billing First Name'),
                t('Billing Last Name'),
                t('Billing E-mail'),
                t('Billing Phone'),
                t('Billing Address1'),
                t('Billing Address2'),
                t('Billing City'),
                t('Billing State'),
                t('Billing Postal Code'),
                t('Billing Country'),
                t('Delivery First Name'),
                t('Delivery Last Name'),
                t('Delivery Phone'),
                t('Delivery Address1'),
                t('Delivery Address2'),
                t('Delivery City'),
                t('Delivery State'),
                t('Delivery Postal Code'),
                t('Delivery Email Address'),
                t('Delivery Country'),
                t('Product Name'),
                //t('Description'),
                t('Quantity'),
                t('Price'),
                t('Ship Method')
            );

            fputcsv($fp, $row);

            foreach($orderList as $order)
            {
                $items = $order->getOrderItems();
                if($items)
                {
                    foreach($items as $item)
                    {
                        // Set data if shipping is not set (mainly for invoices)
                        if($order->getAttribute("shipping_address")->address1)
                        {
                            $shipping_first = $order->getAttribute("shipping_first_name");
                            $shipping_last = $order->getAttribute("shipping_last_name");
                            $shipping_phone = $order->getAttribute("shipping_phone");
                            $address = $order->getAttribute("shipping_address");
                        }
                        else
                        {
                            $shipping_first = $order->getAttribute("billing_first_name");
                            $shipping_last = $order->getAttribute("billing_last_name");
                            $shipping_phone = $order->getAttribute("billing_phone");
                            $address = $order->getAttribute("billing_address");
                        }
                        // Now to set all the data
                        $row = array(
                            $order->getOrderID(),                                    // t('Order Number')
                            $order->getStatusHistory()[0]->getDate(),                // t('Order Date') grab first date
                            $order->getAttribute("billing_first_name"),              // t('Billing First Name')
                            $order->getAttribute("billing_last_name"),               // t('Billing Last Name')
                            $order->getAttribute("email"),                           // t('Billing E-mail')
                            $order->getAttribute("billing_phone"),                   // t('Billing Phone')
                            $order->getAttribute("billing_address")->address1,       // t('Billing Address1')
                            $order->getAttribute("billing_address")->address2,       // t('Billing Address2')
                            $order->getAttribute("billing_address")->city,           // t('BillingCity'),
                            $order->getAttribute("billing_address")->state_province, // t('BillingState'),
                            $order->getAttribute("billing_address")->postal_code,    // t('BillingPostCode'),
                            $order->getAttribute("billing_address")->country,        // t('BillingCountry'),
                            $shipping_first,                                         // t('Delivery First Name')
                            $shipping_last,                                          // t('Delivery Last Name')
                            $shipping_phone,                                         // t('Delivery Phone')
                            $address->address1,                                      // t('Delivery Address1')
                            $address->address2,                                      // t('Delivery Address2')
                            $address->city,                                          // t('Delivery City')
                            $address->state_province,                                // t('Delivery State')
                            $address->postal_code,                                   // t('Delivery Postal Code')
                            $order->getAttribute("email"),                           // t('Delivery Email Address'),
                            $address->country,                                       // t('Delivery Country')
                            $item->getProductName(),                                 // t('Product Name')
                            strip_tags($item->getProductObject()->getProductDesc()), // t('Description')
                            $item->getQty(),                                         // t('Quantity')
                            Price::format($item->getSubTotal()),                     // t('Price')
                            $order->getShippingMethodName()                          // t('Ship Method')
                        );
                        // write each item as a seperate entry
                        fputcsv($fp, $row);
                    }
                }
            }
            fclose($fp);
            die;
        }
    }

}
