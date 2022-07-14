<?php
/**
 * Plugin Name:       E-Customer Emails
 * Plugin URI:        
 * Description:       Send an email easily to a client via the orders menu of WooCommerce
 * Version:           1.0.0
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            Dennis Weijer
 * Author URI:        
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       e-customer-emails
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Function to check if WooCommerce is active
 * 
 * @since   1.0.0
 * @author  Dennis Weijer
 */
register_activation_hook( __FILE__, 'ECE_activation' );
if ( ! function_exists( 'ECE_activation' ) ) {
    function ECE_activation() {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            wp_die( esc_html__( 'You don\'t have permissions to activate this plugin!', 'e-customer-emails' ) );
        } else {
            if (
                ! in_array( 
                    'woocommerce/woocommerce.php',
                    apply_filters( 'active_plugins', get_option( 'active_plugins' ) )
                 )
             ) {
                deactivate_plugins( 'WooCustomerEmails/WooCustomerEmails.php' );
                wp_die( '<strong>Woo Customer Emails: </strong> ' . esc_html__( 'You need to install the plugin ', 'e-customer-emails' ) . '<em>WooCommerce</em> ' . esc_html__( 'before you can use this plugin.', 'e-customer-emails' ) );
             }
        }
    }
}

/**
 * Function to disable this plugin once WooCommerce is being disabled
 * 
 * @since   1.0.0
 * @author  Dennis Weijer
 */
add_action( 'deactivated_plugin', 'ECE_deactive_woo_not_active', 10, 2 );
if ( ! function_exists( 'ECE_deactive_woo_not_active' ) ) {
    function ECE_deactive_woo_not_active( $plugin, $network_activation ) {
        if ( $plugin == "woocommerce/woocommerce.php" ) {
            deactivate_plugins( [plugin_basename( __FILE__ ), "woocommerce/woocommerce.php"], true );
            error_log( 'This plugin has been deactivated because you have deactivated WooCommerce' );
            $args = var_export( func_get_args(), true );
            error_log( $args );
            wp_die( '<strong>Woo Customer Emails: </strong> ' . esc_html__( 'This plugin is deactivated, because you deactivated ', 'e-customer-emails' ) . '<em>WooCommerce</em>' );
        }
    }
}

/**
 * Function to create an Email metabox into the order section of WooCommerce
 * 
 * @since   1.0.0
 * @author  Dennis Weijer
 */
add_action( 'add_meta_boxes', 'ECE_add_meta_boxes' );
if ( ! function_exists( 'ECE_add_meta_boxes' ) ) {
    function ECE_add_meta_boxes() {
        add_meta_box(
            'ECE_email_field',
            esc_html__( 'Email Client', 'e-customer-emails' ),
            'ECE_add_email_field',
            'shop_order',
            'normal',
            'high'
        );
    }
}

/**
 * Function to create the content of the Email metabox
 * 
 * @since   1.0.0
 * @author  Dennis Weijer
 */
if ( ! function_exists( 'ECE_add_email_field' ) ) {
    function ECE_add_email_field() {
        global $post;

        $PostID = $post->ID;

        $nonce = wp_create_nonce( 'ECE_email_form_nonce' );
        $link = sanitize_url( admin_url('admin-ajax.php?action=ece_send_email&post_id='.$PostID.'&nonce='.$nonce) );

        $order = wc_get_order( $post->ID );
        $customer_id = sanitize_key( $order->get_customer_id() );
        
        $user = $order->get_user();
        $billing_email  = sanitize_email( $order->get_billing_email() );

        ob_start();
        include( plugin_dir_path( __FILE__ ) . 'templates/ece-email-form.php' );
        echo ob_get_clean();
    }
}

/**
 * Function to handle the AJAX Request when 'Send Email' button is clicked
 * 
 * @since   1.0.0
 * @author  Dennis Weijer
 */
