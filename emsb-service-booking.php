<?php
/*
 * Plugin Name:	Doctor Appointment Booking Plugin - EMSB
 * Description: Allow your customers to book your service like appointment, event, reservation, etc. Manage your bookings through wp admin dashboard. No overlap, automated email, no headache.
 * Author: 		emsbservicebooking
 * Version:		1.2.2
 * Author URI: 	www.e-motahar.com
 * License:     GNU GENERAL PUBLIC LICENSE Version 3,
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: emsb-service-booking
*/

namespace emsb_service_booking_plugin;
/**
 * Use namespace to avoid conflict
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}




//************** Starts Custom post type service booking with meta boxes ***************//
/**
 * Class emsb_service_booking_plugin_base_class
 * @package emsb_service_booking_plugin
 *
 * Use actual name of post type for
 * easy readability.
 *
 * Potential conflicts removed by namespace
 */
if ( !class_exists( 'emsb_service_booking_plugin_base_class' ) ) {
    class emsb_service_booking_plugin_base_class {

        /**
         * When class is instantiated
         */
        public function __construct() {
            
            add_action('init', array($this, 'emsb_service_post_type'));// Register the post type
            add_action('init', array($this, 'emsb_add_texonomy_to_booking_service'));// Register texonomy
            add_action('add_meta_boxes', array($this,'emsb_add_meta_boxes_to_booking_service')); //add meta boxes
            add_action('save_post', array($this,'emsb_save_all_posts_types_meta_fields_meta')); //add meta boxes
            add_filter( 'archive_template',  array($this,'emsb_get_archive_template') ) ; 
            add_filter( 'single_template',  array($this,'emsb_get_single_template') ) ; 
            add_filter( 'use_block_editor_for_post_type', array($this,'emsb_disable_gutenberg_pages'), 10, 2 );
            add_action('init', array($this, 'emsb_enqueue_public_scripts'));
            add_action( 'wp_enqueue_scripts', array($this,'emsb_enqueue_public_scripts')  );
            
            
        }

        /**
         * Register post type
         */

        public function emsb_service_post_type() {

                $type               = 'emsb_service';
                $slug               = 'emsb_service';
                $name               = 'Services';
                $singular_name      = 'Booking Service';
                
                $labels = array(
                    'name'                  => $name,
                    'singular_name'         => $singular_name,
                    'add_new'               => __('Add New', 'emsb-service-booking'),
                    'add_new_item'          => 'Add New '   . $singular_name,
                    'edit_item'             => 'Edit '      . $singular_name,
                    'new_item'              => 'New '       . $singular_name,
                    'all_items'             => 'All '       . $name,
                    'view_item'             => 'View '      . $name,
                    'search_items'          => 'Search '    . $name,
                    'not_found'             => 'No '        . strtolower($name) . ' found',
                    'not_found_in_trash'    => 'No '        . strtolower($name) . ' found in Trash',
                    'parent_item_colon'     => '',
                    'menu_name'             => $name
                );
                $args = array(
                    'labels'                => $labels,
                    'public'                => true,
                    'publicly_queryable'    => true,
                    'show_ui'               => true,
                    'show_in_menu'          => false,
                    'show_in_admin_bar'     => true,
                    'query_var'             => true,
                    'rewrite'               => array( 
                                                    'slug'        => 'marcacao',
                                                    'with_front'  => true,
                                                    'pages'       => true,
                                                    'feeds'       => true
                                                ),
                    'capability_type'       => 'post',
                    'has_archive'           => true,
                    'hierarchical'          => true,
                    'menu_icon'             => 'dashicons-buddicons-buddypress-logo',
                    'menu_position'         => 26,
                    'supports'              => array( 'title', 'thumbnail'),
                    'show_in_rest'          => true
                );
                register_post_type( $type, $args );
                flush_rewrite_rules();
        }

        //Add texonomy
        public function emsb_add_texonomy_to_booking_service(){
            $labels = array(
                'name'              => _x( 'Especialidades', 'taxonomy general name' ),
                'singular_name'     => _x( 'Especialidade', 'taxonomy singular name' ),
                'search_items'      => __( 'Procurar Especialidades' ),
                'all_items'         => __( 'Especialidades' ),
                'parent_item'       => __( 'Especialidades Existentes' ),
                'parent_item_colon' => __( 'Parent Service Type:' ),
                'edit_item'         => __( 'Editar Especialidade' ), 
                'update_item'       => __( 'Atualizar Especialidade' ),
                'add_new_item'      => __( 'Adicionar Especialidade' ),
                'new_item_name'     => __( 'Nova Especialidade' ),
                'menu_name'         => __( 'Especialidades' ),
              );
              $args = array(
                'labels' => $labels,
                'hierarchical' => true,
                'show_ui'           => true,
                'show_admin_column' => true,
              );
              register_taxonomy( 'emsb_service_type', 'emsb_service', $args );
            
        }

        //adding meta box to save additional meta data for the content type
        public function emsb_add_meta_boxes_to_booking_service(){
            //add a meta box
            add_meta_box(
                'emsb_service_meta_box', //id
                __('Informações Gerais', 'emsb-service-booking'), //title
                array($this,'emsb_callback_to_show_the_service_meta_boxes'),  //display function
                'emsb_service', //content type 
                'normal', //context
                'high' //priority
            );
            
        }

        //displays the back-end admin output for the event information
        public function emsb_callback_to_show_the_service_meta_boxes( $post ) {
            wp_nonce_field( basename( __FILE__ ), 'emsb_nonce' );
            $emsb_service_stored_meta = get_post_meta( $post->ID );
            $emsbtexteditor = get_post_meta( $post->ID, 'emsbtexteditor', true );
        ?>
            
          <div class="emsb-service-header-info">
                <label for="emsb-service-header-info"> <h3> <?php _e( '', 'emsb-service-booking' ); ?> </h3> </label>
                <p>
                    <label for="emsb_display_service_title" class="emsb-row-title"><?php _e( "Especialidades:", 'emsb-service-booking' ); ?></label>
                    <input type="text" name="emsb_display_service_title" id="emsb_display_service_title" value="<?php if ( isset ( $emsb_service_stored_meta['emsb_display_service_title'] ) ) echo $emsb_service_stored_meta['emsb_display_service_title'][0]; ?>" />
                </p>
                <p>
                    <label for="emsb_display_service_location" class="emsb-row-location"><?php _e( "Local de Atendimento:", 'emsb-service-booking' ); ?></label>
                    <input type="text" name="emsb_display_service_location" id="emsb_display_service_location" value="<?php if ( isset ( $emsb_service_stored_meta['emsb_display_service_location'] ) ) echo $emsb_service_stored_meta['emsb_display_service_location'][0]; ?>" />
                </p>
                
            </div>

            <!-- service reservation duration -->
            <div class="emsb-service-availability-container emsb-service-meta-field"> 
                <label for="emsb-time-slot"> <h3> <?php _e( 'Disponibilidade', 'emsb-service-booking' ); ?> </h3> </label> 
                <p>
                    <label for="emsb_service_availability_starts_at" class="emsb-row-date-availability"><?php _e( "Disponível a partir: ", 'emsb-service-booking' ); ?></label>
                    <input type="date" name="emsb_service_availability_starts_at" id="emsb_service_availability_starts_at" value="<?php if ( isset ( $emsb_service_stored_meta['emsb_service_availability_starts_at'] ) ) echo $emsb_service_stored_meta['emsb_service_availability_starts_at'][0]; ?>" />
                </p>
                <p>
                    <label for="emsb_service_availability_ends_at" class="emsb-row-date-availability"><?php _e( "Até: ", 'emsb-service-booking' ); ?></label>
                    <input type="date" name="emsb_service_availability_ends_at" id="emsb_service_availability_ends_at" value="<?php if ( isset ( $emsb_service_stored_meta['emsb_service_availability_ends_at'] ) ) echo $emsb_service_stored_meta['emsb_service_availability_ends_at'][0]; ?>" />
                </p>
                <footer class="blockquote-footer text-danger"> <?php _e( 'Caso não esteja disponível, não selecionar datas.', 'emsb-service-booking' ); ?> </footer>
            </div>

            <!-- weekly Off-days  -->
            <div class="emsb-off-days">
              <label for="emsb-off-days"> <h3> <?php _e( 'Dias que não atua ', 'emsb-service-booking' ); ?> </h3> </label>
                <p>
                    <input type="checkbox" name="emsb_service_off_day_sun" id="emsb_service_off_day_sun" value="1" <?php if ( isset ( $emsb_service_stored_meta['emsb_service_off_day_sun'] ) ) checked( $emsb_service_stored_meta['emsb_service_off_day_sun'][0], '1' ); ?> />
                    <label for="emsb_service_off_day_sun" class="emsb-row-off_days"><?php _e( "Domingo", 'emsb-service-booking' )?></label>
                    
                    <input type="checkbox" name="emsb_service_off_day_mon" id="emsb_service_off_day_mon" value="2" <?php if ( isset ( $emsb_service_stored_meta['emsb_service_off_day_mon'] ) ) checked( $emsb_service_stored_meta['emsb_service_off_day_mon'][0], '2' ); ?> />
                    <label for="emsb_service_off_day_mon" class="emsb-row-off_days"><?php _e( "Segunda", 'emsb-service-booking' )?></label>
                    
                    <input type="checkbox" name="emsb_service_off_day_tues" id="emsb_service_off_day_tues" value="3" <?php if ( isset ( $emsb_service_stored_meta['emsb_service_off_day_tues'] ) ) checked( $emsb_service_stored_meta['emsb_service_off_day_tues'][0], '3' ); ?> />
                    <label for="emsb_service_off_day_tues" class="emsb-row-off_days"><?php _e( "Terça", 'emsb-service-booking' )?></label>
                    
                    <input type="checkbox" name="emsb_service_off_day_wed" id="emsb_service_off_day_wed" value="4" <?php if ( isset ( $emsb_service_stored_meta['emsb_service_off_day_wed'] ) ) checked( $emsb_service_stored_meta['emsb_service_off_day_wed'][0], '4' ); ?> />
                    <label for="emsb_service_off_day_wed" class="emsb-row-off_days"><?php _e( "Quarta", 'emsb-service-booking' )?></label>
                    
                    <input type="checkbox" name="emsb_service_off_day_thurs" id="emsb_service_off_day_thurs" value="5" <?php if ( isset ( $emsb_service_stored_meta['emsb_service_off_day_thurs'] ) ) checked( $emsb_service_stored_meta['emsb_service_off_day_thurs'][0], '5' ); ?> />
                    <label for="emsb_service_off_day_thurs" class="emsb-row-off_days"><?php _e( "Quinta", 'emsb-service-booking' )?></label>
                    
                    <input type="checkbox" name="emsb_service_off_day_fri" id="emsb_service_off_day_fri" value="6" <?php if ( isset ( $emsb_service_stored_meta['emsb_service_off_day_fri'] ) ) checked( $emsb_service_stored_meta['emsb_service_off_day_fri'][0], '6' ); ?> />
                    <label for="emsb_service_off_day_fri" class="emsb-row-off_days"><?php _e( "Sexta", 'emsb-service-booking' )?></label>
                    
                    <input type="checkbox" name="emsb_service_off_day_sat" id="emsb_service_off_day_sat" value="7" <?php if ( isset ( $emsb_service_stored_meta['emsb_service_off_day_sat'] ) ) checked( $emsb_service_stored_meta['emsb_service_off_day_sat'][0], '7' ); ?> />
                    <label for="emsb_service_off_day_sat" class="emsb-row-off_days"><?php _e( "Sábado", 'emsb-service-booking' )?></label>
                </p>
            </div>
            <!-- weekly Off-days ends  -->

            <!-- service reservation duration -->
            <div class="emsb-servation-duration-container emsb-service-meta-field"> 
                <label for="emsb-time-slot"> <h3> <?php _e( 'Disponível o dia inteiro ', 'emsb-service-booking' ); ?> </h3> </label> 
                <p>
                    <label for="emsb_service_full_day_reservation" class="emsb-row-time_slot"><?php _e( "Para médicos que atendem 24h ", 'emsb-service-booking' ); ?></label>
                    <input type="checkbox" name="emsb_service_full_day_reservation" id="emsb_service_full_day_reservation" value="checked" <?php if ( isset ( $emsb_service_stored_meta['emsb_service_full_day_reservation'] ) ) checked( $emsb_service_stored_meta['emsb_service_full_day_reservation'][0], 'checked' ); ?> />
                </p>
            </div>
            

             <!-- AM Time Slot  -->
            <div class="emsb-service-meta-field emsb-time-slot-container">
                <label for="emsb-time-slot"> <h3> <?php _e( 'Horários de Manhã ', 'emsb-service-booking' ); ?> </h3> </label>
                <p>
                    <label for="emsb_service_am_starting_time" class="emsb-row-time_slot"><?php _e( "Começa a atender:", 'emsb-service-booking' ); ?></label>
                    <input min='00:00' max= '11:00' type="time" name="emsb_service_am_starting_time" id="emsb_service_am_starting_time" value="<?php if ( isset ( $emsb_service_stored_meta['emsb_service_am_starting_time'] ) ) echo $emsb_service_stored_meta['emsb_service_am_starting_time'][0]; ?>" />
                </p>
                <p>
                    <label for="emsb_service_am_ending_time" class="emsb-row-time_slot"><?php _e( "Até:", 'emsb-service-booking' ); ?></label>
                    <input min='01:00' max= '11:59' type="time" name="emsb_service_am_ending_time" id="emsb_service_am_ending_time" value="<?php if ( isset ( $emsb_service_stored_meta['emsb_service_am_ending_time'] ) ) echo $emsb_service_stored_meta['emsb_service_am_ending_time'][0]; ?>" />
                </p>
                <p>
                    <label for="emsb_service_am_slot_duration" class="emsb-row-time_slot"><?php _e( "Tempo de consulta:", 'emsb-service-booking' ); ?></label>
                    <input type="number" name="emsb_service_am_slot_duration" id="emsb_service_am_slot_duration" value="<?php if ( isset ( $emsb_service_stored_meta['emsb_service_am_slot_duration'] ) ) echo $emsb_service_stored_meta['emsb_service_am_slot_duration'][0]; ?>" />
                </p>
            </div>
            <!-- AM Time Slot Ends  -->

            <!-- PM Time Slot Starts  -->
            <div class="emsb-service-meta-field emsb-time-slot-container">
                <label for="emsb-time-slot"> <h3> <?php _e( 'Horários da Tarde ', 'emsb-service-booking' ); ?> </h3> </label>
                <p>
                    <label for="emsb_service_pm_starting_time" class="emsb-row-time_slot"><?php _e( "Começa a atender:", 'emsb-service-booking' ); ?></label>
                    <input min='12:00' max= '22:59' type="time" name="emsb_service_pm_starting_time" id="emsb_service_pm_starting_time" value="<?php if ( isset ( $emsb_service_stored_meta['emsb_service_pm_starting_time'] ) ) echo $emsb_service_stored_meta['emsb_service_pm_starting_time'][0]; ?>" />
                </p>
                <p>
                    <label for="emsb_service_pm_ending_time" class="emsb-row-time_slot"><?php _e( "Até:", 'emsb-service-booking' ); ?></label>
                    <input min='13:00' max= '23:59' type="time" name="emsb_service_pm_ending_time" id="emsb_service_pm_ending_time" value="<?php if ( isset ( $emsb_service_stored_meta['emsb_service_pm_ending_time'] ) ) echo $emsb_service_stored_meta['emsb_service_pm_ending_time'][0]; ?>" />
                </p>
                <p>
                    <label for="emsb_service_pm_slot_duration" class="emsb-row-time_slot"><?php _e( "Tempo de consulta:", 'emsb-service-booking' ); ?></label>
                    <input type="number" name="emsb_service_pm_slot_duration" id="emsb_service_pm_slot_duration" value="<?php if ( isset ( $emsb_service_stored_meta['emsb_service_pm_slot_duration'] ) ) echo $emsb_service_stored_meta['emsb_service_pm_slot_duration'][0]; ?>" />
                </p>
            </div>
          <!-- PM Time Slot Ends  -->

          <!-- Orders per slot  -->
          <div class="emsb-service-meta-field emsb-permitted-booking-orders-container">
                <label for="emsb-time-slot"> <h3> <?php _e( 'Número de consultas por horário ', 'emsb-service-booking' ); ?> </h3> </label>
                <p>
                    <label for="emsb_service_orders_per_slot" class="emsb-row-time_slot"><?php _e( "Quantas consultas no mesmo horário? ", 'emsb-service-booking' ); ?></label>
                    <input type="number" min="1" name="emsb_service_orders_per_slot" id="emsb_service_orders_per_slot" value="<?php if ( isset ( $emsb_service_stored_meta['emsb_service_orders_per_slot'] ) ) echo $emsb_service_stored_meta['emsb_service_orders_per_slot'][0]; ?>" />
                </p>
            </div>
          <!-- Orders per slot Ends  -->

          <!-- Service Provider Email Address Starts  -->
          <div class="emsb-service-meta-field">
                <label for="emsb_service_provider_email"> <h3> <?php _e( 'E-mail do Médico ', 'emsb-service-booking' ); ?> </h3> </label>
                <p>
                    <label for="emsb_service_provider_email" class="emsb-row-email"><?php _e( "E-mail: ", 'emsb-service-booking' ); ?><?php echo get_the_author_meta('user_email'); ?></label>
                    <input type="email" name="emsb_service_provider_email" id="emsb_service_provider_email" value="<?php if ( isset ( $emsb_service_stored_meta['emsb_service_provider_email'] ) ) echo $emsb_service_stored_meta['emsb_service_provider_email'][0]; ?>" />
                </p>

            </div>
          <!-- Service Provider Email Address Ends  -->

          <!-- Service description -->
            
          
        <?php }

        // *************** Save all post types meta fields ************* //
        public function emsb_save_all_posts_types_meta_fields_meta( $post_id ) {
            // Checks save status 
            $is_autosave = wp_is_post_autosave( $post_id );
            $is_revision = wp_is_post_revision( $post_id );
            $is_valid_nonce = ( isset( $_POST[ 'emsb_nonce' ] ) && wp_verify_nonce( $_POST[ 'emsb_nonce' ], basename( __FILE__ ) ) ) ? 'true' : 'false';

            // Exits script depending on save status 
            if ( $is_autosave || $is_revision || !$is_valid_nonce ) {
                return;
            }
        // *************** post-type: Reviews **************//

            // Checks for input and sanitizes/saves if needed 
            if( isset( $_POST[ 'emsb_display_service_title' ] ) ) {
                $emsb_display_service_title = htmlspecialchars($_POST[ 'emsb_display_service_title']);
                $emsb_display_service_title_sanitized = sanitize_text_field($emsb_display_service_title);
                update_post_meta( $post_id, 'emsb_display_service_title', $emsb_display_service_title_sanitized );
            }
            if( isset( $_POST[ 'emsb_display_service_location' ] ) ) {
                $emsb_display_service_location = htmlspecialchars($_POST[ 'emsb_display_service_location']);
                $emsb_display_service_location_sanitized = sanitize_text_field($emsb_display_service_location);
                update_post_meta( $post_id, 'emsb_display_service_location', $emsb_display_service_location_sanitized );
            }
            if( isset( $_POST[ 'emsb_display_service_price' ] ) ) {
                $emsb_display_service_price = htmlspecialchars($_POST[ 'emsb_display_service_price']);
                $emsb_display_service_price_sanitized = sanitize_text_field($emsb_display_service_price);
                update_post_meta( $post_id, 'emsb_display_service_price', $emsb_display_service_price_sanitized );
            }

            // *************** Service Availability starts at **************//
            if( isset( $_POST[ 'emsb_service_availability_starts_at' ] ) ) {
                $emsb_service_availability_starts_at = htmlspecialchars($_POST[ 'emsb_service_availability_starts_at']);
                $emsb_service_availability_starts_at_sanitized = sanitize_text_field($emsb_service_availability_starts_at);
                update_post_meta( $post_id, 'emsb_service_availability_starts_at', $emsb_service_availability_starts_at_sanitized );
            }

            // *************** Service Availability ends at **************//
            if( isset( $_POST[ 'emsb_service_availability_ends_at' ] ) ) {
                $emsb_service_availability_ends_at = htmlspecialchars($_POST[ 'emsb_service_availability_ends_at']);
                $emsb_service_availability_ends_at_sanitized = sanitize_text_field($emsb_service_availability_ends_at);
                update_post_meta( $post_id, 'emsb_service_availability_ends_at', $emsb_service_availability_ends_at_sanitized );
            }

            // *************** Off-days **************//
            if( isset( $_POST[ 'emsb_service_off_day_sun' ] ) ) {
                update_post_meta( $post_id, 'emsb_service_off_day_sun', '1' );
            } else {
                update_post_meta( $post_id, 'emsb_service_off_day_sun', '' );
            }

            if( isset( $_POST[ 'emsb_service_off_day_mon' ] ) ) {
                update_post_meta( $post_id, 'emsb_service_off_day_mon', '2' );
            } else {
                update_post_meta( $post_id, 'emsb_service_off_day_mon', '' );
            }

            if( isset( $_POST[ 'emsb_service_off_day_tues' ] ) ) {
                update_post_meta( $post_id, 'emsb_service_off_day_tues', '3' );
            } else {
                update_post_meta( $post_id, 'emsb_service_off_day_tues', '' );
            }

            if( isset( $_POST[ 'emsb_service_off_day_wed' ] ) ) {
                update_post_meta( $post_id, 'emsb_service_off_day_wed', '4' );
            } else {
                update_post_meta( $post_id, 'emsb_service_off_day_wed', '' );
            }

            if( isset( $_POST[ 'emsb_service_off_day_thurs' ] ) ) {
                update_post_meta( $post_id, 'emsb_service_off_day_thurs', '5' );
            } else {
                update_post_meta( $post_id, 'emsb_service_off_day_thurs', '' );
            }

            if( isset( $_POST[ 'emsb_service_off_day_fri' ] ) ) {
                update_post_meta( $post_id, 'emsb_service_off_day_fri', '6' );
            } else {
                update_post_meta( $post_id, 'emsb_service_off_day_fri', '' );
            }

            if( isset( $_POST[ 'emsb_service_off_day_sat' ] ) ) {
                update_post_meta( $post_id, 'emsb_service_off_day_sat', '7' );
            } else {
                update_post_meta( $post_id, 'emsb_service_off_day_sat', '' );
            }


            // *************** Full Day Reservation **************// 
            if( isset( $_POST[ 'emsb_service_full_day_reservation' ] ) ) {
                update_post_meta( $post_id, 'emsb_service_full_day_reservation', 'checked' );
            } else {
                update_post_meta( $post_id, 'emsb_service_full_day_reservation', '' );
            }
            

            // *************** AM Time slot **************//
            $default_emsb_service_am_starting_time = "09:00";
            $default_emsb_service_am_ending_time = "11:00";
            $default_emsb_service_am_slot_duration = "60";
            if( isset( $_POST[ 'emsb_service_am_starting_time' ] ) ) {
                $emsb_service_am_starting_time = htmlspecialchars($_POST[ 'emsb_service_am_starting_time']);
                $emsb_service_am_starting_time_sanitized = sanitize_text_field($emsb_service_am_starting_time);
                update_post_meta( $post_id, 'emsb_service_am_starting_time', $emsb_service_am_starting_time_sanitized );
            } else {
                update_post_meta( $post_id, 'emsb_service_am_starting_time', $default_emsb_service_am_starting_time );
            }

            if( isset( $_POST[ 'emsb_service_am_ending_time' ] ) ) {
                $emsb_service_am_ending_time = htmlspecialchars($_POST[ 'emsb_service_am_ending_time']);
                $emsb_service_am_ending_time_sanitized = sanitize_text_field($emsb_service_am_ending_time);
                update_post_meta( $post_id, 'emsb_service_am_ending_time', $emsb_service_am_ending_time_sanitized );
            } else {
                update_post_meta( $post_id, 'emsb_service_am_ending_time', $default_emsb_service_am_ending_time );
            }

            if( isset( $_POST[ 'emsb_service_am_slot_duration' ] ) ) {
                $emsb_service_am_slot_duration = htmlspecialchars($_POST[ 'emsb_service_am_slot_duration']);
                $emsb_service_am_slot_duration_sanitized = absint($emsb_service_am_slot_duration);
                update_post_meta( $post_id, 'emsb_service_am_slot_duration', $emsb_service_am_slot_duration_sanitized );
            } else {
                update_post_meta( $post_id, 'emsb_service_am_slot_duration', $default_emsb_service_am_slot_duration );
            }

            // *************** PM Time slot **************//
            $default_emsb_service_pm_starting_time = "15:00";
            $default_emsb_service_pm_ending_time = "22:00";
            $default_emsb_service_pm_slot_duration = "60";
            if( isset( $_POST[ 'emsb_service_pm_starting_time' ] ) ) {
                $emsb_service_pm_starting_time = htmlspecialchars($_POST[ 'emsb_service_pm_starting_time']);
                $emsb_service_pm_starting_time_sanitized = sanitize_text_field($emsb_service_pm_starting_time);
                update_post_meta( $post_id, 'emsb_service_pm_starting_time', $emsb_service_pm_starting_time_sanitized );
            } else {
                update_post_meta( $post_id, 'emsb_service_pm_starting_time', $default_emsb_service_pm_starting_time );
            }

            if( isset( $_POST[ 'emsb_service_pm_ending_time' ] ) ) {
                $emsb_service_pm_ending_time = htmlspecialchars($_POST[ 'emsb_service_pm_ending_time']);
                $emsb_service_pm_ending_time_sanitized = sanitize_text_field($emsb_service_pm_ending_time);
                update_post_meta( $post_id, 'emsb_service_pm_ending_time', $emsb_service_pm_ending_time_sanitized );
            } else {
                update_post_meta( $post_id, 'emsb_service_pm_ending_time', $default_emsb_service_pm_ending_time );
            }
            if( isset( $_POST[ 'emsb_service_pm_slot_duration' ] ) ) {
                $emsb_service_pm_slot_duration = htmlspecialchars($_POST[ 'emsb_service_pm_slot_duration']);
                $emsb_service_pm_slot_duration_sanitized = absint($emsb_service_pm_slot_duration);
                update_post_meta( $post_id, 'emsb_service_pm_slot_duration', $emsb_service_pm_slot_duration_sanitized );
            } else {
                update_post_meta( $post_id, 'emsb_service_pm_slot_duration', $default_emsb_service_pm_slot_duration );
            }

            // *************** Orders per slot **************//
            $default_order_per_slot = 1;
            if( isset( $_POST[ 'emsb_service_orders_per_slot' ] ) ) {
                $emsb_service_orders_per_slot = htmlspecialchars($_POST[ 'emsb_service_orders_per_slot']);
                $emsb_service_orders_per_slot_sanitized = absint($emsb_service_orders_per_slot);
                update_post_meta( $post_id, 'emsb_service_orders_per_slot', $emsb_service_orders_per_slot_sanitized );
            } else {
                update_post_meta( $post_id, 'emsb_service_orders_per_slot', $default_order_per_slot);
            }

            // *************** Service Provider Email Address **************//
            $emsb_service_provider_email_default = get_bloginfo('admin_email');
            if(isset( $_POST[ 'emsb_service_provider_email' ] )) {
                $emsb_service_provider_email = htmlspecialchars($_POST[ 'emsb_service_provider_email']);
                $emsb_service_provider_email_sanitized = sanitize_email($emsb_service_provider_email);
                update_post_meta( $post_id, 'emsb_service_provider_email', $emsb_service_provider_email_sanitized );
            } else {
                update_post_meta( $post_id, 'emsb_service_provider_email', $emsb_service_provider_email_default );
            }
            
            // *************** emsbtexteditor **************//
            if( isset( $_POST[ 'emsbtexteditor_check' ] ) ) {
                update_post_meta( $post_id, 'emsbtexteditor_check', 'description' );
            } else {
                update_post_meta( $post_id, 'emsbtexteditor_check', '' );
            }

            if( isset( $_POST[ 'emsbtexteditor' ] ) ) {
                $emsbtexteditor_sanitized = wp_kses_post($_POST[ 'emsbtexteditor']);
                update_post_meta( $post_id, 'emsbtexteditor', $emsbtexteditor_sanitized );
            }

            
        }

        
        
        public function emsb_get_archive_template( $archive_template ) {
            global $post;
        
            if ( is_post_type_archive ( 'emsb_service' ) ) {
                 $archive_template = dirname( __FILE__ ) . '/archive-emsb_service.php';
            }
            return $archive_template;
        }

        public function emsb_get_single_template( $emsb_single_service ) {
            global $post;
            // global $post_type;
        
            if ( is_singular( 'emsb_service' ) ) {
                 $emsb_single_service = dirname( __FILE__ ) . '/single-emsb_service.php';
            }
            return $emsb_single_service;
        }

        public function emsb_disable_gutenberg_pages( $can_edit, $post_type ) {
            if ( 'emsb_service' === $post_type ) {
              return false;
            }
            return $can_edit;
          }

        
         

        public function emsb_enqueue_public_scripts(){
  
            wp_enqueue_style('emsb-calendar', plugin_dir_url(__FILE__) . 'calendar/emsb-calendar.css', array(), '1.1', false );
            wp_enqueue_style('emsb-all', plugin_dir_url(__FILE__) . 'assets/public/css/emsb-all.css', array(), '1.1', false );
            
            wp_enqueue_script("jquery");
            
            wp_enqueue_script('popper-js', plugin_dir_url(__FILE__) . 'assets/js/popper.min.js', array(), '1.1', true );
            wp_enqueue_script('bootstrap-js', plugin_dir_url(__FILE__) . 'assets/js/bootstrap-v4.3.1.min.js', array(), '1.1', true );
            wp_enqueue_script('chosen.jquery', plugin_dir_url(__FILE__) . 'assets/js/chosen.jquery.js', array(), '1.1', true );
            wp_enqueue_script('pseudo-ripple-js', plugin_dir_url(__FILE__) . 'calendar/jquery-pseudo-ripple.js', array(), '1.1', true );
            wp_enqueue_script('nao-calendar-js', plugin_dir_url(__FILE__) . 'calendar/jquery-nao-calendar.js', array(), '1.1', true ); 
            wp_register_script( "emsb-script-js", plugin_dir_url(__FILE__) . 'assets/public/js/script.js', array('jquery') );
            
            wp_localize_script( 'emsb-script-js', 'frontend_ajax_object',
                array( 
                    'ajaxurl' => admin_url( 'admin-ajax.php' )
                )
            );
            wp_enqueue_script('emsb-script-js', plugin_dir_url(__FILE__) . 'assets/public/js/script.js', array(), '1.1', true );

        }
   
          
                
                
                
    }
}
/**
 * Instantiate class
 */
