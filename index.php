<?php
/*
 * Plugin Name: درگاه پرداخت پاسارگاد برای ووکامرس همراه با تایید سفارش
 * Plugin URI: http://blog.alafalaki.ir/%d8%a7%d9%81%d8%b2%d9%88%d9%86%d9%87-%d9%be%d8%b1%d8%af%d8%a7%d8%ae%d8%aa-%d8%a8%d8%a7%d9%86%da%a9-%d9%be%d8%a7%d8%b3%d8%a7%d8%b1%da%af%d8%a7%d8%af-%d9%88%d9%88%da%a9%d8%a7%d9%85%d8%b1%d8%b3/
 * Description: درگاه کامل بانک پاسارگاد. لطفا قبل از استفاده از طریق لینک دیدن خانه افزونه، تغییرات مورد نیاز افزونه را اعمال نمایید.
 * Version: 2.5
 * Author: Ala Alam Falaki
 * Author URI: http://AlaFalaki.ir
 * 
 */


session_start();

require_once("pasargadGatewayClass.php"); // Add Pasargad class To Plugin

add_action('plugins_loaded', 'WC_P', 0); // Make The Plugin Work...

function WC_P() {
    if ( !class_exists( 'WC_Payment_Gateway' ) ) return; // import your gate way class extends/
	
    class WC_full_ppayment extends WC_Payment_Gateway {
        public function __construct(){
        	
            $this -> id 			 	 = 'ppayment';
            $this -> method_title 	  	 = 'بانک پاسارگاد';
            $this -> has_fields 	   	 = false;
            $this -> init_form_fields();
            $this -> init_settings();
			
			$this -> title					= $this-> settings['title'];
			$this -> description			= $this-> settings['description'];
			$this -> merchantCode			= $this-> settings['merchantCode'];
			$this -> terminalCode			= $this-> settings['terminalCode'];
			$this -> redirect_page_id		= $this -> settings['redirect_page_id'];
 
			$this -> msg['message'] = "";
			$this -> msg['class'] = "";
 
			add_action('woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'check_ppayment_response' ) );

  		    if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) { // Compatibalization plugin for diffrent versions.
                add_action( 'woocommerce_update_options_payment_gateways_ppayment', array( &$this, 'process_admin_options' ) );
             } else {
                add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
            }
			 
			add_action('woocommerce_receipt_ppayment', array(&$this, 'receipt_page'));
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
                    'title' => ' شماره پذیرنده :',
                    'type' => 'text',
                    'description' => 'شما میتوانید این کد را از بانک ارائه دهنده درگاه دریافت نمایید .'),
                'terminalCode' => array(
                    'title' => ' شماره ترمینال :',
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
				'redirect_page_id' => array(
                    'title' => 'آدرس بازگشت',
                    'type' => 'select',
                    'options' => $this -> get_pages('صفحه مورد نظر را انتخاب نمایید'),
                    'description' => "صفحه‌ای که در صورت پرداخت موفق نشان داده می‌شود را نشان دهید."),
            );
        }
        public function admin_options(){
            echo '<h3>درگاه پرداخت بانک پاسارگاد</h3>';
			echo '<table class="form-table">';
			echo 
			// IRR
			// IRT
			$this -> generate_settings_html();
			echo '</table>';
			echo '
				<div>
					<a href="http://blog.alafalaki.ir/%d8%a7%d9%81%d8%b2%d9%88%d9%86%d9%87-%d9%be%d8%b1%d8%af%d8%a7%d8%ae%d8%aa-%d8%a8%d8%a7%d9%86%da%a9-%d9%be%d8%a7%d8%b3%d8%a7%d8%b1%da%af%d8%a7%d8%af-%d9%88%d9%88%da%a9%d8%a7%d9%85%d8%b1%d8%b3/">صفحه رسمی پلاگین + مستندات .</a><br />
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
			
            $order = new WC_Order($order_id);
			
			
            $callback 				= ($this -> redirect_page_id=="" || $this -> redirect_page_id==0)?get_site_url() . "/":get_permalink($this -> redirect_page_id);
			$callback 				= add_query_arg( 'wc-api', get_class( $this ), $callback );

			$merchantCode			= $this->merchantCode;
			$terminalCode			= $this->terminalCode;
			$order_total			= round($order -> order_total);

			if(get_woocommerce_currency() == "IRT")
			{
				$order_total = $order_total*10;
			}
			
				$gateWay = new PasargadBank_GateWay();
				date_default_timezone_set('Asia/Tehran');
				$gateWay->SendOrder($order_id,date("Y/m/d H:i:s"),$order_total, $merchantCode, $terminalCode, $callback);
        }
        
        /**
         * Process_payment Function.
         **/
        function process_payment($order_id){
            $order = new WC_Order($order_id);
            return array('result' => 'success', 'redirect' => add_query_arg('order',
                $order->id, add_query_arg('key', $order->order_key, $this->get_return_url($this->order)))
            );
        }
 

		/**
		 * Check for valid payu server callback
		 **/
		function check_ppayment_response(){
			global $woocommerce;
			require_once ("pasargadGatewayClass.php");

			$order_id 				= $_GET['iN'];
			$tref 					= $_GET['tref'];
			$order 					= new WC_Order($order_id);

			$merchantCode			= $this -> merchantCode;
			$terminalCode			= $this -> terminalCode;

			$OrderStatus 			= new PasargadBank_GateWay();

			$order_total			= round($order -> order_total);

			if(get_woocommerce_currency() == "IRT")
			{
				$order_total = $order_total*10;
			}
			
			$result = $OrderStatus->getOrder($_GET['tref']);

			if(($_SESSION['pasargadAmount']) == $order_total){

				if($result['resultObj']['result'] == "True"){ // Check the result.

					if($OrderStatus->verifyOrder($merchantCode, $terminalCode)){
						if($order->status !=='completed'){
			                    $this -> msg['class'] = 'woocommerce_message';
					            $this -> msg['message'] = "پرداخت شما با موفقیت انجام شد.";

								$order->payment_complete();
			                    $order->add_order_note('پرداخت موفق، کد پرداخت: '.$tref);
			                    $woocommerce->cart->empty_cart();
						}
					}else{
	                    $this -> msg['class'] = 'woocommerce_error';
			            $this -> msg['message'] = "پرداخت شما تایید نشد.";

						$order -> add_order_note('پرداخت تایید نشد.');
					}

				}else{
	                $this -> msg['class'] = 'woocommerce_error';
			        $this -> msg['message'] = "پرداخت ناموفق.";

					$order -> add_order_note('پرداخت ناموفق.');
				}

			}else{
	            $this -> msg['class'] = 'woocommerce_error';
			    $this -> msg['message'] = "پرداخت نامعتبر.";

				$order -> add_order_note('پرداخت نا معتبر.');
			}

			unset($_SESSION['pasargadAmount']);

			$redirect_url = ($this->redirect_page_id=="" || $this->redirect_page_id==0)?get_site_url() . "/":get_permalink($this->redirect_page_id);
			$redirect_url = add_query_arg( array('message'=> urlencode($this->msg['message']), 'class'=>$this->msg['class']), $redirect_url );
			wp_redirect( $redirect_url );
			exit;
		}

		// get all pages
		function get_pages($title = false, $indent = true) {
		    $wp_pages = get_pages('sort_column=menu_order');
		    $page_list = array();
		    if ($title) $page_list[] = $title;
		    foreach ($wp_pages as $page) {
		        $prefix = '';
		        // show indented child pages?
		        if ($indent) {
		            $has_parent = $page->post_parent;
		            while($has_parent) {
		                $prefix .=  ' - ';
		                $next_page = get_page($has_parent);
		                $has_parent = $next_page->post_parent;
		            }
		        }
		        // add to page list array array
		        $page_list[$page->ID] = $prefix . $page->post_title;
		    }
		    return $page_list;
		}

	}
    /**
     * Add the Gateway to WooCommerce.
     **/
    function woocommerce_add_ppayment_gateway($methods) {
        $methods[] = 'WC_full_ppayment';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_ppayment_gateway' );


}


if( isset($_GET['message']) )
{
	add_action('the_content', 'showMessage');

	function showMessage($content)
	{
		return '<div class="'.htmlentities($_GET['class']).'">'.urldecode($_GET['message']).'</div>'.$content;
	}
}


?>
