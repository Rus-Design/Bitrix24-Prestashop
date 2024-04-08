<?php
/**
* 2007-2021 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
 *  @author    Rus-Design info@rus-design.com
 *  @copyright 2020 Rus-Design
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  Property of Rus-Design
*/

class Bitrix24SendAbandonedCartCronModuleFrontController extends ModuleFrontController
{
    public $bitrix_url;
    public $webhook_api;
    public $admin_id;

    public function init()
    {
        $cart_cron_token = '2908yarand88o';
        parent::init();
        header('Content-Type: text/plain');
        if (Tools::getValue('token') == $cart_cron_token) {
            $this->abandonedCart();
            die();
        } else {
            die(1);
        }
    }



    private function abandonedCart()
    {
        $this->bitrix_url = Configuration::get('BITRIX24_BITRIX_URL');
        $this->admin_id = Configuration::get('BITRIX24_ADMIN_ID');
        $this->webhook_api = Configuration::get('BITRIX24_WEBHOOK_API');
        $cookie = $this->context->cookie;
        $shop = Configuration::get('PS_SHOP_NAME');
        $currency = new CurrencyCore($cookie->id_currency);
        $my_currency_iso_code = $currency->iso_code;
        $autoresponder_id = 1;
        $delay = 15;
        $sync_fields = array("first_name", "last_name", "name", "description", "sku", "price", "quantity");
        $abandoned_carts = $this->getAbandonedCarts();
        foreach ($abandoned_carts as $abandoned_cart) {
            $cart_updated_time = strtotime($abandoned_cart['date_upd']);
            $reminder_time = strtotime('+' . $delay . ' minutes', $cart_updated_time);
            $current_time = strtotime(date('Y-m-d H:i') . ':00');
            // Don't continue if cart delay time has not passed.
            if ($current_time < $reminder_time) {
                continue;
            }
            // Get cart by id.
            $id_customer = (int) $abandoned_cart['id_customer'];
            $id_cart = (int) $abandoned_cart['id_cart'];
            $cart = new Cart($abandoned_cart['id_cart']);
            $customer_address = new Address($this->context->cart->id_address_delivery);
            $city = $customer_address->city;
            $address1=$customer_address->address1;
            $phone=$customer_address->phone;

            // Get cart products.
            $products = $cart->getProducts();
            // Don't continue if no products in cart.
            if (empty($products)) {
                continue;
            }


            $adresses = array(
                'email' => $abandoned_cart['email'],
            );

            if (in_array('first_name', $sync_fields)) {
                $adresses['first_name'] = $abandoned_cart['firstname'];
            }

            if (in_array('last_name', $sync_fields)) {
                $adresses['last_name'] = $abandoned_cart['lastname'];
            }

            // Populate abandoned cart with empty values for legacy api.
            $fields_available = array(
                'name',
                'description',
                'sku',
                'price',
                'quantity',
                'base_price',
            );
            foreach ($fields_available as $field) {
                for ($i=1; $i<=10; $i++) {
                    $adresses['product_' . $field . '_' . $i] = '';
                }
            }

            $selected_fields = array_intersect($fields_available, $sync_fields);
            // Collect products of abandoned cart.
            $count = 1;
            $dealProductsRows[] = array();
            foreach ($products as $product) {

                $upc_barcode = $product['upc'];
               $ean13_barcode = $product['ean13'];
               $brand = $product['manufacturer_name'];
               $supplier = Supplier::getNameById($product['id_supplier']);
               $variants = $product['name'].(isset($product['attributes']) ? ' - '.$product['attributes'] : '');
               $link = new Link();
               $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
               $crewrite = Category::getLinkRewrite($product['id_category_default'], $id_lang);
               $url = $link->getProductLink($product['id_product'], $product['link_rewrite'], $crewrite);
                // Get only 10 products.
                if ($count > 10) {
                    $adresses['over_10_products'] = 'true';
                    break;
                }
                // Standardize template parameters across integrations.
                foreach ($selected_fields as $sync_field) {
                    switch ($sync_field) {
                        case 'base_price':
                            $adresses['product_base_price_' . $count] = Tools::displayPrice(
                                $product['price_without_reduction']
                            );
                            break;
                        case 'price':
                            $adresses['product_price_' . $count] = Tools::displayPrice(
                                $product['price_with_reduction']
                            );
                            break;
                        case 'sku':
                            $adresses['product_sku_' . $count] = $product['reference'];
                            break;
                        case 'description':
                            $adresses['product_description_' . $count] = htmlspecialchars(
                                $product['description_short']
                            );
                            break;
                        default:
                            $adresses['product_' . $sync_field .'_' . $count] = $product[$sync_field];
                            break;
                    }
                }
                $count++;                
            }

            $productsinorder = array();
            foreach ($products as $product) {
               $productinorder = '';
               $productinorder .= $this->l('Product: ') . $product['name'].(isset($product['attributes']) ? ' - '.$product['attributes'] : '') . '<br>' . $this->l('Sku: ') . $product['reference'] . '<br>' . $this->l('Id: ') . $product['id_product'] . '<br>' . $this->l('Q-ty: ') . $product['quantity'] . '<br>' . $this->l('Price: ') . $product['price'] . ' ' . $my_currency_iso_code . '<br>'; //v.1.1.0
               $productsinorder[] = $productinorder;
            }

            $queryUrl = $this->bitrix_url.'/'.'rest/'.$this->admin_id.'/'.$this->webhook_api.'/crm.lead.add.json';
            $queryData = http_build_query(array(
            'fields' => array(
                'TITLE' => $this->l('Abandoned cart number ') . $id_cart,
                'OPPORTUNITY' => $product['price_with_reduction'],
                'CURRENCY_ID' => $my_currency_iso_code,
                'STATUS_ID' => 'NEW',
                'TYPE_ID' => 'SALE',
                'SOURCE_ID' => 'STORE',
                'ASSIGNED_BY_ID' => null,
                'COMMENTS' => $productinorder,
                'NAME' => $abandoned_cart['firstname'],
                'LAST_NAME' => $abandoned_cart['lastname'],
                'ADDRESS' => $address1,
                'ADDRESS_CITY' => $city,
                'PHONE' => array(
                    array(
                        "VALUE" => $phone,
                        "VALUE_TYPE" => "MOBILE"
                    )
                ),
                'EMAIL' => array(
                    array(
                        "VALUE" => $abandoned_cart['email']
                   )
                    )
       ),
            'params' => array("REGISTER_SONET_EVENT" => "Y")
            ));
            $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $queryUrl,
            CURLOPT_POSTFIELDS => $queryData,
            ));
            $result = curl_exec($curl);
            curl_close($curl);
            $result = json_decode($result, 1);
            $leadID = $result['result'];
            
