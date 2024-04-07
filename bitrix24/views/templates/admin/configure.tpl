{*
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
*}

<div class="panel">
	<div class="row moduleconfig-header">
		<div class="col-xs-5 text-right">
			<img src="{$module_dir|escape:'html':'UTF-8'}views/img/logo.jpg" />
		</div>
		<div class="col-xs-7 text-left">
			<h2>{l s='Bitrix24 module integration' mod='bitrix24'}</h2>
			<h4>{l s='Send data all you need from your shop' mod='bitrix24'}</h4>
		</div>
	</div>

	<div class="row moduleconfig-header">
		<div class="col-xs-5 text-right">
			<img src="{$module_dir|escape:'html':'UTF-8'}views/img/logo.jpg" />
		</div>
		<div class="col-xs-7 text-left">
			<span>{l s='Other modules ' mod='bitrix24'}</span><a href="https://rus-design.com/en/?{Context::getContext()->shop->getBaseURL(true)}" target="_blank">https://rus-design.com/en/</a>
		</div>
	</div>

	<hr />

	<div class="moduleconfig-content">
		<div class="row">
			<div class="col-xs-12">
				<p>
					<h4>{l s='Send all you need informartion of order and customer' mod='bitrix24'}</h4>
					<ul class="ul-spaced">
						<li>{l s='Order number & order ID' mod='bitrix24'}</li>
						<li>{l s='Shop name' mod='bitrix24'}</li>
						<li>{l s='Customer firstname' mod='bitrix24'}</li>
						<li>{l s='Customer lastname' mod='bitrix24'}</li>
						<li>{l s='City' mod='bitrix24'}</li>
						<li>{l s='Delivery address' mod='bitrix24'}</li>
						<li>{l s='Phone number' mod='bitrix24'}</li>
						<li>{l s='Customer email' mod='bitrix24'}</li>
						<li>{l s='Order total' mod='bitrix24'}</li>
						<li>{l s='Products in order (Non create product in Bitrix24)' mod='bitrix24'}</li>
						<li>{l s='Product reference' mod='bitrix24'}</li>
						<li>{l s='Product id' mod='bitrix24'}</li>
						<li>{l s='Quantity of product in order' mod='bitrix24'}</li>
						<li>{l s='Delivery method' mod='bitrix24'}</li>
						<li>{l s='Payment method (NEW!)' mod='bitrix24'}</li>
						<li>{l s='Customer comment (NEW!)' mod='bitrix24'}</li>
						<li>{l s='Order status (NEW!)' mod='bitrix24'}</li>
						<li>{l s='Abandoned cart (NEW!)' mod='bitrix24'}</li>
					</ul>
				</p>

				<br />
            <p class="text-center">CRON URL FOR ABANDONED CART <strong> {Context::getContext()->shop->getBaseURL(true)}module/bitrix24/SendAbandonedCartCron?token=2908yarand88o</strong></p>
				<p class="text-center">
					<strong>
						<a href="https://addons.prestashop.com/ru/2_community-developer?contributor=604758" target="_blank" title="Prestashop Modules Development - R-D">
							<span>Prestashop Modules Development - R-D</span>
						</a>
					</strong>
				</p>
			</div>
		</div>
	</div>
</div>