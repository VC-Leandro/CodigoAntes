<?php


add_action( 'admin_menu', array ( 'emsb_Admin_Page', 'emsb_admin_menu' ) );

class emsb_Admin_Page
{

	public static function emsb_admin_menu()
	{
		$main = add_menu_page(
			'SVP CEMEQ',                                         // page title
			'CEMEQ',                                         // menu title
			'manage_options',                               // capability
			'emsb_admin_page',                              // menu slug
            array ( __CLASS__, 'emsb_admin_main_page' ),
            '',
            26                                              // callback function
		);

		$sub = add_submenu_page(
			'emsb_admin_page',                         // parent slug
			'Manage Bookings',                         // page title
			'Marcação',                               // menu title
			'manage_options',                          // capability
			'emsb_admin_page',                         // menu slug
			array ( __CLASS__, 'emsb_admin_main_page' )         // callback function, same as above
		);

        
        $emsb_post_type = 'emsb_service';
        /* Get CPT Object */
        $emsb_post_type_obj = get_post_type_object( $emsb_post_type );
        $emsb_services_menu_page = add_submenu_page(
            'emsb_admin_page',                             // parent slug
            $emsb_post_type_obj->labels->name,             // page title
            'Médicos',                                // menu title
            $emsb_post_type_obj->cap->edit_posts,          // capability
            'edit.php?post_type=' . $emsb_post_type        // menu slug
        );

        $emsb_add_new_service_menu_page = add_submenu_page(
            'emsb_admin_page',                             // parent slug
            $emsb_post_type_obj->labels->name,             // page title
            'Novo Médico',                                 // menu title
            $emsb_post_type_obj->cap->edit_posts,          // capability
            'post-new.php?post_type=' . $emsb_post_type    // menu slug
        );

        $emsb_bookings = add_submenu_page(
			'emsb_admin_page',                         // parent slug
			'EMSB bookings',                           // page title
			'Consultas',                            // menu title
			'manage_options',                          // capability
			'emsb_admin_bookings_page',                // menu slug
			array ( __CLASS__, 'emsb_admin_bookings_page_func' )         // callback function, same as above
        );
        
        $emsb_settings = add_submenu_page(
			'emsb_admin_page',                         // parent slug
			'EMSB bookings',                           // page title
			'Configurações',                                // menu title
			'manage_options',                          // capability
			'emsb_admin_settings_page',                // menu slug
			array ( __CLASS__, 'emsb_admin_settings_page_func' )         // callback function, same as above
		);
        
		foreach ( array ( $main, $sub, $emsb_bookings, $emsb_settings, $emsb_services_menu_page) as $slug )
		{
			// make sure the style callback is used on our page only
			add_action(
				"admin_print_styles-$slug",
				array ( __CLASS__, 'enqueue_style' )
			);
			// make sure the script callback is used on our page only
			add_action(
				"admin_print_scripts-$slug",
				array ( __CLASS__, 'enqueue_script' )
            );
            
            
        }

        add_action( 'admin_enqueue_scripts', array ( __CLASS__, 'emsb_edit_services' ));
        add_action( 'admin_notices', array ( __CLASS__, 'emsb_services_header_html' ));

        // Add custom column to service post type edit list
        add_filter( 'manage_emsb_service_posts_columns', array ( __CLASS__, 'emsb_columns_head_only_emsb_services' ), 10);
        add_action( 'manage_emsb_service_posts_custom_column', array ( __CLASS__, 'emsb_columns_content_only_emsb_services' ), 10, 2);

		
    }


    // CREATE TWO FUNCTIONS TO HANDLE THE COLUMN
    public static function emsb_columns_head_only_emsb_services($emsb_featured_image_column) {
        unset($emsb_featured_image_column['taxonomy-emsb_service_type']);
        unset($emsb_featured_image_column['title']);
        unset($emsb_featured_image_column['date']);
        
        $emsb_featured_image_column['esmb_service_featured_image'] = 'Foto';
        $emsb_featured_image_column['title'] = 'Médico';
        $emsb_featured_image_column['taxonomy-emsb_service_type'] = 'Especialidades';
        $emsb_featured_image_column['emsb_availability'] = 'Disponível até';
        $emsb_featured_image_column['date'] = 'Data';
        return $emsb_featured_image_column;
    }
    public static function emsb_columns_content_only_emsb_services($column_name, $post_ID) {

        $today = date("Y-m-d");
        $today_time = strtotime($today);

        add_image_size( 'emsb_service-admin-post-featured-image', 60, 60, false );
        switch($column_name){
            case 'esmb_service_featured_image':
            if( function_exists('the_post_thumbnail') ) {
                echo the_post_thumbnail('emsb_service-admin-post-featured-image');
            } 
            break;

            case 'emsb_availability':
                $emsb_service_ending_date = get_post_meta( $post_ID, 'emsb_service_availability_ends_at', TRUE);
                $expire_time = strtotime($emsb_service_ending_date);
                if($expire_time > $today_time){
                    echo $emsb_service_ending_date;
                } else { ?>
                    <div class='emsb-service-not-available'> <?php _e( 'Atualize a disponibilidade do médico.', 'emsb-service-booking' ); ?> </div>
                <?php 
                }
                
            break;
        }
        
    }