            $queryUrl = $this->bitrix_url.'/'.'rest/'.$this->admin_id.'/'.$this->webhook_api.'/crm.contact.add.json';
            $queryData = http_build_query(array(
            'fields' => array(
                'NAME' => $abandoned_cart['firstname'],
                'LAST_NAME' => $abandoned_cart['lastname'],
                'ADDRESS' => $address1,
                'ADDRESS_CITY' => $city,
                'ASSIGNED_BY_ID' => null,
                'PHONE' => array(
                         array(
                             "VALUE" => $phone,
                             "VALUE_TYPE" => "MOBILE"
                         )
                     ),
                'EMAIL' => array(
                         array(
                             "VALUE" => $abandoned_cart['email']
                        )
                         )
            ),
            ));
            $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $queryUrl,
            CURLOPT_POSTFIELDS => $queryData,
            ));
            $result = curl_exec($curl);
            curl_close($curl);
            $result = json_decode($result, 1);

            $rows[] = array();
            foreach($products as $product) {
                $rows[] = array(
                    "PRODUCT_ID" => $product['id_product'],
                    "XML_ID" => $product['id_product'],
                    "PRODUCT_NAME" => $product['name'],
                    "PRICE" => $product['price_with_reduction'],
                    "QUANTITY" => $product['quantity'],
                );
            }

            $queryUrl = $this->bitrix_url.'/'.'rest/'.$this->admin_id.'/'.$this->webhook_api.'/crm.lead.productrows.set.json';
                    $queryData = http_build_query(array(
                    'ID' => $leadID,
                    'ROWS' => $rows,
                    ));
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                    CURLOPT_SSL_VERIFYPEER => 0,
                    CURLOPT_POST => 1,
                    CURLOPT_HEADER => 0,
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_URL => $queryUrl,
                    CURLOPT_POSTFIELDS => $queryData,
                    ));
                   
                    $result = curl_exec($curl);
                    curl_close($curl);
                    $result = json_decode($result, 1);
        }
        
    }

    /**
     * Gets abandoned cart data from DB.
     *
     * @return array Abandoned carts array
     */
    private function getAbandonedCarts()
    {
        $sql = 'SELECT c.id_cart,
                    c.id_customer,
                    c.date_upd,
                    cu.firstname,
                    cu.lastname,
                    cu.email
                FROM ' . _DB_PREFIX_ . 'cart c
                LEFT JOIN ' . _DB_PREFIX_ . 'orders o
                ON (o.id_cart = c.id_cart)
                RIGHT JOIN ' . _DB_PREFIX_ . 'customer cu
                ON (cu.id_customer = c.id_customer)
                RIGHT JOIN '._DB_PREFIX_.'cart_product cp
                ON (cp.id_cart = c.id_cart)
                WHERE DATE_SUB(CURDATE(),INTERVAL 7 DAY) <= c.date_add
                AND o.id_order IS NULL';

        $sql .= Shop::addSqlRestriction(Shop::SHARE_CUSTOMER, 'c');
        $sql .= ' GROUP BY cu.id_customer';

        return Db::getInstance()->executeS($sql);
    }
}