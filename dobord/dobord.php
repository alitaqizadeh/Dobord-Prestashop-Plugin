<?php

if (!defined('_PS_VERSION_'))
exit;

class Dobord extends Module
{
	public function __construct()
	{
		$this->name = 'dobord';
		$this->tab = 'front_office_features';
		$this->version = '1.0';
		$this->author = 'Dobord Team';
		$this->secure_key = Tools::encrypt($this->name);
		parent::__construct();
		$this->displayName = $this->l('Dobord Module');
		$this->description = $this->l('Connect To Dobord Api And Send Customer Informations');
		
		parent::__construct();
 
		$this->displayName = $this->l('dobord');
		$this->description = $this->l('Connect To Dobord Api And Send Customer Informations');
	 
		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
	 
		if (!Configuration::get('dobord')) {
		  $this->warning = $this->l('No name provided');
		}
	}
	
	public function install()
	{
		if (!parent::install() OR !$this->registerHook('actionValidateOrder') OR !$this->registerHook('footer'))
		  return false;
		return true;
	}
	
	public function uninstall()
	{
		if (!parent::uninstall())
		  return false;
		return true;
	}
	public function hookFooter($params)
	{
		if(isset($_GET['dobord_session_id']))
		{
			$domain = ($_SERVER['HTTP_HOST'] != 'localhost') ? $_SERVER['HTTP_HOST'] : false;
			setcookie("dbrdsid", $_GET['dobord_session_id'], time() + 3600, "/", $domain, false);
		}
	}
	public function hookActionValidateOrder($params)
	{
		if(isset($_COOKIE['dbrdsid']))
		{
			$details = $params['order'];

			$curr_iso = $this->context->currency->iso_code;
			$curr_name = $this->context->currency->name;

			$rial = true;

			if($curr_iso != 'IRR' && $curr_name != 'Rial')
			{
				$rial = false;		
			}

			$transactionid = $details->reference;
			$products = array();
			foreach ($details->product_list as $product) 
			{
				if ($rial == false) 
				{
					$price = ($product['total']) * 10;
				}
				else
				{
					$price = $product['total'];
				}
				$products[$product['id_product']] = $price;
			}
			$auth = [
	            'username' => 'test',
	            'password' => 'test'
        	];

        	$ch = curl_init();
        	curl_setopt($ch, CURLOPT_URL, "https://site.dobord.com/merchant");
	        curl_setopt($ch, CURLOPT_POST, 1);
	        curl_setopt($ch, CURLOPT_POSTFIELDS,
	            http_build_query($auth));
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	        $response_loging = curl_exec($ch);
	        curl_close($ch);
	        if ($response_loging)
	        {
	            $response_loging = json_decode($response_loging, true);
	        }
	        if (is_array($response_loging) && $response_loging['token'])
	        {
               $customer = [
                    'token' => $response_loging['token'],
                    'userid' => $_COOKIE['dbrdsid'],
                    'transactionid' => $transactionid,
                    "productlist" => $products,
                ];

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, "https://site.dobord.com/customer");
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS,
                    http_build_query($customer));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response_cache_back_money = curl_exec($ch);
                curl_close($ch);
            }
        	setcookie("dbrdsid", "", time() - 3600, "/");
        	header("Location: https://dobord.com/searchstore");
			die();
		}
	}
}
?>