$emsb_service_booking_plugin = new emsb_service_booking_plugin_base_class();



class emsb_database {
    public function __construct() {
        register_activation_hook( __FILE__, array($this,'create_emsb_bookings_table'));
        register_activation_hook( __FILE__, array($this,'emsb_default_booking'));
        register_activation_hook( __FILE__, array($this,'create_emsb_settings_table'));
        register_activation_hook( __FILE__, array($this,'emsb_settings_default_data'));
        
    }

    public function create_emsb_bookings_table(){
            global $wpdb;
            $sql_enquiry = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}emsb_bookings (
            `id` int(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `approve_booking` varchar(20),
            `service_id` int(11) NOT NULL,
            `service_name` varchar(200),
            `service_price` text,
            `booked_date_id` text,
            `booked_slot_id` text,
            `booked_date` text,
            `booked_time_slot` text,
            `customer_name` varchar(200),
            `customer_email` text,
            `customer_phone` text,
            `booking_time` TIMESTAMP NOT NULL,
            `customer_IP` VARCHAR(100) NOT NULL,
            `starting_time_ms` BIGINT NOT NULL,
            `available_orders` int(11) NOT NULL
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8";


        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql_enquiry );
        

    }

    public function emsb_default_booking(){
        global $wpdb;
        $emsb_bookings_table_name = $wpdb->prefix . "emsb_bookings";
    
        $dataInsert = $wpdb->insert( $emsb_bookings_table_name, array(
            'service_id' => 0,
            'service_name' => "Default Data",
            'service_price' => "Default Data",
            'booked_date_id' => "Default Data",
            'booked_slot_id' => "Default Data",
            'booked_date' => "Default Data",
            'booked_time_slot' => "Default Data",
            'customer_name' => "Default Data",
            'customer_email' => "Default Data",
            'customer_phone' => "Default Data"
        ) );
    

    }

