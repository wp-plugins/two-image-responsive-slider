<?php

/* 
 * Plugin Name: Two Image Responsive Slider
 * Description: This plugin displays slide show for responsive using FlexSlider2. You can set 2 images whose size is different, size for PC and smart phone.
 * Version: 1.0.0
 * Author: Hiroki Kanazawa
 * License: GPLv2
 * Domain Path: /languages
 */

define( 'TIRS_PLUGIN_FILE', __FILE__ );
define( 'TIRS_PLUGIN_PATH', dirname(__FILE__) );

register_activation_hook(__FILE__, 'two_image_slider_activate');

/* 管理画面専用ファイルあり */
if ( is_admin() ) {
	include_once 'admin/admin-class.php';
}

$tir_slider_class = new Two_Image_Responsive_Slider();
$tir_slider_class->register();

add_action( 'init', array( $tir_slider_class, 'init_register' ) );

/* オプション値の初期値を登録 */
if ( ! function_exists( 'two_image_slider_activate' ) ) {
	function two_image_slider_activate() {
		$key_and_init = array(
			'large-slider-width'             => 930,
			'large-slider-height'            => 300,
			'small-slider-width'             => 650,
			'small-slider-height'            => 300,
			'responsive-slider-change-width' => 650,
			'slider-animation'               => 'fade',
			'slider-direction'               => 'horizontal',
			'slider-animationLoop'           => true,
			'slider-slideshowSpeed'          => 7000,
			'slider-animationSpeed'          => 600,
			'slider-pauseOnHover'            => false,
			'slider-controlNav'              => true,
			'slider-directionNav'            => true,
		);
		foreach ( $key_and_init as $key => $value ) {
			if( ! get_option( $key ) ) {
				update_option( $key, $value );
			}
		}
	}
}


class Two_Image_Responsive_Slider {

	private $version = '';
	private $langs   = '';

	public function __construct() {
		$data = get_file_data(
			__FILE__,
			array( 'ver' => 'Version', 'langs' => 'Domain Path' )
		);
		$this->version = $data['ver'];
		$this->langs   = $data['langs'];
	}

	public function register() {
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
	}

	/* スライド画像はカスタム投稿タイプで登録 */
	public function init_register() {
		$labels = array(
			'name'               => _x( 'Slider', 'post type general name', 'tirs-text-domain' ),
			'singular_name'      => _x( 'Slide', 'post type singular name', 'tirs-text-domain' ),
			'menu_name'          => _x( 'Slider', 'admin menu', 'tirs-text-domain' ),
			'name_admin_bar'     => _x( 'Slider', 'add new on admin bar', 'tirs-text-domain' ),
			'add_new'            => _x( 'Add New Slide', 'slider', 'tirs-text-domain' ),
			'add_new_item'       => __( 'Add New', 'tirs-text-domain' ),
			'new_item'           => __( 'New Slide', 'tirs-text-domain' ),
			'edit_item'          => __( 'Edit', 'tirs-text-domain' ),
			'view_item'          => __( 'View Slider', 'tirs-text-domain' ),
			'all_items'          => __( 'All Slider', 'tirs-text-domain' ),
			'search_items'       => __( 'Search Slides', 'tirs-text-domain' ),
			'parent_item_colon'  => __( 'Parent Slider:', 'tirs-text-domain' ),
			'not_found'          => __( 'No books found.', 'tirs-text-domain' ),
			'not_found_in_trash' => __( 'No books found in Trash.', 'tirs-text-domain' ),
		);
		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => true,
			'menu_position'      => null,
			'supports'           => array( 'title', 'page-attributes' ),
		);
		register_post_type( 'responsive-slider', $args );
		
		/* オプションで設定した画像サイズにトリミングするように */
		if ( function_exists( 'add_image_size' ) ) {
			add_image_size( 'large-slider', get_option( 'large-slider-width' ), get_option( 'large-slider-height' ), array( 'center', 'center' ) );
			add_image_size( 'small-slider', get_option( 'small-slider-width' ), get_option( 'small-slider-height' ), array( 'center', 'center' ) );
		}
	}

	public function plugins_loaded() {
		load_plugin_textdomain(
			'tirs-text-domain',
			false,
			dirname( plugin_basename(__FILE__) ) . $this->langs
		);

		if ( is_admin() ) {
			global $pagenow;
			$tirs_admin = new Two_Image_Slider_Admin( $this->version );
			add_action( 'admin_menu', array( $tirs_admin, 'admin_menu' ) );
			add_action( 'admin_init', array( $tirs_admin, 'admin_init' ) );
			add_action( 'admin_notices', array( $tirs_admin, 'admin_notices' ) );
			if ( 'post-new.php' == $pagenow || 'post.php' == $pagenow ) {
				add_action( 'admin_menu', array( $tirs_admin, 'add_custom_box' ) );
				add_action( 'save_post', array( $tirs_admin, 'check_meta_value' ) );
			} elseif ( 'edit.php' == $pagenow ) {
				add_filter( 'manage_posts_columns', array( $tirs_admin, 'manage_posts_columns' ), 15 );
				add_filter( 'manage_pages_custom_column', array( $tirs_admin, 'manage_posts_custom_column' ), 10, 2 );
			}
		} else {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script' ) );
			add_action( 'wp_head', array( $this, 'slider_load_script' ) );
		}
	}
	
	public function enqueue_script() {
		wp_register_script(
				'jquery-flexslider',
				plugins_url( 'js/jquery.flexslider.js', __FILE__ ),
				array( 'jquery' ),
				'2.4.0',
				false
		);
		wp_enqueue_script( 'jquery-flexslider' );
		wp_register_style(
				'flexslider',
				plugins_url( 'js/flexslider.css', __FILE__ ),
				array(),
				'2.4.0',
				'all'
		);
		wp_enqueue_style( 'flexslider' );
	}
	
	public function slider_load_script() {
		?>

<script type="text/javascript">
	jQuery( function( $ ) {
		var slider = $(".flexslider");
		var slide_images = $(".flexslider img");
		if ( slider.length > 0 ) {
			if ( window.innerWidth > <?php echo esc_attr( get_option( 'responsive-slider-change-width' ) ); ?> ) {
				slide_images.each( function() {
					$( this ).attr( "src", $( this ).data( "large-slide" ) );
				} );
			} else {
				slide_images.each( function() {
					$( this ).attr( "src", $( this ).data( "small-slide" ) );
				} );
			}
			$( window ).load( function() {
				slider.flexslider( slider.data( "slider-option" ) );
			} );
		}
	} );
</script>

		<?php
	}
}