    public static function emsb_services_header_html() {
        global $pagenow ,$post;
        global $post_type;
        $emsb_plugin_path = plugin_dir_url( __FILE__ );
        $emsb_icon_url = $emsb_plugin_path . 'assets/img/service-booking.png';

        if( $post_type == 'emsb_service' && ($pagenow == 'edit.php' || $pagenow == 'post.php') ) {
            ?>
                <div class="emsb-container">
                    <header class="emsb-admin-main-page-header-wrapper">
                        <div class="jumbotron text-center">
                            <div class="emsb-admin-plugin-title">
                                <img src="<?php echo $emsb_icon_url; ?>" alt="Service Booking Icon">
                                <h2 class="display-5"> <?php _e( 'Sistema de Consultas CEMEQ', 'emsb-service-booking' ); ?></h2>
                            </div>
                        </div>
                    </header>

                    <main class="emsb-admin-main-page-wrapper">
                        <div class="tabs">
                            <ul>
                                <li><a href="admin.php?page=emsb_admin_page" > <?php _e( 'Marcação ', 'emsb-service-booking' ); ?>  </a></li>
                                <li><a href="edit.php?post_type=emsb_service" class="active"> <?php _e( 'Médicos ', 'emsb-service-booking' ); ?></a></li>
                                <li><a href="post-new.php?post_type=emsb_service"> <?php _e( 'Novo Médico ', 'emsb-service-booking' ); ?></a></li>
                                <li><a href="admin.php?page=emsb_admin_bookings_page"> <?php _e( 'Consultas ', 'emsb-service-booking' ); ?></a></li>
                                <li><a href="admin.php?page=emsb_admin_settings_page"><?php _e( 'Configurações  ', 'emsb-service-booking' ); ?></a></li>
                            </ul>
                        </div>
                        
                    </main>

                </div>
            <?php
        }

        if( $post_type == 'emsb_service' && $pagenow == 'post-new.php' ) {
            ?>
                <div class="emsb-container">
                    <header class="emsb-admin-main-page-header-wrapper">
                        <div class="jumbotron text-center">
                            <div class="emsb-admin-plugin-title">
                                <img src="<?php echo $emsb_icon_url; ?>" alt="Service Booking Icon">
                                <h2 class="display-5"> <?php _e( 'Sistema de Consultas CEMEQ', 'emsb-service-booking' ); ?></h2>
                            </div>
                        </div>
                    </header>

                    <main class="emsb-admin-main-page-wrapper">
                        <div class="tabs">
                            <ul>
                                <li><a href="admin.php?page=emsb_admin_page" > <?php _e( 'Marcação ', 'emsb-service-booking' ); ?></a></li>
                                <li><a href="edit.php?post_type=emsb_service" > <?php _e( 'Médicos ', 'emsb-service-booking' ); ?></a></li>
                                <li><a href="post-new.php?post_type=emsb_service" class="active"> <?php _e( 'Novo Médico ', 'emsb-service-booking' ); ?></a></li>
                                <li><a href="admin.php?page=emsb_admin_bookings_page"> <?php _e( 'Consultas ', 'emsb-service-booking' ); ?></a></li>
                                <li><a href="admin.php?page=emsb_admin_settings_page"><?php _e( 'Configurações  ', 'emsb-service-booking' ); ?></a></li>
                            </ul>
                        </div>
                        
                    </main>

                </div>
            <?php
        }


    }

    public static function emsb_edit_services($hook) {

        global $post;
        global $post_type;

        wp_enqueue_style('emsb-admin-css', plugin_dir_url(__FILE__) . 'assets/private/css/emsb-admin.css', array(), '1.1', false );
        
        wp_enqueue_script('jquery');

        wp_register_script( 'emsb-admin-scripts', plugins_url( 'assets/private/js/emsb-admin-scripts.js', __FILE__ ), array(), FALSE, TRUE);
        wp_enqueue_script( 'emsb-admin-scripts' );
        wp_localize_script( 'emsb-admin-scripts', 'backend_ajax_object',
            array( 
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'pluginsUrl' => plugins_url()
                
            )
        );
        
        if ( $hook == 'post-new.php' || $hook == 'post.php' || $hook == 'edit.php' ) {
            if ( 'emsb_service' === $post_type ) {     
                wp_enqueue_style('bootstrap-css', plugin_dir_url(__FILE__) . 'assets/css/bootstrap-v4.3.1.min.css', array(), '1.1', false );
                wp_enqueue_style('style-css', plugin_dir_url(__FILE__) . 'assets/private/css/emsb-admin-only.css', array(), '1.1', false );
                
                wp_enqueue_script('popper-js', plugin_dir_url(__FILE__) . 'assets/js/popper.min.js', array(), '1.1', true );
                wp_enqueue_script('bootstrap-js', plugin_dir_url(__FILE__) . 'assets/js/bootstrap-v4.3.1.min.js', array(), '1.1', true );
               
                
            }
        }
        
        
    }

