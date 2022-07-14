<?php 
    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }

    /**
     * Template for the email form
     * 
     * @since   1.0.0
     * @author  Dennis Weijer
     */
?>

<div class="ece-form">
    <div class="form-field form-field-wide">
        <label for=""><?php esc_html_e( "Customer Email:", "e-customer-emails" ); ?></label>
        <input type="email" disabled="disabled" value="<?php echo esc_attr( sanitize_email( $billing_email ) ); ?>">
    </div>
    <br />
    <div class="form-field form-field-wide">
        <label for=""><?php esc_html_e( "Email Subject:", "e-customer-emails" ); ?></label>
        <input type="text" id="ece-subject" minlength="10" maxlength="75">
    </div>
    <br />
    <div class="form-field form-field-wide">
        <label for=""><?php esc_html_e( "Message:", "e-customer-emails" ); ?></label>
        <?php wp_editor( '', 'ece-message', [ 'wpautop' => false ] ); ?>
        <br />
        <span class="description">
            <?php esc_html_e( "Type the following in your message:", "e-customer-emails" ); ?>
            <ul>
                <li>&emsp;&emsp;<strong>[first_name]</strong> - <?php esc_html_e( "To insert the first name of the client.", "e-customer-emails" ); ?></li>
                <li>&emsp;&emsp;<strong>[last_name]</strong> - <?php esc_html_e( "To insert the last name of the client.", "e-customer-emails" ); ?></li>
                <li>&emsp;&emsp;<strong>[full_name]</strong> - <?php esc_html_e( "To insert the full name of the client.", "e-customer-emails" ); ?></li>
            </ul>
        </span>
    </div>
    <br />
</div>

<div style="overflow: auto;">
    <div id="publishing-action">
        <a class="ece_send_email_button button button-primary" data-nonce="<?php echo esc_attr( $nonce ); ?>" data-post_id="<?php echo esc_attr( $PostID ); ?>" href="<?php echo esc_attr( $link ); ?>"><?php esc_html_e( "Send email", "e-customer-emails" ); ?></a>
        <span class="ece-spinner spinner"></span>
    </div>
</div>
<p id="ece-response-text-success" style="color: green;"></p>
<ul id="ece-response-text-error" style="color: red;"></ul>