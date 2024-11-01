<?php
/**
 * Plugin Name:			Storefront Gridder
 * Plugin URI:			https://disenialia.com/storefront/storefront-gridder/storefront-gridder.zip
 * Description:			Add gridder to your Storefront and display a grid of products with an expandable zone for details.
 * Version:				1.0.3
 * Author:				ulisesfreitas
 * Author URI:			https://disenialia.com/
 * Requires at least:	4.0.0
 * Tested up to:		5.3.2
 *
 * Text Domain: storefront-gridder
 * Domain Path: /languages/
 *
 * @package Storefront_Gridder
 * @category Core
 * @author ulisesfreitas
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Returns the main instance of Storefront_Gridder to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Storefront_Gridder
 */
function Storefront_Gridder() {
	return Storefront_Gridder::instance();
} // End Storefront_Gridder()

Storefront_Gridder();

/**
 * Main Storefront_Gridder Class
 *
 * @class Storefront_Gridder
 * @version	1.0.0
 * @since 1.0.0
 * @package	Storefront_Gridder
 */
final class Storefront_Gridder {
	/**
	 * Storefront_Gridder The single instance of Storefront_Gridder.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $token;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $version;

	// Admin - Start
	/**
	 * The admin object.
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $admin;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct() {
		$this->token 			= 'storefront-gridder';
		$this->plugin_url 		= plugin_dir_url( __FILE__ );
		$this->plugin_path 		= plugin_dir_path( __FILE__ );
		$this->version 			= '1.0.0';

		register_activation_hook( __FILE__, array( $this, 'install' ) );
		add_action( 'init', array( $this, 'gridder_sf_load_plugin_textdomain' ) );
		add_action( 'init', array( $this, 'gridder_sf_setup' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'gridder_sf_plugin_links' ) );
	}

	/**
	 * Main Storefront_Gridder Instance
	 *
	 * Ensures only one instance of Storefront_Gridder is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Storefront_Gridder()
	 * @return Main Storefront_Gridder instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	} // End instance()

	/**
	 * Load the localisation file.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function gridder_sf_load_plugin_textdomain() {
		load_plugin_textdomain( 'storefront-gridder', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	}

	/**
	 * Plugin page links
	 *
	 * @since  1.0.0
	 */
	public function gridder_sf_plugin_links( $links ) {
		$plugin_links = array(
			'<a href="https://wordpress.org/support/plugin/storefront-gridder">' . __( 'Support', 'storefront-gridder' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Installation.
	 * Runs on activation. Logs the version number and assigns a notice message to a WordPress option.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install() {
		$this->_log_version_number();

		if( 'storefront' != basename( TEMPLATEPATH ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( 'Sorry, you can&rsquo;t activate this plugin unless you have installed the Storefront theme.' );
		}

		// get theme customizer url
		$url = admin_url() . 'customize.php?';
		$url .= 'url=' . urlencode( site_url() . '?storefront-customizer=true' ) ;
		$url .= '&return=' . urlencode( admin_url() . 'plugins.php' );
		$url .= '&storefront-customizer=true';

		$notices 		= get_option( 'gridder_sf_activation_notice', array() );
		$notices[]		= sprintf( __( '%sThanks for installing the Storefront Gridder. To get started, visit the %sCustomizer%s.%s %sOpen the Customizer%s', 'storefront-gridder' ), '<p>', '<a href="' . esc_url( $url ) . '">', '</a>', '</p>', '<p><a href="' . esc_url( $url ) . '" class="button button-primary">', '</a></p>' );

		update_option( 'gridder_sf_activation_notice', $notices );
	}

	/**
	 * Log the plugin version number.
	 * @access  private
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number() {
		// Log the version number.
		update_option( $this->token . '-version', $this->version );
	}

	/**
	 * Setup all the things.
	 * Only executes if Storefront or a child theme using Storefront as a parent is active and the extension specific filter returns true.
	 * Child themes can disable this extension using the storefront_top_bar_enabled filter
	 * @return void
	 */
	public function gridder_sf_setup() {
		$theme = wp_get_theme();

		if ( 'Storefront' == $theme->name || 'storefront' == $theme->template && apply_filters( 'storefront_gridder_supported', true ) ) {
			add_action( 'customize_register', array( $this, 'gridder_sf_customize_register' ) );

			add_filter( 'body_class', array( $this, 'gridder_sf_body_class' ) );

			add_action( 'storefront_homepage_before_product_categories', array( $this, 'gridder_sf_render' ) ,10); //storefront_homepage_before_product_categories //storefront_page

			add_action( 'admin_notices', array( $this, 'gridder_sf_customizer_notice' ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'gridder_sf_styles' ),	9 );
			add_action( 'wp_enqueue_scripts', array( $this, 'gridder_sf_scripts' ),	9 );

			add_filter( 'load_gridder_home' , array($this,'gridder_storefront_homepage_template_callback') );
		}
	}

	/**
	 * Admin notice
	 * Checks the notice setup in install(). If it exists display it then delete the option so it's not displayed again.
	 * @since   1.0.0
	 * @return  void
	 */
	public function gridder_sf_customizer_notice() {
		$notices = get_option( 'gridder_sf_activation_notice' );

		if ( $notices = get_option( 'gridder_sf_activation_notice' ) ) {

			foreach ( $notices as $notice ) {
				echo '<div class="updated">' . $notice . '</div>';
			}

			delete_option( 'gridder_sf_activation_notice' );
		}
	}

	/**
	 * Customizer Controls and settings
	 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
	 */
	public function gridder_sf_customize_register( $wp_customize ) {

		/**
		 * Add new section
		 */
		$wp_customize->add_section(
			'gridder_sf_custom' , array(
			    'title'      => __( 'Gridder Products Gallery', 'storefront-gridder' ),
			    'priority'   => 30,
			)
		);

		/**
		 * Add new settings
		 */

		$wp_customize->add_setting('gridder_number_of_products', array(
				'default' => apply_filters( 'gridder_number_of_products_text', 12),
		) );

		$wp_customize->add_setting('gridder_number_of_columns', array(
				'default' => apply_filters( 'gridder_number_of_columns_text', 4),
		) );

		$wp_customize->add_setting('gridder_orderby', array(
				'default' => apply_filters( 'gridder_orderby_text', 'name'),
		) );

		$wp_customize->add_setting('gridder_order', array(
				'default' => apply_filters( 'gridder_order_text', 'ASC'),
		) );

		$wp_customize->add_setting('gridder_display_price', array(
				'default' => apply_filters( 'gridder_display_price_val', 1 ),
		) );

		$wp_customize->add_setting('gridder_display_add_to_cart_btn', array(
				'default' => apply_filters( 'gridder_display_add_to_cart_btn_val', 1 ),
		) );

		$wp_customize->add_setting('gridder_display_view_product', array(
				'default' => apply_filters( 'gridder_display_view_product_val', 0 ),
		) );

		$wp_customize->add_setting(
			'gridder_mobile_display',
			array(
				'default' => 'show-on-mobile',
			)
		);


		/**
		 * Add controls and apply respective settings and hook on section
		 */


		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'gridder_number_of_products',
				array(
					'label'         => __( 'Number of products to show', 'storefront-gridder' ),
					'description' => __( 'Choose the number of products to show', 'storefront-gridder' ),
					'section'       => 'gridder_sf_custom',
					'settings'      => 'gridder_number_of_products',
					'type'          => 'number',
					'priority'    => 1,

					)
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'gridder_number_of_columns', array(
			'label'       => __( 'Number of columns', 'storefront-gridder' ),
			'description' => __( 'Choose the number of columns', 'storefront-gridder' ),
			'section'     => 'gridder_sf_custom',
			'settings'    => 'gridder_number_of_columns',
			'type'        => 'select',
			'priority'    => 2,
			'choices'     => array(
				'2'       =>  '6',
				'3'       =>  '4',
				'4' 	  =>  '3',
				'6' 	  =>  '2',
			),
		) ) );

		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'gridder_orderby', array(
			'label'       => __( 'Order by', 'storefront-gridder' ),
			'description' => __( 'Choose name or date', 'storefront-gridder' ),
			'section'     => 'gridder_sf_custom',
			'settings'    => 'gridder_orderby',
			'type'        => 'select',
			'priority'    => 3,
			'choices'     => array(
				'date'       =>  __('Date','storefront-gridder'),
				'name'       =>  __('Name','storefront-gridder'),

			),
		) ) );

		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'gridder_order', array(
			'label'       => __( 'Order', 'storefront-gridder' ),
			'description' => __( 'Choose Latest or Oldest', 'storefront-gridder' ),
			'section'     => 'gridder_sf_custom',
			'settings'    => 'gridder_order',
			'type'        => 'select',
			'priority'    => 4,
			'choices'     => array(
				'ASC'       =>  __('Latest','storefront-gridder'),
				'DESC'       =>  __('Oldest','storefront-gridder'),

			),
		) ) );

		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'gridder_display_price', array(
			'label'       => __( 'Display product price', 'storefront-gridder' ),
			'description' => __( 'Choose show or hide to display the product price', 'storefront-gridder' ),
			'section'     => 'gridder_sf_custom',
			'settings'    => 'gridder_display_price',
			'type'        => 'select',
			'priority'    => 5,
			'choices'     => array(
				'0'       =>  __('Hide','storefront-gridder'),
				'1'       =>  __('Show','storefront-gridder'),

			),
		) ) );

		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'gridder_display_add_to_cart_btn', array(
			'label'       => __( 'Display add to cart button(ajax)', 'storefront-gridder' ),
			'description' => __( 'Choose show or hide ajax add to cart button', 'storefront-gridder' ),
			'section'     => 'gridder_sf_custom',
			'settings'    => 'gridder_display_add_to_cart_btn',
			'type'        => 'select',
			'priority'    => 6,
			'choices'     => array(
				'0'       =>  __('Hide','storefront-gridder'),
				'1'       =>  __('Show','storefront-gridder'),

			),
		) ) );

		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'gridder_display_view_product', array(
			'label'       => __( 'Display View Product button', 'storefront-gridder' ),
			'description' => __( 'Choose show or hide for View Product button ', 'storefront-gridder' ),
			'section'     => 'gridder_sf_custom',
			'settings'    => 'gridder_display_view_product',
			'type'        => 'select',
			'priority'    => 7,
			'choices'     => array(
				'0'       =>  __('Hide','storefront-gridder'),
				'1'       =>  __('Show','storefront-gridder'),

			),
		) ) );

		$wp_customize->add_control(
		    new WP_Customize_Control(
		        $wp_customize,
		        'gridder_mobile_display',
		        array(
		            'label'          		=> __( 'Mobile Display', 'storefront-gridder' ),
		            'section'        		=> 'gridder_sf_custom',
		            'settings'       		=> 'gridder_mobile_display',
		            'priority'    	  		=> 8,
		            'type'           		=> 'select',
		            'choices'        		=> array(
		                'show-on-mobile'   	=> __( 'Show', 'storefront-gridder' ),
		                'hide-on-mobile'  	=> __( 'Hide', 'storefront-gridder' )
		            )
		        )
		    )
		);

	}




	/**
	 * External Styles
	 */
	function gridder_sf_styles() {

		wp_enqueue_style( 'storefront-gridder-css', plugins_url( '/assets/css/storefront-gridder.css', __FILE__ ) );
	}

	/**
	 * External Scripts
	 */
	function gridder_sf_scripts() {

		wp_enqueue_script('storefront-gridder-js', plugins_url( '/assets/js/storefront-gridder.js', __FILE__ ), array('jquery'), '1.0.0', false);
	}




	/**
	 * Storefront Top Bar Body Class
	 * Adds a class based on the extension name and any relevant settings.
	 */
	public function gridder_sf_body_class( $classes ) {
		$classes[] = 'storefront-gridder-active';

		return $classes;
	}

	/**
	 * Homepage callback
	 * @return bool
	 */
	public function gridder_storefront_homepage_template_callback() {
		//return is_page_template( 'template-homepage.php' ) ? true : false;
		return is_front_page() ? true : false;
	}


	/**
	 * Layout
	 * Adjusts the default Storefront layout when the plugin is active
	 */
	public function gridder_sf_render() {


		if ( is_woocommerce_activated() ) {



			$is_front_page = apply_filters('load_gridder_home', true);

			$products_number			= trim( get_theme_mod( 'gridder_number_of_products',  apply_filters( 'gridder_number_of_products_text', 12) ) );
			$number_of_columns 			= trim( get_theme_mod( 'gridder_number_of_columns',  apply_filters( 'gridder_number_of_columns_text', 4) ) );
			$orderby 					= trim( get_theme_mod( 'gridder_orderby',  apply_filters( 'gridder_orderby_text', 4) ) );
			$order 						= trim( get_theme_mod( 'gridder_order',  apply_filters( 'gridder_order_text', 4) ) );
			$display_price 				= get_theme_mod('gridder_display_price', apply_filters('gridder_display_price_val', 1 ) );
			$display_add_to_cart_btn 	= get_theme_mod('gridder_display_add_to_cart_btn', apply_filters('gridder_display_add_to_cart_btn_val', 1 ) );
			$display_view_product 		= get_theme_mod('gridder_display_view_product', apply_filters('gridder_display_view_product_val', 0 ) );
			$mobile_display 			= get_theme_mod('gridder_mobile_display', 'show-on-mobile' );

			$class_add_to_cart = "";
			if($display_add_to_cart_btn == 1){
				$class_add_to_cart = "add_to_cart_inline";
			}

			if($is_front_page):


			    $args = array(
			    			'post_type'			=> 'product',
			    			'post_status' 		=> 'publish',
			    			'posts_per_page' 	=> $products_number,
			    			'orderby' 			=> $orderby,
			    			'order' 			=> $order,
			    			);

			    $loop = new WP_Query( $args );

				echo '<section class="'. $mobile_display .'">';
				echo '<div class="woocommerce storefront-row">';
				echo '<ul class="gridder">';
			    while ( $loop->have_posts() ) : $loop->the_post();
			    	global $product;
					$product_meta = get_post_meta($loop->post->ID);
					$featured_image =  wp_get_attachment_image( $product_meta['_thumbnail_id'][0], 'medium' );
					echo '<li class="gridder-list storefront-col-'.$number_of_columns.'" data-griddercontent="#content-'.get_the_id().'">';
					echo $featured_image;
					echo '</li>';
					endwhile;
				echo '</ul>';
				wp_reset_query();

				while ( $loop->have_posts() ) : $loop->the_post();
					global $product;
					$product_obj = new WC_Product($loop->post->ID);
					$price = wc_price($product_obj->get_price_excluding_tax(1,$product_obj->get_price()));
					$product_meta = get_post_meta($loop->post->ID);
					$featured_image =  wp_get_attachment_image( $product_meta['_thumbnail_id'][0], 'medium' );
					echo '<div class="gridder-content" id="content-'.$loop->post->ID.'">';
	                echo '<div class="gridder-description storefront-row">';
	                echo '<div class="storefront-col-1"></div>';
	                echo '<div class="storefront-col-4">'.$featured_image.'</div>';
	                echo '<div class="storefront-col-7"><h2>'. get_the_title() .'</h2><br/><p class="gridder-product-description">';
	                echo substr(strip_tags($loop->post->post_content), 0, 300);
	                echo '<p></div>';
					echo '<p class="product woocommerce ' . $class_add_to_cart . '" style="none">';
					if($display_price == 1){
						echo '<a href="#" rel="nofollow" class="button product_type_simple alt">'.$price.'</a>';
					}
					if($display_add_to_cart_btn == 1){
						echo '<a rel="nofollow" href="'. do_shortcode('[add_to_cart_url id="'.$loop->post->ID.'"]'). '" data-quantity="1" data-product_id="'.$loop->post->ID.'" data-product_sku="" class="button product_type_simple add_to_cart_button ajax_add_to_cart">'. __('Add to cart', 'woocommerce') . '</a>';
					}
					if($display_view_product == 1){
						echo '<a rel="nofollow" href="'.get_permalink($loop->post->ID).'" class="button product_type_simple">'. __('View Product', 'woocommerce') . '</a>';
					}
					echo '</p>';
	                echo '</div>';
	                echo '</div>';
	                echo '<div class="clear"></div>';
				endwhile;
				wp_reset_query();
				echo '</div>';
				echo '</section>';

			endif;
		}
	}


} // End Class