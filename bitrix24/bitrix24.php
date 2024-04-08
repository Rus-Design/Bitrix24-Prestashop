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

if (!defined('_PS_VERSION_')) {
    exit;
}

class Bitrix24 extends Module
{
    protected $_html = '';
    protected $_postErrors = array();

    public $bitrix_url;
    public $webhook_api;
    public $admin_id;
    public $manager_id;

    public function __construct()
    {
        $this->name = 'bitrix24';
        $this->tab = 'analytics_stats';
        $this->version = '1.1.3';
        $this->author = 'Rus-Design';
        $this->need_instance = 1;
        $this->module_key = '4b9b0d1ab777e68d4740898ec055e9df';

        $config = Configuration::getMultiple(array(
            'BITRIX24_BITRIX_URL',
            'BITRIX24_WEBHOOK_API',
            'BITRIX24_ADMIN_ID',
            'BITRIX24_MANAGER_ID'
        ));
        if (!empty($config['BITRIX24_BITRIX_URL'])) {
            $this->bitrix_url = $config['BITRIX24_BITRIX_URL'];
        }
        if (!empty($config['BITRIX24_WEBHOOK_API'])) {
            $this->webhook_api = $config['BITRIX24_WEBHOOK_API'];
        }
        if (!empty($config['BITRIX24_ADMIN_ID'])) {
            $this->admin_id = $config['BITRIX24_ADMIN_ID'];
        }
        if (!empty($config['BITRIX24_MANAGER_ID'])) {
            $this->manager_id = $config['BITRIX24_MANAGER_ID'];
        } else { //v.1.1.1
            $this->manager_id = null; //v.1.1.1
        } //v.1.1.1

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Bitrix24');
        $this->description = $this->l('Bitrix24 create leads and contacts');

        $this->confirmUninstall = $this->l('');

        if (!isset($this->bitrix_url) || !isset($this->webhook_api) || !isset($this->admin_id)) {
            $this->warning = $this->l('All details must be configured before using this module.');
        }

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('actionValidateOrder');
    }

    public function uninstall()
    {
        Configuration::deleteByName('BITRIX24_BITRIX_URL') and
        Configuration::deleteByName('BITRIX24_ADMIN_ID') and
        Configuration::deleteByName('BITRIX24_MANAGER_ID') and
        Configuration::deleteByName('BITRIX24_WEBHOOK_API');

        return parent::uninstall();
    }

    protected function _postValidation()
    {
        if (Tools::isSubmit('submitBitrix24Module')) {
            if (!Tools::getValue('BITRIX24_WEBHOOK_API')) {
                $this->_postErrors[] = $this->l('Webhook api key are required.');
            } elseif (!Tools::getValue('BITRIX24_BITRIX_URL')) {
                $this->_postErrors[] = $this->l('Bitrix24 url is required.');
            } elseif (!Tools::getValue('BITRIX24_ADMIN_ID')) {
                $this->_postErrors[] = $this->l('Bitrix24 admin id is required.');
            }
        }
    }
    
    protected function _postProcess()
    {
        if (Tools::isSubmit('submitBitrix24Module')) {
            Configuration::updateValue('BITRIX24_BITRIX_URL', Tools::getValue('BITRIX24_BITRIX_URL'));
            Configuration::updateValue('BITRIX24_WEBHOOK_API', Tools::getValue('BITRIX24_WEBHOOK_API'));
            Configuration::updateValue('BITRIX24_ADMIN_ID', Tools::getValue('BITRIX24_ADMIN_ID'));
            Configuration::updateValue('BITRIX24_MANAGER_ID', Tools::getValue('BITRIX24_MANAGER_ID'));
        }
        $this->_html .= $this->displayConfirmation($this->l('Settings updated'));
    }
    
    protected function _displayBitrix24()
    {
        return $this->display(__FILE__, '/views/templates/admin/configure.tpl');
    }

