<?php
/**
 * Plugin Name: Advanced Pods AJAX Filter
 * Description: V10 - Recreating V4 UI with specific Pods mapping.
 * Version: 1.0.0
 * Author: Jules
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'APAF_PATH', plugin_dir_path( __FILE__ ) );
define( 'APAF_URL', plugin_dir_url( __FILE__ ) );

class Advanced_Pods_Ajax_Filter {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_shortcode( 'advanced_pods_filter', array( $this, 'render_shortcode' ) );
		add_action( 'wp_ajax_apaf_filter_properties', array( $this, 'ajax_handler' ) );
		add_action( 'wp_ajax_nopriv_apaf_filter_properties', array( $this, 'ajax_handler' ) );
	}

	public function enqueue_scripts() {
		// Enqueue CSS
		wp_enqueue_style( 'select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css' );
		wp_enqueue_style( 'nouislider-css', 'https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.0/nouislider.min.css' );
		wp_enqueue_style( 'apaf-style', APAF_URL . 'assets/css/style.css', array(), '1.0.0' );

		// Enqueue JS
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), '4.1.0', true );
		wp_enqueue_script( 'nouislider-js', 'https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.0/nouislider.min.js', array(), '15.7.0', true );
		wp_enqueue_script( 'apaf-script', APAF_URL . 'assets/js/script.js', array( 'jquery', 'select2-js', 'nouislider-js' ), '1.0.0', true );

		wp_localize_script( 'apaf-script', 'apaf_vars', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'apaf_nonce' )
		) );
	}

	public function render_shortcode( $atts ) {
		ob_start();
		include APAF_PATH . 'templates/search-interface.php';
		return ob_get_clean();
	}

	public function ajax_handler() {
		check_ajax_referer( 'apaf_nonce', 'nonce' );

		$pretensao = isset( $_POST['pretensao'] ) ? sanitize_text_field( $_POST['pretensao'] ) : '';
		$cidade    = isset( $_POST['cidade'] ) ? sanitize_text_field( $_POST['cidade'] ) : '';
		$bairro    = isset( $_POST['bairro'] ) ? sanitize_text_field( $_POST['bairro'] ) : '';
		// $zone   = isset( $_POST['zone'] ) ? sanitize_text_field( $_POST['zone'] ) : ''; // Not mapped in DB rules
		$types     = isset( $_POST['property_types'] ) ? array_map( 'sanitize_text_field', $_POST['property_types'] ) : array();

		$quartos   = isset( $_POST['quartos'] ) ? sanitize_text_field( $_POST['quartos'] ) : '';
		$banheiros = isset( $_POST['banheiros'] ) ? sanitize_text_field( $_POST['banheiros'] ) : '';
		$vagas     = isset( $_POST['vagas'] ) ? sanitize_text_field( $_POST['vagas'] ) : '';

		$min_price = isset( $_POST['min_price'] ) ? floatval( $_POST['min_price'] ) : '';
		$max_price = isset( $_POST['max_price'] ) ? floatval( $_POST['max_price'] ) : '';

		$financing = isset( $_POST['financing'] ) ? intval( $_POST['financing'] ) : 0;

		$args = array(
			'post_type'      => 'imovel',
			'posts_per_page' => -1,
			'tax_query'      => array( 'relation' => 'AND' ),
			'meta_query'     => array( 'relation' => 'AND' ),
		);

		// Taxonomies
		if ( ! empty( $pretensao ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'pretensao',
				'field'    => 'slug',
				'terms'    => $pretensao,
			);
		}
		if ( ! empty( $cidade ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'cidade',
				'field'    => 'slug',
				'terms'    => $cidade,
			);
		}
		if ( ! empty( $bairro ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'bairro',
				'field'    => 'slug',
				'terms'    => $bairro,
			);
		}
		if ( ! empty( $types ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'tipo_imovel',
				'field'    => 'slug',
				'terms'    => $types,
			);
		}

		// Meta Fields
		// Price (BETWEEN)
		if ( $min_price !== '' && $max_price !== '' && $max_price > 0 ) {
			$args['meta_query'][] = array(
				'key'     => 'preco_venda',
				'value'   => array( $min_price, $max_price ),
				'type'    => 'NUMERIC', // Currency usually numeric
				'compare' => 'BETWEEN',
			);
		} elseif ( $min_price !== '' ) {
             $args['meta_query'][] = array(
				'key'     => 'preco_venda',
				'value'   => $min_price,
				'type'    => 'NUMERIC',
				'compare' => '>=',
			);
        }

		// Numeric Specs - Exact match or "at least"?
		// "Square Buttons" usually imply specific selection.
        // Prompt says "Cast as NUMERIC in WP_Query".
        // I will implement as ">=" (At least X rooms) which is standard for real estate filters,
        // unless it's "4+", but usually clicking "2" means "2 or more" or "exactly 2".
        // Let's assume ">=" is safer for "search" unless user wants exactly 2.
        // Given the "4+" label in my template, I'll treat it as ">=".
		if ( ! empty( $quartos ) ) {
			$args['meta_query'][] = array(
				'key'     => 'quartos',
				'value'   => $quartos,
				'type'    => 'NUMERIC',
				'compare' => '>=',
			);
		}
		if ( ! empty( $banheiros ) ) {
			$args['meta_query'][] = array(
				'key'     => 'banheiros',
				'value'   => $banheiros,
				'type'    => 'NUMERIC',
				'compare' => '>=',
			);
		}
		if ( ! empty( $vagas ) ) {
			$args['meta_query'][] = array(
				'key'     => 'vagas_de_garagem',
				'value'   => $vagas,
				'type'    => 'NUMERIC',
				'compare' => '>=',
			);
		}

		// Financing (Yes/No)
        // Key `aceita_financiamento`. Type: Yes/No.
        // Pods Yes/No usually stores '1' or '0'.
		if ( $financing ) {
			$args['meta_query'][] = array(
				'key'     => 'aceita_financiamento',
				'value'   => '1',
				'compare' => '=', // Or 'LIKE' if stored weirdly, but usually '1'
			);
		}

		$query = new WP_Query( $args );
		$html = '';

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				$title = get_the_title();
				$price = get_post_meta( get_the_ID(), 'preco_venda', true );
                if ( is_numeric( $price ) ) {
                    $price_fmt = 'R$ ' . number_format( $price, 2, ',', '.' );
                } else {
                    $price_fmt = $price; // Fallback
                }

                // Image
                if ( has_post_thumbnail() ) {
                    $img_url = get_the_post_thumbnail_url( get_the_ID(), 'medium' );
                } else {
                    $img_url = 'https://via.placeholder.com/300x200?text=Sem+Imagem';
                }

                // Specs
                $q = get_post_meta( get_the_ID(), 'quartos', true );
                $b = get_post_meta( get_the_ID(), 'banheiros', true );
                $v = get_post_meta( get_the_ID(), 'vagas_de_garagem', true );

				$html .= '<div class="apaf-card">';
				$html .= '<div class="apaf-card-img" style="background-image: url(' . esc_url( $img_url ) . ');"></div>';
				$html .= '<div class="apaf-card-body">';
				$html .= '<h4 class="apaf-card-title">' . esc_html( $title ) . '</h4>';
				$html .= '<span class="apaf-card-price">' . esc_html( $price_fmt ) . '</span>';
				$html .= '<div class="apaf-card-specs">';
				if($q) $html .= '<span>' . esc_html( $q ) . ' Quartos</span>';
				if($b) $html .= '<span>' . esc_html( $b ) . ' Banheiros</span>';
				if($v) $html .= '<span>' . esc_html( $v ) . ' Vagas</span>';
				$html .= '</div>'; // specs
				$html .= '</div>'; // body
				$html .= '</div>'; // card
			}
			wp_reset_postdata();
		} else {
			$html .= '<p>Nenhum imóvel encontrado.</p>';
		}

		wp_send_json_success( array( 'html' => $html ) );
	}
}

new Advanced_Pods_Ajax_Filter();