    /**
	 * Load stylesheet on our admin page only.
	 *
	 * @return void
	 */
	public static function enqueue_style()
	{
		wp_register_style('emsb_bootstrap_css', plugins_url( 'assets/css/bootstrap-v4.3.1.min.css', __FILE__ ));
        wp_enqueue_style( 'emsb_bootstrap_css' );

        wp_enqueue_style('style-css', plugin_dir_url(__FILE__) . 'assets/private/css/emsb-admin-only.css', array(), '1.1', false );
        
	}
	/**
	 * Load JavaScript on our admin page only.
	 *
	 * @return void
	 */
	public static function enqueue_script()
	{
        wp_enqueue_script( 'jquery' );
        

        wp_register_script('popper', plugins_url( 'assets/js/popper.min.js', __FILE__ ), array(), FALSE, TRUE );
        wp_enqueue_script( 'popper' );

        wp_register_script('bootstrap', plugins_url( 'assets/js/bootstrap-v4.3.1.min.js', __FILE__ ), array(), FALSE, TRUE );
        wp_enqueue_script( 'bootstrap' );
        
        wp_register_script( 'emsb-bookings', plugins_url( 'assets/private/js/emsb-bookings-table-scripts.js', __FILE__ ), array(), FALSE, TRUE);
        wp_enqueue_script( 'emsb-bookings' );

        wp_register_script( 'emsb-admin-scripts', plugins_url( 'assets/private/js/emsb-admin-scripts.js', __FILE__ ), array(), FALSE, TRUE);
        wp_enqueue_script( 'emsb-admin-scripts' );
        
        wp_localize_script( 'emsb-admin-scripts', 'backend_ajax_object',
                    array( 
                        'ajaxurl' => admin_url( 'admin-ajax.php' ),
                        'pluginsUrl' => plugins_url()
                    )
                );
        
        
	}