add_action( "wp_ajax_ece_send_email", "ece_send_email" );
function ece_send_email() {

    // Nonce check for an extra layer of security, the function will exit if it fails
    if ( ! wp_verify_nonce( sanitize_key( $_REQUEST[ 'nonce' ] ), "ECE_email_form_nonce" ) ) {
        exit( "Woof Woof Woof" );
    }

    // Setting standard result, if no other result is given by this function
    $result['type'] = "error";
    $result['msg'] = esc_html__( "No output given. Please contact a developer.", "e-customer-emails" );

    // Getting all the values, sanitize them, replace strings (if neccesary), and generate errors if there are any
    $errors = [];

    $post_id = sanitize_key( $_REQUEST[ 'post_id' ] );
    if ( empty( $post_id ) ) {
        $errors[] = "No valid post id passed!";
    }

    $order = wc_get_order( $post_id );
    $customer_id = sanitize_key( $order->get_customer_id() );
    
    $billing_email  = sanitize_email( $order->get_billing_email() );
    $first_name = wp_kses_post( $order->get_billing_first_name() );
    $last_name = wp_kses_post( $order->get_billing_last_name() );

    $gottenSubject = wp_kses_post( $_REQUEST['subject'] );

    $gottenMessage = wp_kses_post( $_REQUEST['message'] );
    $gottenMessage = str_replace( '&nbsp;', '<br />', $gottenMessage );
    $gottenMessage = str_replace( '[first_name]', $first_name, $gottenMessage );
    $gottenMessage = str_replace( '[last_name]', $first_name, $gottenMessage );
    $gottenMessage = str_replace( '[full_name]', $first_name . ' ' . $last_name, $gottenMessage );

    $SubjectLength = strlen( $gottenSubject );
    $gottenMessageLength = strlen( $gottenMessage );

    if ( empty( $gottenSubject) || $gottenSubject == '' ) {
        $errors[] = esc_html__( "Please enter an Email Subject!", "e-customer-emails" );
    } else {
        if ( $SubjectLength < 10 || $SubjectLength > 75 ) {
            $errors[] = esc_html__( "The length of the subject needs to be between 10 and 75 characters!", "e-customer-emails" );
        }
    }

    if ( empty( $gottenMessage ) || $gottenMessage == '' ) {
        $errors[] = esc_html__( "Please enter an Email message!", "e-customer-emails" );
    } else {
        if ( $gottenMessageLength < 17 ) {
            $errors[] = esc_html__( "The length of the message needs to be be 10 characters or more!", "e-customer-emails" );
        }
    }

    // If there are no errors
    if ( empty( $errors ) ) {
        // Generate the email
        $current_user = wp_get_current_user();

        $to = esc_html( $billing_email );
        $subject = $gottenSubject;
        $message = $gottenMessage;
        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $headers .= 'From: '. esc_html( $current_user->display_name ) .' <'. sanitize_email( $current_user->user_email ) .'>' . "\r\n";
        $headers .= 'To: '. $first_name . ' ' . $last_name .' <'. $billing_email .'>' . "\r\n";

        // Send the email and check if it was sent successfully
        if ( wp_mail( $to, $subject, $message, $headers ) ) {
            $result['type'] = "success";
            $result['msg'] = esc_html__( "Email successfully sent!", "e-customer-emails" );
        } else {
            $result['type'] = "error";
            $result['msg'] = [  esc_html__( "Email could not be sent!", "e-customer-emails" ) ];
        }
    } 
    // If there are errors
    else {
        $result['msg'] = $errors;
    }

    // Check if action was fired via Ajax call. If yes, JS code will be triggered. Else the user is redirected to the post page
    if( ! empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest' ) {
        $result = json_encode( $result );
        echo wp_kses_post( $result );
    } else {
        header( "Location: " . sanitize_url( $_SERVER['HTTP_REFERER'] ) );
    }

    // Don't forget to end your scripts with a die() function - very important
    wp_die();
}

/**
 * Function to enqueue all the scripts
 * 
 * @since   1.0.0
 * @author  Dennis Weijer
 */
add_action( 'init', 'ECE_script_enqueue' );
function ECE_script_enqueue() {

    // Register JS files with a unique handle, file location, and an array of dependencies
    wp_register_script( "send_email_script", plugin_dir_url( __FILE__ ) . 'js/ece-send-email.js', array('jquery') );
    
    // Localize scripts to the domain name, so that we can reference the url to admin-ajax.php file easily
    wp_localize_script( "send_email_script", 'ECE_Ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

    // Enqueue jQuery library and all the scripts above
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'send_email_script' );
}