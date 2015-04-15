<?php
/*
 * Plugin Name: درگاه پرداخت پاسارگاد برای ووکامرس همراه با تایید سفارش
 * Plugin URI: http://blog.alafalaki.ir/%D8%A7%D9%81%D8%B2%D9%88%D9%86%D9%87-%D9%BE%D8%B1%D8%AF%D8%A7%D8%AE%D8%AA-%D8%A8%D8%A7%D9%86%DA%A9-%D9%BE%D8%A7%D8%B3%D8%A7%D8%B1%DA%AF%D8%A7%D8%AF-%D9%88%D9%88%DA%A9%D8%A7%D9%85%D8%B1%D8%B3/
 * Description: درگاه کامل جهان پی برای سایت های فروش فایل. لطفا قبل از استفاده از طریق لینک دیدن خانه افزونه، تغییرات مورد نیاز افزونه را اعمال نمایید.
 * Version: 1.0
 * Author: Ala Alam Falaki
 * Author URI: http://AlaFalaki.ir
 * 
 */


session_start();

require_once("pasargadGatewayClass.php"); // Add Pasargad class To Plugin

add_action('plugins_loaded', 'WC_P', 0); // Make The Plugin Work...

function WC_P() {
    if ( !class_exists( 'WC_Payment_Gateway' ) ) return; // import your gate way class extends/
	
    class WC_full_Ppayment extends WC_Payment_Gateway {
        public function __construct(){
        	
            $this -> id 			 	 = 'Ppayment';
            $this -> method_title 	  	 = 'بانک پاسارگاد';
            $this -> has_fields 	   	 = false;
            $this -> init_form_fields();
            $this -> init_settings();
			
			$this->title				= $this->get_option('title');
			$this->description			= $this->get_option('description');
			$this->merchantCode			= $this->get_option('merchantCode');
			$this->terminalCode			= $this->get_option('terminalCode');
            
  		    if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) { // Compatibalization plugin for diffrent versions.
                add_action( 'woocommerce_update_options_payment_gateways_Ppayment', array( &$this, 'process_admin_options' ) );
             } else {
                add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
            }
			 
			add_action('woocommerce_receipt_Ppayment', array(&$this, 'receipt_page'));
        }

		/**
		 * Declaring admin page fields.
		 */
       function init_form_fields(){
            $this -> form_fields = array(
                'enabled' => array(
                    'title' => 'فعال سازی/غیر فعال سازی :',
                    'type' => 'checkbox',
                    'label' => 'فعال سازی درگاه پرداخت بانک پاسارگاد',
                    'description' => 'برای امکان پرداخت کاربران از طریق این درگاه باید تیک فعال سازی زده شده باشد .',
                    'default' => 'no'),
                'merchantCode' => array(
                    'title' => 'کد merchant :',
                    'type' => 'text',
                    'description' => 'شما میتوانید این کد را از بانک ارائه دهنده درگاه دریافت نمایید .'),
                'terminalCode' => array(
                    'title' => 'کد terminal :',
                    'type' => 'text',
                    'description' => 'شما میتوانید این کد را از بانک ارائه دهنده درگاه دریافت نمایید .'),
                'title' => array(
                    'title' => 'عنوان درگاه :',
                    'type'=> 'text',
                    'description' => 'این عتوان در سایت برای کاربر نمایش داده می شود .',
                    'default' => 'بانک پاسارگاد'),
                'description' => array(
                    'title' => 'توضیحات درگاه :',
                    'type' => 'textarea',
                    'description' => 'این توضیحات در سایت، بعد از انتخاب درگاه توسط کاربر نمایش داده می شود .',
                    'default' => 'پرداخت وجه از طریق درگاه بانک پاسارگاد توسط تمام کارت های عضو شتاب .'),
            );
        }
        public function admin_options(){
            echo '<h3>درگاه پرداخت بانک پاسارگاد</h3>';
			echo '<table class="form-table">';
			$this -> generate_settings_html();
			echo '</table>';
			echo '
				<div>
					<a href="http://blog.alafalaki.ir/%D8%A7%D9%81%D8%B2%D9%88%D9%86%D9%87-%D9%BE%D8%B1%D8%AF%D8%A7%D8%AE%D8%AA-%D8%A8%D8%A7%D9%86%DA%A9-%D9%BE%D8%A7%D8%B3%D8%A7%D8%B1%DA%AF%D8%A7%D8%AF-%D9%88%D9%88%DA%A9%D8%A7%D9%85%D8%B1%D8%B3/">صفحه رسمی پلاگین + مستندات .</a><br />
					<a href="https://github.com/AlaFalaki/Ppayment" target="_blank">حمایت از پروژه در GitHub .</a><br />
					<a href="https://twitter.com/AlaFalaki" target="_blank">من را در تویتر دنبال کنید .</a>
				</div>
			';
		}
        /**
         * Receipt page.
         **/
		function receipt_page($order_id){
            global $woocommerce;
			
            $order = &new WC_Order($order_id);
			
			
            $callback 				= get_site_url() . "/wc-api/callback_controller/";
			$merchantCode			= $this->merchantCode;
			$terminalCode			= $this->terminalCode;
			$order_total			= round($order -> order_total);
			
				$gateWay = new PasargadBank_GateWay();
				date_default_timezone_set('Asia/Tehran');
				$gateWay->SendOrder($order_id,date("Y/m/d H:i:s"),$order_total*10, $merchantCode, $terminalCode, $callback);
        }
        
        /**
         * Process_payment Function.
         **/
        function process_payment($order_id){
            $order = &new WC_Order($order_id);
            return array('result' => 'success', 'redirect' => add_query_arg('order',
                $order->id, add_query_arg('key', $order->order_key, $this->get_return_url($this->order)))
            );
        }
	}
    /**
     * Add the Gateway to WooCommerce.
     **/
    function woocommerce_add_JPpayment_gateway($methods) {
        $methods[] = 'WC_full_Ppayment';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_JPpayment_gateway' );


	/*
	 * CallBack Handler
	 * 
	 * Handle The TransAction With JP Servers !
	 */
	class callback_controller extends WC_Payment_Gateway{
		public function __construct(){
			global $woocommerce;

			require_once ("pasargadGatewayClass.php");
			$order_id 				= $_GET['iN'];
			$tref 					= $_GET['tref'];
			$order 					= new WC_Order($order_id);

			$payment_Model			= new WC_full_Ppayment();

			$merchantCode			= $payment_Model->merchantCode;
			$terminalCode			= $payment_Model->terminalCode;

			$OrderStatus = new PasargadBank_GateWay();

			$result = $OrderStatus->getOrder($_GET['tref']);

			if(($_SESSION['pasargadAmount']/10) == $order->order_total){

				if($result['resultObj']['result'] == "True"){ // Check the result.

					if($OrderStatus->verifyOrder($merchantCode, $terminalCode)){
						if($order->status !=='completed'){
								$order->payment_complete();
			                    $order->add_order_note('پرداخت موفق، کد پرداخت: '.$tref);
			                    $woocommerce->cart->empty_cart();
								header("location: " . get_site_url() . "/my-account/?TransAction=Succes&Order_id=" . $order_id);
								exit;
						}
					}else{
						$order -> add_order_note('پرداخت تایید نشد.');
						header("location: " . get_site_url() . "/my-account/?TransAction=NotValidated&Order_id=" . $order_id);
						exit;
					}

				}else{
					unset($_SESSION['pasargadAmount']);
					$order -> add_order_note('پرداخت ناموفق.');
					header("location: " . get_site_url() . "/my-account/?TransAction=Faild&Order_id=" . $order_id);
					exit;
				}

			}else{
				unset($_SESSION['pasargadAmount']);
				$order -> add_order_note('پرداخت نا معتبر.');
				header("location: " . get_site_url() . "/my-account/?TransAction=NotValid&Order_id=" . $order_id);
			}
		}
	}
}
?>