	public static function emsb_admin_main_page() { 
        global $wpdb;
        $emsb_settings_data = $wpdb->prefix . 'emsb_settings';

        $emsb_plugin_path = plugin_dir_url( __FILE__ );
        $emsb_icon_url = $emsb_plugin_path . 'assets/img/service-booking.png';
        
        if(isset($_POST['emsb_save_admin_email_data'])){
            $admin_mail_subject = stripslashes_deep($_POST['emsb_admin_email_subject']);
            $admin_mail_body = stripslashes_deep($_POST['emsb_admin_email_body']);
            $emsb_customer_pending_email_subject = stripslashes_deep($_POST['emsb_customer_pending_email_subject']);
            $emsb_customer_pending_email_body = stripslashes_deep($_POST['emsb_customer_pending_email_body']);
            $emsb_customer_confirmed_email_subject = stripslashes_deep($_POST['emsb_customer_confirmed_email_subject']);
            $emsb_customer_confirmed_email_body = stripslashes_deep($_POST['emsb_customer_confirmed_email_body']);
            $emsb_customer_cancelled_email_subject = stripslashes_deep($_POST['emsb_customer_cancelled_email_subject']);
            $emsb_customer_cancelled_email_body = stripslashes_deep($_POST['emsb_customer_cancelled_email_body']);
            
            $customer_cookie_duration = stripslashes_deep($_POST['emsb_customer_cookie_duration']);
            // Securly insert data with $wpdb->inert method preventing the sql injection and also escaping strings
            $wpdb->insert($emsb_settings_data, array(
                'admin_mail_subject' => $admin_mail_subject,
                'admin_mail_body' => $admin_mail_body,
                'customer_mail_pending_subject' => $emsb_customer_pending_email_subject,
                'customer_mail_pending_body' => $emsb_customer_pending_email_body,
                'customer_mail_confirmed_subject' => $emsb_customer_confirmed_email_subject,
                'customer_mail_confirmed_body' => $emsb_customer_confirmed_email_body,
                'customer_mail_cancel_subject' => $emsb_customer_cancelled_email_subject,
                'customer_mail_cancel_body' => $emsb_customer_cancelled_email_subject,
                'customer_cookie_duration' => $customer_cookie_duration
            ));
            
        };

        // When the page loads fetch data from database
        $emsb_settings_data_fetch = $wpdb->get_row( "SELECT * FROM $emsb_settings_data ORDER BY id DESC LIMIT 1" );

        // When settings data is changed fetch new data from database
        $emsb_check_changes = isset($_POST['emsb_save_admin_email_data']);

        if($emsb_check_changes){
            $emsb_settings_data_fetch = $wpdb->get_row( "SELECT * FROM $emsb_settings_data ORDER BY id DESC LIMIT 1" );
        }

        $fetch_admin_mail_subject = $emsb_settings_data_fetch->admin_mail_subject;
        $fetch_admin_mail_body = $emsb_settings_data_fetch->admin_mail_body;
        $fetch_emsb_customer_pending_email_subject = $emsb_settings_data_fetch->customer_mail_pending_subject;
        $fetch_emsb_customer_pending_email_body = $emsb_settings_data_fetch->customer_mail_pending_body;
        $fetch_emsb_customer_confirmed_email_subject = $emsb_settings_data_fetch->customer_mail_confirmed_subject;
        $fetch_emsb_customer_confirmed_email_body = $emsb_settings_data_fetch->customer_mail_confirmed_body;
        $fetch_emsb_customer_cancelled_email_subject = $emsb_settings_data_fetch->customer_mail_cancel_subject;
        $fetch_emsb_customer_cancelled_email_body = $emsb_settings_data_fetch->customer_mail_cancel_body;
        $fetch_customer_cookie_duration = $emsb_settings_data_fetch->customer_cookie_duration;
        

        ?>
        <div class="emsb-container">
            <header class="emsb-admin-main-page-header-wrapper">
                <div class="jumbotron text-center">
                    <div class="emsb-admin-plugin-title">
                        <img src="<?php echo $emsb_icon_url; ?>" alt="Service Booking Icon">
                        <h2 class="display-5"> <?php _e( 'Sistema de Consultas CEMEQ', 'emsb-service-booking' ); ?></h2>
                    </div>
                </div>
            </header>
            <main class="emsb-admin-main-page-wrapper">
                <div class="tabs">
                    <ul>
                        <li><a href="admin.php?page=emsb_admin_page" class="active"> <?php _e( 'Marcação ', 'emsb-service-booking' ); ?></a></li>
                        <li><a href="edit.php?post_type=emsb_service"><?php _e( 'Médicos  ', 'emsb-service-booking' ); ?></a></li>
                        <li><a href="post-new.php?post_type=emsb_service"><?php _e( 'Novo Médico  ', 'emsb-service-booking' ); ?></a></li>
                        <li><a href="admin.php?page=emsb_admin_bookings_page"><?php _e( 'Consultas  ', 'emsb-service-booking' ); ?></a></li>
                        <li><a href="admin.php?page=emsb_admin_settings_page"><?php _e( 'Configurações  ', 'emsb-service-booking' ); ?></a></li>
                    </ul>
                </div>
                
                <div class="emsb-table-wrapper container text-center">
                        <div class="emsb-container">
                            <div class="header_wrap">
                                <div class="emsb-approval-nonce-wrapper">
                                    <input type="hidden" name="emsb_booking_approval_nonce" id="emsb_booking_approval_nonce" value="<?php echo wp_create_nonce("emsb_booking_approval_nonce"); ?>" >
                                </div>
                            </div>
                            <form class="emsb-pending-bookings-container">
                                <table class="table table-striped table-class" id= "table-id">
                                    <thead>
                                        <tr>
                                            <th><?php _e( 'Selecinar ', 'emsb-service-booking' ); ?></th>
                                            <th><?php _e( 'ID', 'emsb-service-booking' ); ?></th>
                                            <th><?php _e( 'Médico', 'emsb-service-booking' ); ?></th>
                                            <th><?php _e( 'Paciente', 'emsb-service-booking' ); ?></th>
                                            <th><?php _e( 'Contato', 'emsb-service-booking' ); ?></th>
                                            <th><?php _e( 'E-mail', 'emsb-service-booking' ); ?></th>
                                            <th><?php _e( 'Data ', 'emsb-service-booking' ); ?></th>
                                            <th><?php _e( 'Horário', 'emsb-service-booking' ); ?></th>
                                            <th><?php _e( 'Status', 'emsb-service-booking' ); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody id="emsbPendingBookings">
                            
                                    </tbody>
                                </table>
                                <div class="emsb-admin-loading-gif">
                                    <img src="<?php echo plugin_dir_url( __FILE__ ) . 'assets/img/loading.gif'; ?>">
                                </div>
                                <footer class="blockquote-footer emsb-pending-table-footer">  <?php _e( '10 Solicitações de marcações pendentes apenas para os próximos slots do horário atual (Do primeiro ao ultimo solicitante)', 'emsb-service-booking' ); ?> </footer>
                            </form> 
                               
                        </div> <!-- End of Container -->

                        <!-- Doc button  -->
                        <div class="my-4 emsb-doc-button">
                            <button type="button" class="btn btn-primary ">
                                <a class="text-light" target="_blank" href="https://e-motahar.com/emsb-service-booking-wordpress-plugin/"> <?php _e( 'Documentation', 'emsb-service-booking' ); ?> </a>
                            </button>
                        </div>

                    </div>

            </main>
        </div>
        
            
        <?php

    }

    
    public static function emsb_admin_bookings_page_func() {
            global $title;
            global $wpdb;
            $emsb_bookings = $wpdb->prefix . 'emsb_bookings';	
            $emsb_all_bookings_from_database = "SELECT * FROM $emsb_bookings WHERE approve_booking = '1' ORDER BY id DESC";
            $emsb_order_list = $wpdb->get_results($emsb_all_bookings_from_database, ARRAY_A);

            $emsb_plugin_path = plugin_dir_url( __FILE__ );
            $emsb_icon_url = $emsb_plugin_path . 'assets/img/service-booking.png';
        ?> 
            <div class="emsb-container">
                <header class="emsb-admin-main-page-header-wrapper">
                    <div class="jumbotron text-center">
                        <div class="emsb-admin-plugin-title">
                            <img src="<?php echo $emsb_icon_url; ?>" alt="Service Booking Icon">
                            <h2 class="display-5"> <?php _e( 'Sistema de Consultas CEMEQ', 'emsb-service-booking' ); ?></h2>
                        </div>
                    </div>
                </header>

                <main class="emsb-admin-main-page-wrapper">
                    <div class="tabs">
                        <ul>
                            <li><a href="admin.php?page=emsb_admin_page" > <?php _e( 'Marcação', 'emsb-service-booking' ); ?></a></li>
                            <li><a href="edit.php?post_type=emsb_service"> <?php _e( 'Médicos', 'emsb-service-booking' ); ?></a></li>
                            <li><a href="post-new.php?post_type=emsb_service"><?php _e( 'Novo Médico  ', 'emsb-service-booking' ); ?></a></li>
                            <li><a href="admin.php?page=emsb_admin_bookings_page" class="active"><?php _e( 'Consultas ', 'emsb-service-booking' ); ?></a></li>
                            <li><a href="admin.php?page=emsb_admin_settings_page"><?php _e( 'Configurações  ', 'emsb-service-booking' ); ?></a></li>
                        </ul>
                    </div>
                    <div class="emsb-table-wrapper container text-center">
                        <div class="emsb-container">
                            <div class="header_wrap">
                                <div class="num_rows">
                                    <div class="form-group"> 	
                                        <!--		Show Numbers Of Rows 		-->
                                            <select class  ="form-control" name="state" id="maxRows">
                                                <option value="15"><?php _e( '15', 'emsb-service-booking' ); ?></option>
                                                <option value="20"><?php _e( '20', 'emsb-service-booking' ); ?></option>
                                                <option value="50"><?php _e( '50', 'emsb-service-booking' ); ?></option>
                                                <option value="70"><?php _e( '70', 'emsb-service-booking' ); ?></option>
                                                <option value="100"><?php _e( '100', 'emsb-service-booking' ); ?></option>
                                                <option value="5000000"><?php _e( 'Show ALL Rows', 'emsb-service-booking' ); ?></option>
                                            </select>
                                        </div>
                                    </div>
                                <div class="tb_search">
                                    <input type="text" id="search_input_all" onkeyup="FilterkeyWord_all_table()" placeholder="Procurar.." class="form-control">
                                </div>
                                <div class="emsb-approval-nonce-wrapper">
                                    <input type="hidden" name="emsb_booking_approval_nonce" id="emsb_booking_approval_nonce" value="<?php echo wp_create_nonce("emsb_booking_approval_nonce"); ?>" >
                                </div>
                            </div>
                            <form >
                                <table class="table table-striped table-class" id= "table-id">
                                    <thead>
                                        <tr>
                                            <th><?php _e( 'No. ', 'emsb-service-booking' ); ?></th>
                                            <th><?php _e( 'ID da Consulta ', 'emsb-service-booking' ); ?></th>
                                            <th><?php _e( 'Médico Responsável ', 'emsb-service-booking' ); ?></th>
                                            <th><?php _e( 'Paciente ', 'emsb-service-booking' ); ?></th>
                                            <th> <?php _e( 'Contato', 'emsb-service-booking' ); ?></th>
                                            <th> <?php _e( 'E-mail', 'emsb-service-booking' ); ?></th>
                                            <th><?php _e( 'Data da Consulta ', 'emsb-service-booking' ); ?></th>
                                            <th> <?php _e( 'Horário', 'emsb-service-booking' ); ?></th>
                                            <th> <?php _e( 'Status', 'emsb-service-booking' ); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody >
                                        <?php  
                                            $index = 0;
                                            foreach($emsb_order_list as $emsb_order_list) : 
                                                $index = $index + 1;
                                        ?>
                                            <tr>
                                                <td><?php echo $index; ?></td>
                                                <td><?php echo $emsb_order_list['id']; ?></td>
                                                <td><?php echo $emsb_order_list['service_name']; ?></td>
                                                <td><?php echo $emsb_order_list['customer_name']; ?></td>
                                                <td><?php echo $emsb_order_list['customer_phone']; ?></td>
                                                <td><?php echo $emsb_order_list['customer_email']; ?></td>
                                                <td><?php echo $emsb_order_list['booked_date']; ?></td>
                                                <td><?php echo $emsb_order_list['booked_time_slot']; ?></td>
                                                <td><span> Confirmada </span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    
                                    <tbody>
                                </table>
                            </form>                      
                                <!--    Start Pagination -->
                                <div class='pagination-container'>
                                    <nav aria-label="Page navigation example">
                                        <ul class="pagination">
                                            <!--	Here the JS Function Will Add the Rows -->
                                        </ul>
                                    </nav>
                                </div>
                            <div class="rows_count"><?php _e( 'Showing 11 to 20 of 91 entries ', 'emsb-service-booking' ); ?></div>
                        
                        </div> <!-- End of Container -->

                    </div>


                </main>
            </div>
        <?php 
    }


