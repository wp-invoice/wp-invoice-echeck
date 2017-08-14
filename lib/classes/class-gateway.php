<?php
/**
* Name: Authorize.net eCheck
* Class: wpi_echeck
* Internal Slug: wpi_echeck
* JS Slug: wpi_echeck
* Version: 1.0
* Description: Provides Authorize.net eCheck Gateway for WP-Invoice
 */

namespace UsabilityDynamics\WPIE {

    use net\authorize\api\contract\v1 as AnetAPI;
    use net\authorize\api\controller as AnetController;

    if (!class_exists('UsabilityDynamics\WPIE\Gateway') && class_exists('\wpi_gateway_base')) {

        class Gateway extends \wpi_gateway_base {

            /**
             * Construct
             */
            function __construct() {

                //** we do not call __construct here to prevent wrong $type to be generated */
                $this->type = 'wpi_echeck';

                //** Thats why we need to replicate all that was done in parent::__construct but properly for current case
                __('Customer Information', ud_get_wp_invoice()->domain);
                add_filter('sync_billing_update', array('wpi_gateway_base', 'sync_billing_filter'), 10, 3);
                add_filter('wpi_recurring_settings', create_function(' $gateways ', ' $gateways[] = "' . $this->type . '"; return $gateways; '));
                add_action('wpi_recurring_settings_' . $this->type, array($this, 'recurring_settings'));
                add_action('wpi_payment_fields_' . $this->type, array($this, 'wpi_payment_fields'));

                /**
                 * Opations array for settings page
                 */
                $this->options = array(
                    'name' => 'Authorize.net eCheck',
                    'public_name' => 'Authorize.net eCheck',
                    'allow' => true,
                    'default_option' => '',
                    'settings' => array(
                        'gateway_username' => array(
                            'label' => __( "API Login ID", ud_get_wp_invoice_echeck()->domain ),
                            'value' => '',
                            'description' => __( "Your credit card processor will provide you with a gateway username.", ud_get_wp_invoice_echeck()->domain )
                        ),
                        'gateway_tran_key' => array(
                            'label' => __( "API Transaction Key", ud_get_wp_invoice_echeck()->domain ),
                            'value' => "",
                            'description' => __( "You will be able to generate this in your credit card processor's control panel.", ud_get_wp_invoice_echeck()->domain )
                        ),
                        'gateway_test_mode' => array(
                            'label' => __( "Test / Live Mode", ud_get_wp_invoice_echeck()->domain ),
                            'type' => "select",
                            'data' => array(
                                "TRUE" => __( "Test - Do Not Process Transactions", ud_get_wp_invoice_echeck()->domain ),
                                "FALSE" => __( "Live - Process Transactions", ud_get_wp_invoice_echeck()->domain )
                            )
                        )
                    )
                );

                /**
                 * Fields list for frontend
                 */
                $this->front_end_fields = array(

                    'customer_information' => array(

                        'first_name'  => array(
                            'type'  => 'text',
                            'class' => 'text-input',
                            'name'  => 'cc_data[first_name]',
                            'label' => __( 'First Name', ud_get_wp_invoice_echeck()->domain )
                        ),

                        'last_name'   => array(
                            'type'  => 'text',
                            'class' => 'text-input',
                            'name'  => 'cc_data[last_name]',
                            'label' => __( 'Last Name', ud_get_wp_invoice_echeck()->domain )
                        ),

                        'company_name'   => array(
                            'type'  => 'text',
                            'class' => 'text-input',
                            'name'  => 'cc_data[company_name]',
                            'label' => __( 'Company', ud_get_wp_invoice_echeck()->domain )
                        ),

                        'user_email'  => array(
                            'type'  => 'text',
                            'class' => 'text-input',
                            'name'  => 'cc_data[user_email]',
                            'label' => __( 'User Email', ud_get_wp_invoice_echeck()->domain )
                        ),

                        'phonenumber' => array(
                            'type'  => 'text',
                            'class' => 'text-input',
                            'name'  => 'cc_data[phonenumber]',
                            'label' => __( 'Phone Number', ud_get_wp_invoice_echeck()->domain )
                        ),

                        'streetaddress'     => array(
                            'type'  => 'text',
                            'class' => 'text-input',
                            'name'  => 'cc_data[streetaddress]',
                            'label' => __( 'Address', ud_get_wp_invoice_echeck()->domain )
                        ),

                        'city'        => array(
                            'type'  => 'text',
                            'class' => 'text-input',
                            'name'  => 'cc_data[city]',
                            'label' => __( 'City', ud_get_wp_invoice_echeck()->domain )
                        ),

                        'state'       => array(
                            'type'   => 'text',
                            'class'  => 'text-input',
                            'name'   => 'cc_data[state]',
                            'label'  => __( 'State', ud_get_wp_invoice_echeck()->domain )
                        ),

                        'zip'         => array(
                            'type'  => 'text',
                            'class' => 'text-input',
                            'name'  => 'cc_data[zip]',
                            'label' => __( 'Zip', ud_get_wp_invoice_echeck()->domain )
                        ),

                        'country'     => array(
                            'type'   => 'text',
                            'class'  => 'text-input',
                            'name'   => 'cc_data[country]',
                            'label'  => __( 'Country', ud_get_wp_invoice_echeck()->domain )
                        )

                    ),

                    'billing_information' => array(

                        'bank_aba_code'    => array(
                            'type'   => 'text',
                            'class'  => 'bank_aba_code input_field text-input',
                            'name'   => 'cc_data[bank_aba_code]',
                            'label'  => __( 'Routing Number', ud_get_wp_invoice_echeck()->domain )
                        ),

                        'bank_acct_num'   => array(
                            'type'   => 'text',
                            'class'  => 'text-input bank_acct_num',
                            'name'   => 'cc_data[bank_acct_num]',
                            'label'  => __( 'Account Number', ud_get_wp_invoice_echeck()->domain )
                        ),

                        'bank_acct_type'    => array(
                            'type'   => 'select',
                            'class'  => 'text-input bank_acct_type',
                            'name'   => 'cc_data[bank_acct_type]',
                            'label'  => __( 'Account Type', ud_get_wp_invoice_echeck()->domain ),
                            'values' => serialize(array(
                                'checking' => 'Checking',
                                'businesschecking' => 'Business Checking',
                                'savings' => 'Savings'
                            ))
                        ),

                        'bank_name'   => array(
                            'type'   => 'text',
                            'class'  => 'text-input bank_name',
                            'name'   => 'cc_data[bank_name]',
                            'label'  => __( 'Bank Name', ud_get_wp_invoice_echeck()->domain )
                        ),

                        'bank_acct_name' => array(
                            'type'   => 'text',
                            'class'  => 'text-input bank_acct_name',
                            'name'   => 'cc_data[bank_acct_name]',
                            'label'  => __( 'Bank Account Name', ud_get_wp_invoice_echeck()->domain )
                        ),

                        'echeck_type' => array(
                            'type'   => 'select',
                            'class'  => 'text-input echeck_type',
                            'name'   => 'cc_data[echeck_type]',
                            'label'  => __( 'Check Type', ud_get_wp_invoice_echeck()->domain ),
                            'values' => serialize(array(
                                'ARC' => 'ARC',
                                'BOC' => 'BOC',
                                'CCD' => 'CCD',
                                'PPD' => 'PPD',
                                'TEL' => 'TEL',
                                'WEB' => 'WEB'
                            ))
                        ),

                        'bank_check_number' => array(
                            'type'   => 'text',
                            'class'  => 'text-input bank_check_number',
                            'name'   => 'cc_data[bank_check_number]',
                            'label'  => __( 'Check Number', ud_get_wp_invoice_echeck()->domain ),
                        )

                    )

                );

                add_action( 'wpi_echeck_user_meta_updated', array( $this, 'user_meta_updated' ) );
            }

            /**
             * Override function
             * @param string $args
             * @param bool $from_ajax
             */
            function frontend_display($args = '', $from_ajax = false) {
                global $wpdb, $wpi_settings, $invoice;
                //** Setup defaults, and extract the variables */
                $defaults = array();
                extract(wp_parse_args($args, $defaults), EXTR_SKIP);
                //** Include the template file required */
                $process_payment_nonce = wp_create_nonce( "process-payment" );

                include( ud_get_wp_invoice()->path('lib/gateways/templates/', 'dir') . 'payment_header.tpl.php' );
                include( ud_get_wp_invoice_echeck()->path('static/views/', 'dir') . 'echeck_frontend.php' );
                include( ud_get_wp_invoice()->path('lib/gateways/templates/', 'dir') . 'payment_footer.tpl.php');
            }

            /**
             * @param $this_invoice
             */
            public function recurring_settings( $this_invoice ) {
                ?>
                <h4><?php _e('eCheck Recurring Billing', ud_get_wp_invoice_echeck()->domain); ?></h4>
                <p><?php _e('Currently Authorize.net eCheck gateway does not support Recurring Billing', ud_get_wp_invoice_echeck()->domain); ?></p>
                <?php
            }

            /**
             * Render fields
             *
             * @param array $invoice
             */
            public function wpi_payment_fields( $invoice ) {

                $this->front_end_fields = apply_filters( 'wpi_crm_custom_fields', $this->front_end_fields, 'cc_data' );

                if ( !empty( $this->front_end_fields ) ) {
                  // For each section
                  foreach( $this->front_end_fields as $key => $value ) {
                    // If section is not empty
                    if ( !empty( $this->front_end_fields[ $key ] ) ) {
                            $html = '';
                            ob_start();

                            ?>
                            <ul class="wpi_checkout_block">
                                <li class="section_title"><?php _e( ucwords( str_replace('_', ' ', $key) ), ud_get_wp_invoice()->domain); ?></li>
                            <?php
                            $html = ob_get_clean();
                            echo $html;
                      // For each field
                      foreach( $value as $field_slug => $field_data ) {
                        //** Change field properties if we need */
                        $field_data = apply_filters('wpi_payment_form_styles', $field_data, $field_slug, 'wpi_echeck');
                        $html = '';
                        ob_start();

                        switch ( $field_data['type'] ) {
                          case self::TEXT_INPUT_TYPE:
                            ?>

                            <li class="wpi_checkout_row">
                              <div class="control-group">
                                <label class="control-label" for="<?php echo esc_attr( $field_slug ); ?>"><?php _e($field_data['label'], ud_get_wp_invoice()->domain); ?></label>
                                <div class="controls">
                                  <input id="<?php echo esc_attr( $field_slug ); ?>" placeholder="<?php echo esc_attr( !empty($field_data['placeholder']) ? $field_data['placeholder'] : '' ); ?>" type="<?php echo esc_attr( $field_data['type'] ); ?>" class="<?php echo esc_attr( $field_data['class'] ); ?>"  name="<?php echo esc_attr( $field_data['name'] ); ?>" value="<?php echo isset($field_data['value'])?$field_data['value']:(!empty($invoice['user_data'][$field_slug])?$invoice['user_data'][$field_slug]:'');?>" />
                                </div>
                              </div>
                            </li>

                            <?php

                            $html = ob_get_clean();

                            break;

                          case self::SELECT_INPUT_TYPE:

                            ?>

                            <li class="wpi_checkout_row">
                              <label for="<?php echo esc_attr( $field_slug ); ?>"><?php _e($field_data['label'], ud_get_wp_invoice()->domain); ?></label>
                              <?php echo \WPI_UI::select("name={$field_data['name']}&values={$field_data['values']}&id={$field_slug}&class={$field_data['class']}"); ?>
                            </li>

                            <?php

                            $html = ob_get_clean();

                            break;

                        case self::CHECKBOX_INPUT_TYPE:

                            ?>

                            <li class="wpi_checkout_row">
                            <div class="control-group">
                            <?php
                            $values = maybe_unserialize($field_data['values']);
                            $k_cnt = 0;
                            foreach ($values as $k_val => $v_val) {
                                if($k_cnt==0){ ?>
                                  <label class="control-label" for="<?php echo esc_attr( $field_slug ); ?>"><?php _e($field_data['label'], ud_get_wp_invoice()->domain); ?></label>
                                <?php }else{ ?>
                                    </div></li><li class="wpi_checkout_row"><div class="control-group"><label class="control-label" >&nbsp;</label>
                                <?php } ?>
                                <div class="controls" for="<?php echo $k_val; ?>" >
                                <?php
                                    $k_cnt++;
                                    echo WPI_UI::checkbox("name={$k_val}&id={$k_val}&class={$field_data['class']}&group={$field_slug}&label={$v_val}"); ?>
                                </div>
                            <?php } ?>
                             </div>
                            </li>

                            <?php

                            $html = ob_get_clean();

                            break;

                          case self::TEXTAREA_INPUT_TYPE:

                            ?>
                            <li class="wpi_checkout_row">
                              <label for="<?php echo esc_attr( $field_slug ); ?>"><?php _e($field_data['label'], ud_get_wp_invoice()->domain); ?></label>
                              <?php echo WPI_UI::textarea("name={$field_data['name']}&values={$field_data['values']}&id={$field_slug}&class={$field_data['class']}"); ?>
                            </li>

                            <?php

                            $html = ob_get_clean();

                            break;

                          case self::RECAPTCHA_INPUT_TYPE:
                            $this->display_recaptcha($field_data);

                            break;

                          default:
                            break;

                        }

                        echo $html;

                      }
                        echo '</ul>';
                    }
                  }

                }

            }

            /**
             * Overrided process payment for Authorize.net
             *
             * @global object $invoice
             * @global array $wpi_settings
             * @param array $data
             */
            static function process_payment($data=null) {
                check_ajax_referer( 'process-payment', 'security' );

                global $invoice;
                $response = array();

                $wp_user_id = $invoice['user_data']['ID'];
                $input_data = is_null($data) ? $_REQUEST['cc_data'] : $data;

                update_user_meta($wp_user_id, 'last_name', $input_data['last_name']);
                update_user_meta($wp_user_id, 'first_name', $input_data['first_name']);
                update_user_meta($wp_user_id, 'city', $input_data['city']);
                update_user_meta($wp_user_id, 'company_name', $input_data['company_name']);
                update_user_meta($wp_user_id, 'state', $input_data['state']);
                update_user_meta($wp_user_id, 'zip', $input_data['zip']);
                update_user_meta($wp_user_id, 'streetaddress', $input_data['streetaddress']);
                update_user_meta($wp_user_id, 'phonenumber', $input_data['phonenumber']);
                update_user_meta($wp_user_id, 'country', $input_data['country']);

                do_action( 'wpi_echeck_user_meta_updated', $input_data );

                $settings = $invoice[ 'billing' ][ 'wpi_echeck' ][ 'settings' ];

                if ($invoice['deposit_amount'] > 0) {
                  $amount = (float) $input_data['amount'];
                  if (((float) $input_data['amount']) > $invoice['net']) {
                    $amount = $invoice['net'];
                  }
                  if (((float) $input_data['amount']) < $invoice['deposit_amount']) {
                    $amount = $invoice['deposit_amount'];
                  }
                } else {
                  $amount = $invoice['net'];
                }

                $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
                $merchantAuthentication->setName( $settings['gateway_username']['value'] );
                $merchantAuthentication->setTransactionKey( $settings['gateway_tran_key']['value'] );

                // Create the payment data for a Bank Account
                $bankAccount = new AnetAPI\BankAccountType();
                $bankAccount->setAccountType( $input_data['bank_acct_type'] );
                $bankAccount->setEcheckType( $input_data['echeck_type'] );
                $bankAccount->setRoutingNumber( $input_data['bank_aba_code'] );
                $bankAccount->setAccountNumber( $input_data['bank_acct_num'] );
                $bankAccount->setNameOnAccount( $input_data['bank_acct_name'] );
                $bankAccount->setBankName( $input_data['bank_name'] );

                if ( !empty( $input_data['bank_check_number'] ) ) {
                    $bankAccount->setCheckNumber( $input_data['bank_check_number'] );
                }

                // Bill to info
                $customerAddress = new AnetAPI\CustomerAddressType();
                $customerAddress->setFirstName( $input_data['first_name'] );
                $customerAddress->setLastName( $input_data['last_name'] );
                $customerAddress->setCompany( $input_data['company_name'] );
                $customerAddress->setAddress( $input_data['streetaddress'] );
                $customerAddress->setCity( $input_data['city'] );
                $customerAddress->setState( $input_data['state'] );
                $customerAddress->setZip( $input_data['zip'] );
                $customerAddress->setCountry( $input_data['country'] );
                $customerAddress->setPhoneNumber( $input_data['phonenumber'] );

                // Create bank payment
                $bankPayment = new AnetAPI\PaymentType();
                $bankPayment->setBankAccount($bankAccount);

                // Create Order
                $order = new AnetAPI\OrderType();
                $order->setInvoiceNumber( $invoice['invoice_id'] );
                $order->setDescription( $invoice['post_title'] );

                $transactionRequestType = new AnetAPI\TransactionRequestType();
                $transactionRequestType->setTransactionType("authCaptureTransaction");
                $transactionRequestType->setAmount( $amount );
                $transactionRequestType->setCurrencyCode( $input_data['currency_code'] );
                $transactionRequestType->setPayment( $bankPayment );
                $transactionRequestType->setOrder( $order );
                $transactionRequestType->setBillTo( $customerAddress );

                $request = new AnetAPI\CreateTransactionRequest();
                $request->setMerchantAuthentication($merchantAuthentication);
                $request->setRefId( $refId = 'ref'.time() );
                $request->setTransactionRequest($transactionRequestType);

                $mode = $settings['gateway_test_mode']['value'] == 'TRUE'
                    ? \net\authorize\api\constants\ANetEnvironment::SANDBOX
                    : \net\authorize\api\constants\ANetEnvironment::PRODUCTION;

                $controller = new AnetController\CreateTransactionController($request);

                $res = $controller->executeWithApiResponse( $mode );

                if ( null == $res ) {
                    $response['success'] = false;
                    $response['error'] = true;
                    $data['messages'][] = __( '001: Unknown eCheck.net Payment Error Occurred. Please contact support.' );
                    $response['data'] = $data;
                    die( json_encode( $response ) );
                }

                if ( $res->getMessages()->getResultCode() == 'Ok' ) {

                    $result = $res->getTransactionResponse();

                    if ($result != null && $result->getMessages() != null) {

                        $invoice_obj = new \WPI_Invoice();
                        $invoice_obj->load_invoice("id={$invoice['invoice_id']}");

                        $event_note = \WPI_Functions::currency_format($amount, $invoice['invoice_id']) . " paid via eCheck.net";
                        $event_amount = $amount;
                        $event_type = 'add_payment';

                        $event_note = urlencode($event_note);

                        $invoice_obj->add_entry("attribute=balance&note=$event_note&amount=$event_amount&type=$event_type");

                        $success = "Successfully processed by {$_SERVER['REMOTE_ADDR']}";
                        $invoice_obj->add_entry("attribute=invoice&note=$success&type=update");

                        $payer_email = "eCheck.net Payer email: {$input_data['user_email']}";
                        $invoice_obj->add_entry("attribute=invoice&note=$payer_email&type=update");

                        $trans_id = "eCheck.net Transaction ID: {$result->getTransId()}";
                        $invoice_obj->add_entry("attribute=invoice&note=$trans_id&type=update");

                        $invoice_obj->save_invoice();

                        wp_invoice_mark_as_paid($invoice['invoice_id'], $check = true);

                        parent::successful_payment( $invoice_obj );
                        parent::successful_payment_webhook( $invoice_obj );

                        send_notification( $invoice );

                        $data['messages'][] = $result->getMessages()[0]->getDescription();
                        $response['success'] = true;
                        $response['error'] = false;
                        $response['data'] = $data;
                        die( json_encode( $response ) );

                    } else {
                        $response['success'] = false;
                        $response['error'] = true;

                        if ($result->getErrors() != null) {
                            $data['messages'][] = $result->getErrors()[0]->getErrorText();
                        } else {
                            $data['messages'][] = __( '002: Unknown eCheck.net Payment Error Occurred. Please contact support.' );
                        }

                        $response['data'] = $data;
                        die( json_encode( $response ) );
                    }

                } else {
                    $result = $res->getTransactionResponse();

                    $response['success'] = false;
                    $response['error'] = true;

                    if ($result != null && $result->getErrors() != null) {
                        $data['messages'][] = $result->getErrors()[0]->getErrorText();
                    } else {
                        $data['messages'][] = $res->getMessages()->getMessage()[0]->getText();
                    }

                    $response['data'] = $data;
                    die( json_encode( $response ) );
                }


            }

        }
    }
}