    public function create_emsb_settings_table(){
            global $wpdb;
            $sql_enquiry_notifiction_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}emsb_settings (
            `id` int(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `admin_mail_subject` text,
            `admin_mail_body` text,
            `customer_mail_pending_subject` text,
            `customer_mail_pending_body` text,
            `customer_mail_confirmed_subject` text,
            `customer_mail_confirmed_body` text,
            `customer_mail_cancel_subject` text,
            `customer_mail_cancel_body` text,
            `customer_cookie_duration` int(11)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8";


        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql_enquiry_notifiction_table );

    }
    public function emsb_settings_default_data() {
		global $wpdb;
        $emsb_settings_data = $wpdb->prefix . 'emsb_settings';
		$wpdb->insert($emsb_settings_data, array(
            'admin_mail_subject' => "Service provider's email subject",
            'admin_mail_body' => "Service provider's email message body",
            'customer_mail_pending_subject' => "Booking Pending",
            'customer_mail_pending_body' => "pending booking email body",
            'customer_mail_confirmed_subject' => "Booking Confirmed",
            'customer_mail_confirmed_body' => "Confirmation email message body",
            'customer_mail_cancel_subject' => "Booking Cancelled",
            'customer_mail_cancel_body' => "Cancel email message body",
            'customer_cookie_duration' => 30
        ));

    }
    

}
$emsb_database = new emsb_database();