    public static function emsb_admin_settings_page_func() {
            global $wpdb;
            $emsb_settings_data = $wpdb->prefix . 'emsb_settings';
    
            $emsb_plugin_path = plugin_dir_url( __FILE__ );
            $emsb_icon_url = $emsb_plugin_path . 'assets/img/service-booking.png';
            
            if(isset($_POST['emsb_save_admin_email_data'])){
                $admin_mail_subject = stripslashes_deep($_POST['emsb_admin_email_subject']);
                $admin_mail_body = stripslashes_deep($_POST['emsb_admin_email_body']);
                $emsb_customer_pending_email_subject = stripslashes_deep($_POST['emsb_customer_pending_email_subject']);
                $emsb_customer_pending_email_body = stripslashes_deep($_POST['emsb_customer_pending_email_body']);
                $emsb_customer_confirmed_email_subject = stripslashes_deep($_POST['emsb_customer_confirmed_email_subject']);
                $emsb_customer_confirmed_email_body = stripslashes_deep($_POST['emsb_customer_confirmed_email_body']);
                $emsb_customer_cancelled_email_subject = stripslashes_deep($_POST['emsb_customer_cancelled_email_subject']);
                $emsb_customer_cancelled_email_body = stripslashes_deep($_POST['emsb_customer_cancelled_email_body']);
                
                $customer_cookie_duration = stripslashes_deep($_POST['emsb_customer_cookie_duration']);
                // Securly insert data with $wpdb->inert method preventing the sql injection and also escaping strings
                $wpdb->insert($emsb_settings_data, array(
                    'admin_mail_subject' => $admin_mail_subject,
                    'admin_mail_body' => $admin_mail_body,
                    'customer_mail_pending_subject' => $emsb_customer_pending_email_subject,
                    'customer_mail_pending_body' => $emsb_customer_pending_email_body,
                    'customer_mail_confirmed_subject' => $emsb_customer_confirmed_email_subject,
                    'customer_mail_confirmed_body' => $emsb_customer_confirmed_email_body,
                    'customer_mail_cancel_subject' => $emsb_customer_cancelled_email_subject,
                    'customer_mail_cancel_body' => $emsb_customer_cancelled_email_subject,
                    'customer_cookie_duration' => $customer_cookie_duration
                ));
                
            };
    
            // When the page loads fetch data from database
            $emsb_settings_data_fetch = $wpdb->get_row( "SELECT * FROM $emsb_settings_data ORDER BY id DESC LIMIT 1" );
    
            // When settings data is changed fetch new data from database
            $emsb_check_changes = isset($_POST['emsb_save_admin_email_data']);
    
            if($emsb_check_changes){
                $emsb_settings_data_fetch = $wpdb->get_row( "SELECT * FROM $emsb_settings_data ORDER BY id DESC LIMIT 1" );
            }
    
            $fetch_admin_mail_subject = $emsb_settings_data_fetch->admin_mail_subject;
            $fetch_admin_mail_body = $emsb_settings_data_fetch->admin_mail_body;
            $fetch_emsb_customer_pending_email_subject = $emsb_settings_data_fetch->customer_mail_pending_subject;
            $fetch_emsb_customer_pending_email_body = $emsb_settings_data_fetch->customer_mail_pending_body;
            $fetch_emsb_customer_confirmed_email_subject = $emsb_settings_data_fetch->customer_mail_confirmed_subject;
            $fetch_emsb_customer_confirmed_email_body = $emsb_settings_data_fetch->customer_mail_confirmed_body;
            $fetch_emsb_customer_cancelled_email_subject = $emsb_settings_data_fetch->customer_mail_cancel_subject;
            $fetch_emsb_customer_cancelled_email_body = $emsb_settings_data_fetch->customer_mail_cancel_body;
            $fetch_customer_cookie_duration = $emsb_settings_data_fetch->customer_cookie_duration;
            
    
            ?>
            <div class="emsb-container">
                <header class="emsb-admin-main-page-header-wrapper">
                    <div class="jumbotron text-center">
                        <div class="emsb-admin-plugin-title">
                            <img src="<?php echo $emsb_icon_url; ?>" alt="Service Booking Icon">
                            <h2 class="display-5"> <?php _e( 'Sistema de Consultas CEMEQ', 'emsb-service-booking' ); ?></h2>
                        </div>
                    </div>
                </header>
                <main class="emsb-admin-main-page-wrapper">
                    <div class="tabs">
                        <ul>
                            <li><a href="admin.php?page=emsb_admin_page"> <?php _e( 'Marcação ', 'emsb-service-booking' ); ?></a></li>
                            <li><a href="edit.php?post_type=emsb_service"><?php _e( 'Médicos  ', 'emsb-service-booking' ); ?></a></li>
                            <li><a href="post-new.php?post_type=emsb_service"><?php _e( 'Novo Médico  ', 'emsb-service-booking' ); ?></a></li>
                            <li><a href="admin.php?page=emsb_admin_bookings_page"><?php _e( 'Consultas  ', 'emsb-service-booking' ); ?></a></li>
                            <li><a href="admin.php?page=emsb_admin_settings_page" class="active"><?php _e( 'Configurações  ', 'emsb-service-booking' ); ?></a></li>
                        </ul>
                    </div>
                    
                    <form method="post">
                        <div class="emsb-email-notification-data-wrapper container">
                        <!-- Admin Email Notification data starts-->
                            <div class="emsb-admin-email-data-form">
                                <div class="card">
                                    <div class="card-header">
                                        <?php _e( 'Notificação de Solicitação para o Médico', 'emsb-service-booking' ); ?>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="emsb_admin_email_subject"><?php _e( 'Assunto  ', 'emsb-service-booking' ); ?></label>
                                            <input type="text" name="emsb_admin_email_subject" class="form-control" id="emsb_admin_email_subject" value="<?php echo $fetch_admin_mail_subject; ?>" placeholder="Exemplo: Solicitação de Agendamento">
                                        </div>
                                        <div class="form-group">
                                            <label for="emsb_admin_email_body"><?php _e( 'Texto do E-mail  ', 'emsb-service-booking' ); ?></label>
                                            <textarea class="form-control" name="emsb_admin_email_body" id="emsb_admin_email_body" rows="5" placeholder="Exemplo: Uma solicitação de consulta foi efetuada, verifique as Marcações para aceitar ou recusar!"><?php echo $fetch_admin_mail_body; ?></textarea>
                                        </div>
                                        <footer class="blockquote-footer"><?php _e( 'O Médico receberá este E-mail assim que uma solicitação de consulta for efetuada. ', 'emsb-service-booking' ); ?> </footer>
                                    </div>
                                </div>
                            </div>
                             <!-- Admin Email Notification ends -->
    
                            <!-- Customer Email Notification data starts -->
                            <div class="emsb-customer-email-data-form mt-5">
                                <div class="card">
                                    <div class="card-header">
                                        <?php _e( 'Notificação de Solicitação do Paciente ', 'emsb-service-booking' ); ?>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="emsb_customer_pending_email_subject"><?php _e( 'Assunto  ', 'emsb-service-booking' ); ?></label>
                                            <input type="text" name="emsb_customer_pending_email_subject" class="form-control" id="emsb_customer_pending_email_subject" value="<?php echo $fetch_emsb_customer_pending_email_subject; ?>" placeholder="Exemplo: Solicitação enviada!">
                                        </div>
                                        <div class="form-group">
                                            <label for="emsb_customer_pending_email_body"><?php _e( 'Texto do E-mail  ', 'emsb-service-booking' ); ?></label>
                                            <textarea class="form-control" name="emsb_customer_pending_email_body" id="emsb_customer_pending_email_body" rows="5" placeholder="Exemplo: Sua solicitação foi enviada para o nosso sistema, aguarde a confirmação da consulta!"><?php echo $fetch_emsb_customer_pending_email_body; ?></textarea>
                                        </div>
                                        <footer class="blockquote-footer"> <?php _e( 'O Paciente receberá este E-mail assim que efetuar seu agendamento ', 'emsb-service-booking' ); ?></footer>
                                    </div>
                                </div>
                            </div>
                            <!-- Customer Email Notification data ends -->
    
                            <!-- Customer Email Notification on booking confirmation -->
                            <div class="emsb-customer-email-data-form mt-5">
                                <div class="card">
                                    <div class="card-header">
                                        <?php _e( 'Notificação de Confirmação do Agendamento ', 'emsb-service-booking' ); ?>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="emsb_customer_confirmed_email_subject"><?php _e( 'Assunto ', 'emsb-service-booking' ); ?></label>
                                            <input type="text" name="emsb_customer_confirmed_email_subject" class="form-control" id="emsb_customer_confirmed_email_subject" value="<?php echo $fetch_emsb_customer_confirmed_email_subject; ?>" placeholder="Exemplo: Sua consulta foi confirmada!">
                                        </div>
                                        <div class="form-group">
                                            <label for="emsb_customer_confirmed_email_body"><?php _e( 'Texto do E-mail ', 'emsb-service-booking' ); ?></label>
                                            <textarea class="form-control" name="emsb_customer_confirmed_email_body" id="emsb_customer_confirmed_email_body" rows="5" placeholder="Exemplo: Esteja presente  no dia e no horário agendado!"><?php echo $fetch_emsb_customer_confirmed_email_body; ?></textarea>
                                        </div>
                                        <footer class="blockquote-footer"> <?php _e( 'O Paciente receberá este E-mail assim que sua solicitação for confirmada', 'emsb-service-booking' ); ?></footer>
                                    </div>
                                </div>
                            </div>
                            <!-- Customer Email Notification on booking confirmation ends -->
    
                            <!-- Customer Email Notification on booking Cancellation -->
                            <div class="emsb-customer-email-data-form mt-5">
                                <div class="card">
                                    <div class="card-header">
                                        <?php _e( 'Notificação de cancelamento do Agendamento ', 'emsb-service-booking' ); ?>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="emsb_customer_cancelled_email_subject"><?php _e( 'Assunto  ', 'emsb-service-booking' ); ?></label>
                                            <input type="text" name="emsb_customer_cancelled_email_subject" class="form-control" id="emsb_customer_cancelled_email_subject" value="<?php echo $fetch_emsb_customer_cancelled_email_subject; ?>" placeholder="Exemplo: Sua solicitação não pode ser aceita!">
                                        </div>
                                        <div class="form-group">
                                            <label for="emsb_customer_cancelled_email_body"><?php _e( 'Texto do E-mail  ', 'emsb-service-booking' ); ?></label>
                                            <textarea class="form-control" name="emsb_customer_cancelled_email_body" id="emsb_customer_cancelled_email_body" rows="5" placeholder="Exemplo: Sua solicitação de agendamento não pode ser aceita! "><?php echo $fetch_emsb_customer_cancelled_email_body; ?></textarea>
                                        </div>
                                        <footer class="blockquote-footer"> <?php _e( 'O Paciente receberá este E-mail assim que sua solicitação for cancelada', 'emsb-service-booking' ); ?></footer>
                                    </div>
                                </div>
                            </div>
                            <!-- Customer Email Notification on booking Cancellation ends -->
    
                            <!-- User Cookie -->
                            <div class="emsb-customer-email-data-form mt-5">
                                <div class="card">
                                    <div class="card-header">
                                        <?php _e( 'Cookies  ', 'emsb-service-booking' ); ?>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="emsb_customer_cookie_duration"><?php _e( 'Por quantos dias você deseja salvar as informações do paciente no cookie do navegador?  ', 'emsb-service-booking' ); ?></label>
                                            <input type="number" name="emsb_customer_cookie_duration"id="emsb_customer_cookie_duration" value="<?php echo $fetch_customer_cookie_duration; ?>" class="form-control"  placeholder="30">
                                        </div>
                                        <footer class="blockquote-footer">  <?php _e( 'Não altere com frequência! ', 'emsb-service-booking' ); ?> </footer>
                                    </div>
                                </div>
                            </div>
                            <!-- User Cookie data ends -->
                            <button name="emsb_save_admin_email_data" type="submit" class="btn btn-primary mt-3"> <?php _e( 'Salvar ', 'emsb-service-booking' ); ?></button>
                        </div>
                        
                    </form>
                </main>
            </div>
            
                
            <?php
    
    }
	

	protected static function list_globals()
	{
		print '<h2>Global variables</h2><table class="code">';
		ksort( $GLOBALS );
		foreach ( $GLOBALS as $key => $value )
		{
			print '<tr><td>$' . esc_html( $key ) . '</td><td>';
			if ( ! is_scalar( $value ) )
			{
				print '<var>' . gettype( $value ) . '</var>';
			}
			else
			{
				if ( FALSE === $value )
					$show = '<var>FALSE</var>';
				elseif ( '' === $value )
				$show = '<var>""</var>';
				else
					$show = esc_html( $value );
				print $show;
			}
			print '</td></tr>';
		}
		print '</table>';
	}

	protected static function list_backtrace( $backtrace )
	{
		print '<h2>debug_backtrace()</h2><ol class="code">';
		foreach ( $backtrace as $item )
		{
			print '<li>';
			if ( isset ( $item['class'] ) )
				print $item['class'] . $item['type'];
			print $item['function'];
			if ( isset ( $item['args'] ) )
				print '<pre>args = ' . print_r( $item['args'], TRUE ) . '</pre>';
			if ( isset ( $item['file'] ) )
				print '<br>' . $item['file'] . ' line: ' . $item['line'];
			print "\n";
		}
		print '</ol>';
	}
}