    public function getContent()
    {
        if (Tools::isSubmit('submitBitrix24Module')) {
            $this->_postValidation();
            if (!count($this->_postErrors)) {
                $this->_postProcess();
            } else {
                foreach ($this->_postErrors as $err) {
                    $this->_html .= $this->displayError($err);
                }
            }
        } else {
            $this->_html .= '<br />';
        }

        $this->_html .= $this->_displayBitrix24();
        $this->_html .= $this->renderForm();

        return $this->_html;
        $this->_html .= '<br />';

        $this->_html .= $this->_displayBitrix24();
        $this->_html .= $this->renderForm();
        
        return $this->_html;
        if (((bool)Tools::isSubmit('submitBitrix24Module')) == true) {
            $this->postProcess();
        }
    }

    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitBitrix24Module';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'desc' => $this->l('Bitrix24 url with "https://" and without "/" on end url, like a "https://company.bitrix24.com"'),
                        'name' => 'BITRIX24_BITRIX_URL',
                        'label' => $this->l('Bitrix24 url'),
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'BITRIX24_WEBHOOK_API',
                        'label' => $this->l('Webhook Api Key'),
                        'desc' => $this->l('Create api webhook in Bitrix24 and paste here'),
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'BITRIX24_ADMIN_ID',
                        'label' => $this->l('Bitrix24 admin id'),
                        'desc' => $this->l('Admin id who is created api webhook in Bitrix24'),
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'BITRIX24_MANAGER_ID',
                        'label' => $this->l('Bitrix24 manager id (is not a required)'),
                        'desc' => $this->l('Manager id who is work with deals in Bitrix24. is not a required field. if the value is not set, the default manager is set.'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    protected function getConfigFormValues()
    {
        return array(
            'BITRIX24_BITRIX_URL' => Configuration::get('BITRIX24_BITRIX_URL'),
            'BITRIX24_WEBHOOK_API' => Configuration::get('BITRIX24_WEBHOOK_API'),
            'BITRIX24_ADMIN_ID' => Configuration::get('BITRIX24_ADMIN_ID'),
            'BITRIX24_MANAGER_ID' => Configuration::get('BITRIX24_MANAGER_ID'),
        );
    }

    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    public function hookActionValidateOrder($params) //v.1.1.0
    {
        $cookie = $this->context->cookie; //v.1.1.1
        $shop = Configuration::get('PS_SHOP_NAME');
        $currency = new CurrencyCore($cookie->id_currency); //v.1.1.0
        $my_currency_iso_code = $currency->iso_code; //v.1.1.0
        $order_detail = $params['order']->getOrderDetailList(); //v.1.1.0
        $carrier = new Carrier((int)($this->context->cart->id_carrier), $this->context->cart->id_lang); //v.1.1.0
        $carriername = $carrier->name; //v.1.1.0
        $address = new Address($this->context->cart->id_address_delivery);
        $zipcode = $address->postcode; //v.1.1.1
        $country = $address->country; //v.1.1.1
        $city = $address->city;
        $address1=$address->address1;
        $phone=$address->phone?$address->phone:$address->phone_mobile;
        $total = $this->context->cart->getOrderTotal();
        $products = $this->context->cart->getProducts();
        $order = $params['order'];
        $order_id = $order->id;
        $order_ref = $order->reference;
        $order_message = $order->getFirstMessage(); //v.1.1.1
        $paymentname = $order->payment; //v.1.1.1
        $status_order = $params['orderStatus']->name; //v.1.1.1
        $total_shipping = $this->context->cart->getTotalShippingCost(); //v.1.1.1
        if ($order_message == '') { //v.1.1.1
            $order_message = 'No message from customer'; //v.1.1.1
        } else { //v.1.1.1
            $order_message = $order->getFirstMessage(); //v.1.1.1
        } //v.1.1.1

        $productsinorder = array(); //v.1.1.0
            foreach ($products as $product) { //v.1.1.0
               $productinorder = ''; //v.1.1.1
               $productinorder .= $this->l('Product: ') . $product['name'].(isset($product['attributes']) ? ' - '.$product['attributes'] : '') . '<br>' . $this->l('Sku: ') . $product['reference'] . '<br>' . $this->l('Id: ') . $product['id_product'] . '<br>' . $this->l('Q-ty: ') . $product['quantity'] . '<br>' . $this->l('Price: ') . $product['price'] . ' ' . $my_currency_iso_code . '<br>'; //v.1.1.0
               $productsinorder[] = $productinorder; //v.1.1.0
            } //v.1.1.0

        $queryUrl = $this->bitrix_url.'/'.'rest/'.$this->admin_id.'/'.$this->webhook_api.'/crm.lead.add.json';
        $queryData = http_build_query(array(
        'fields' => array(
            'STATUS_ID' => 'NEW',
            'SOURCE_ID' => 'STORE',
            'ASSIGNED_BY_ID' => $this->manager_id,
            'TITLE' => $this->l('New order number ') . $order_ref . ' (#' . $order_id . ')' . ' - ' . $this->l('Shop ') . $shop,
            'NAME' => $this->context->customer->firstname,
            'LAST_NAME' => $this->context->customer->lastname,
            'ADDRESS' => $address1,
            'ADDRESS_CITY' => $city,
            'COMMENTS' => $productinorder . '<br>' . $this->l('Delivery method: ') . $carriername . '<br>' . $this->l('Payment method: ') . $paymentname . '<br>' . $this->l('Customer message: ') . $order_message . '<br>' . $this->l('Order status: ') . $status_order . '<br>', //v.1.1.0
            'OPPORTUNITY' => $total,
            'CURRENCY_ID' => $my_currency_iso_code, //v.1.1.0
            'PHONE' => array(
                     array(
                         "VALUE" => $phone,
                         "VALUE_TYPE" => "MOBILE"
                     )
                 ),
            'EMAIL' => array(
                     array(
                         "VALUE" => $this->context->customer->email
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
        $result = json_decode($result, 1); //v.1.1.0
        $leadID = $result['result']; //v.1.1.0
    //// Add customer to Bitrix24 contact
        $queryUrl = $this->bitrix_url.'/'.'rest/'.$this->admin_id.'/'.$this->webhook_api.'/crm.contact.add.json';
        $queryData = http_build_query(array(
        'fields' => array(
            'NAME' => $this->context->customer->firstname,
            'LAST_NAME' => $this->context->customer->lastname,
            'ADDRESS' => $address1,
            'ADDRESS_CITY' => $city,
            'ASSIGNED_BY_ID' => $this->manager_id,
            'PHONE' => array(
                     array(
                         "VALUE" => $phone,
                         "VALUE_TYPE" => "MOBILE"
                     )
                 ),
            'EMAIL' => array(
                     array(
                         "VALUE" => $this->context->customer->email
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
        $result = json_decode($result, 1); //v.1.1.0
    //// Add customer to Bitrix24 contact End
        $rows[] = array(); //v.1.1.0
        foreach($products as $product) { //v.1.1.0
            $rows[] = array( //v.1.1.0
                "PRODUCT_NAME" => $product['name'], //v.1.1.0
                "QUANTITY" => $product['quantity'], //v.1.1.0
                "PRICE" => $product['price'], //v.1.1.0
                "PRODUCT_ID" => $product['id_product'], //v.1.1.0
            ); //v.1.1.0
        } //v.1.1.0

        $delivery_cost_in_deal[] = array( //v.1.1.1
            "PRODUCT_NAME" => 'Shipping cost ( ' . $carriername . ' ):', //v.1.1.1
            "PRICE" => $total_shipping, //v.1.1.1
            "QUANTITY" => 1, //v.1.1.1
        ); //v.1.1.1
        //// Add product to Bitrix24
        $queryUrl = $this->bitrix_url.'/'.'rest/'.$this->admin_id.'/'.$this->webhook_api.'/crm.lead.productrows.set.json';
        $queryData = http_build_query(array(
        'ID' => $leadID, //v.1.1.0
        'ROWS' => array_merge($rows,$delivery_cost_in_deal), //v.1.1.1
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
        $result = json_decode($result, 1); //v.1.1.0
        //// Add product to Bitrix24 End
    }
}