/* スライドをクエリで取得する際のパラメータ配列を生成する関数 */
if ( ! function_exists( 'get_responsive_slider_query_parameta' ) ) {
	function get_responsive_slider_query_parameta( $post_id = 0 ) {
		$args = array(
			'post_type'      => 'responsive-slider',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		);
		if ( is_admin() ) {
			$args['post_status'] = 'any';
		}
		if ( 0 == $post_id ) {
			$args['meta_query'] = array(
				'key'     => 'slide_parent',
				'value'   => 'top',
				'compare' => '!=',
			);
			if ( is_admin() ) {
				$args['post_parent'] = 0;
			}
		} else {
			$args['post_parent'] = $post_id;
			$args['orderby']     = 'menu_order';
			$args['order']       = 'ASC';
		}
		return $args;
	}
}


/* スライドのオプションを配列で取得する関数 */
if ( ! function_exists( 'get_responsive_slider_option' ) ) {
	function get_responsive_slider_option( $post_id = 0 ) {
		
		$slider_options = array(
			'animation'      => get_option( 'slider-animation' ),
			'direction'      => get_option( 'slider-direction' ),
			'animationLoop'  => get_option( 'slider-animationLoop' ),
			'slideshowSpeed' => get_option( 'slider-slideshowSpeed' ),
			'animationSpeed' => get_option( 'slider-animationSpeed' ),
			'pauseOnHover'   => get_option( 'slider-pauseOnHover' ),
			'controlNav'     => get_option( 'slider-controlNav' ),
			'directionNav'   => get_option( 'slider-directionNav' ),
		);
		
		if ( 0 != $post_id ) {
			$meta_values = get_post_meta( $post_id );
			foreach ( $meta_values as $meta_key => $meta_value ) {
				if ( isset( $slider_options[ $meta_key ] ) ) {
					$slider_options[ $meta_key ] = array_shift( $meta_value );
				}
			}
		}
		return $slider_options;
	}
}


/* スライダーを表示する関数 */
if ( ! function_exists( 'responsive_slider' ) ) {
	function responsive_slider( $slider_id = 0 ) {

		$slide_query = new WP_Query( get_responsive_slider_query_parameta( $slider_id ) );
		
		$slider = array(
			'html'   => '',
			'css'    => 'line-height: 1;',
			'option' => get_responsive_slider_option( $slider_id ),
		);

		if ( $slide_query->have_posts() ) {
			$html_format   = "<li><img class='slide-image-%s' alt='%s' data-large-slide='%s' data-small-slide='%s' /></li>";

			for ( $i = 0; $i < $slide_query->found_posts; $i++ ) {
				$slide_query->the_post();
				$post_id = get_the_ID();

				$large_id = get_post_meta( $post_id, 'large_slide_image', true );
				$small_id = get_post_meta( $post_id, 'small_slide_image', true );
				if ( '' != $large_id && '' == $small_id ) {
					$small_id = $large_id;
				}
				elseif ( '' == $large_id && '' != $small_id ) {
					$large_id = $small_id;
				}
				$large_images = wp_get_attachment_image_src( $large_id, 'large-slider' );
				$small_images = wp_get_attachment_image_src( $small_id, 'small-slider' );

				$slider['html'] .= sprintf( $html_format, $i, esc_attr( get_the_title() ), esc_url( is_array( $large_images ) ? $large_images[0] : '' ), esc_url( is_array( $small_images ) ? $small_images[0] : '' ) );
			}
			if ( $slide_query->found_posts == 1 ) {
				$slider['css'] .= ' margin-bottom: 0;';
			}
			wp_reset_postdata();
			?> 
			<div class='flexslider' data-slider-option='<?php echo json_encode( $slider['option'] ); ?>' style='<?php echo $slider['css']; ?>'>
				<ul class='slides'><?php echo $slider['html']; ?></ul>
			</div>
			<?php
		} else {
			echo '<!-- ' .  __('There is no slide', 'tirs-text-domain') . ' -->';
		}
	}
}


/* スライダーをショートコードで表示する場合の関数 */
if ( ! function_exists( 'shortcode_responsive_slider' ) ) {
	function shortcode_responsive_slider( $atts ) {
		$default_atts = array(
			'id' => 0,
		);
		$marged_atts = shortcode_atts( $default_atts, $atts );
		extract( $marged_atts );
		responsive_slider( $id );
	}
	
	add_shortcode( 'responsive-slider', 'shortcode_responsive_slider' );
}