class emsb_post_pages {
    public function __construct() {
        $emsb_page_slug = get_page_by_path( 'marcacao' );
        if(!$emsb_page_slug){
            register_activation_hook( __FILE__, array($this,'emsb_create_archive_page'));
        } 
        
        
    }

    public function emsb_create_archive_page(){

        $booking_services  = array( 
                            'post_title'     => 'marcacao',
                            'post_type'      => 'page',
                            'post_name'      => 'marcacao',
                            'post_status'    => 'publish',
                            'comment_status' => 'closed'
                        );

        $PageID = wp_insert_post( $booking_services, FALSE ); 

    }

    
}
$emsb_post_pages = new emsb_post_pages();


class emsb_internationlization {
    public function __construct() {
        // Initialize the plugin
		add_action( 'init', array( $this, 'emsb_load_plugin_textdomain' ) );
        
    }

    public function emsb_load_plugin_textdomain(){
        load_plugin_textdomain( 'emsb-service-booking', false, plugin_basename( dirname( __FILE__ ) ) . "/languages/" );
    }

    
}
$emsb_internationlization = new emsb_internationlization();


include( plugin_dir_path( __FILE__ ) . 'emsb-ajax-calls.php');
include( plugin_dir_path( __FILE__ ) . 'emsb-service-booking-admin.php');
