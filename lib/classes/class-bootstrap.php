<?php
/**
 * Bootstrap
 *
 * @since 1.0.0
 */
namespace UsabilityDynamics\WPIE {

  if( !class_exists( 'UsabilityDynamics\WPIE\Bootstrap' ) ) {

    final class Bootstrap extends \UsabilityDynamics\WP\Bootstrap_Plugin {
      
      /**
       * Singleton Instance Reference.
       *
       * @protected
       * @static
       * @property $instance
       * @type UsabilityDynamics\WPIE\Bootstrap object
       */
      protected static $instance = null;
      
      /**
       * Instantaite class.
       */
      public function init() {

        add_action('wpi_pre_init', array($this, 'load_gateway'));
        
      }

      /**
       * Do load gateway
       */
      public function load_gateway() {
        global $wpi_settings;

        $default_headers = array(
            'Name' => 'Name',
            'Version' => 'Version',
            'Description' => 'Description'
        );

        $slug = 'wpi_echeck';

        $file = ud_get_wp_invoice_echeck()->path('lib/classes/', 'dir') . 'class-gateway.php';

        $plugin_data = get_file_data( $file, $default_headers, 'plugin' );
        $wpi_settings['installed_gateways'][$slug]['name'] = $plugin_data['Name'];
        $wpi_settings['installed_gateways'][$slug]['version'] = $plugin_data['Version'];
        $wpi_settings['installed_gateways'][$slug]['description'] = $plugin_data['Description'];

        if (WP_DEBUG) {
          include_once( $file );
        } else {
          @include_once( $file );
        }

        //** Initialize the object, then update the billing permissions to show whats in the object */
        eval("\$wpi_settings['installed_gateways']['" . $slug . "']['object'] = new UsabilityDynamics\WPIE\Gateway();");

        //** Sync our options */
        \wpi_gateway_base::sync_billing_objects();
      }
      
      /**
       * Plugin Activation
       *
       */
      public function activate() {}
      
      /**
       * Plugin Deactivation
       *
       */
      public function deactivate() {}

    }

  }

}
