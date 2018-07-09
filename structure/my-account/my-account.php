<?php
/**
 * This file contains all functionality regarding the my-account page.
 */

/**
 * Set templates for my-account pages.
 */    
function my_account_template( $page_template ) {
    if ( is_page( 'Mijn Account' ) ) {
        $page_template = plugin_dir_path( __FILE__ ) . '/template.blade.php';
    } else if ( is_page( 'Wijzig Wachtwoord' ) ) {
        $page_template = plugin_dir_path( __FILE__ ) . '/change-password.blade.php';
    } else if ( is_page( 'Beheer Vacatures' ) ) {
        $page_template = plugin_dir_path( __FILE__ ) . '/manage-vacancies.blade.php';
    } else if ( is_page( 'Nieuwe Vacature' ) ) {
        $page_template = plugin_dir_path( __FILE__ ) . '/new-vacancy.blade.php';
    } else if ( is_page( 'Bewerk Vacature' ) ) {
        $page_template = plugin_dir_path( __FILE__ ) . '/edit-vacancy.blade.php'; 
    } else if ( is_page( 'Favorieten' ) ) {
        $page_template = plugin_dir_path( __FILE__ ) . '/favorites.blade.php';
    } else if ( is_page( 'Reacties' ) ) {
        $page_template = plugin_dir_path( __FILE__ ) . '/applications.blade.php';
    } 
    return $page_template;
}
add_filter( 'page_template', 'my_account_template' );
 
/**
 * Output the my account menu.
 */

// We do this with a function because blade templating can't do relative pathing.
function my_account_menu(){
    if(is_user_logged_in()){
        $user = wp_get_current_user();
        $role = ( array ) $user->roles;
        if($role[0] == 'organisation'){
        ?>
        <ul id="account-menu">
            <li><a href="/mijn-account">Profiel</a></li>
            <li><a href="/beheer-vacatures">Beheer vacatures</a></li>
            <li><a href="/nieuwe-vacature">Nieuwe vacature plaatsen</a></li>
            <li><a href="/wijzig-wachtwoord">Wijzig wachtwoord</a></li>
            <li><a href="<?php echo wp_logout_url( home_url() ); ?>">Logout</a></li>
        </ul>
        <?php
        } else if($role[0] == 'volunteer'){
        ?>
        <ul id="account-menu">
            <li><a href="/mijn-account">Profiel</a></li>
            <li><a href="/reacties">Reacties</a></li>
            <li><a href="/favorieten">Favorieten</a></li>
            <li><a href="/wijzig-wachtwoord">Wijzig wachtwoord</a></li>
            <li><a href="<?php echo wp_logout_url( home_url() ); ?>">Logout</a></li>
        </ul>
        <?php
        } else{
            echo 'Uw account is geen vrijwilliger of organisatie.';
        }
    } else{
        echo 'Je moet ingelogd zijn om deze pagina te bekijken.';
    }
}

/**
 * Sync usermeta fields in acf_form to usermeta.
 */
function usermeta_acf_save_post( $post_id ) {
    // Bail early if no ACF data
    if(empty($_POST['acf'])) {
        return;
    }
    // Bail early if editing in admin
    if(is_admin()) {
        return;
    }
    if( $_POST['post_id'] != 'new' ) {
        $emailField = $_POST['acf']['field_acf_form_email'];
        $wp_user_id = str_replace("user_", "", $post_id);
        if (isset($emailField)) {
            if (email_exists($emailField)){
                // Email exists for another account. 
                // TODO: Output error here
            } else {
                $args = array(
                    'ID' => $wp_user_id,
                    'user_email' => esc_attr($emailField)
                );            
                wp_update_user($args);
            }  
        } 	
    }
    // return the ID
    return $post_id;
}
add_action('acf/save_post', 'usermeta_acf_save_post', 20);

/**
 * Sync wordpress email to custom field when user email is updated in back-end.
 */
function sync_custom_field_on_change($user_id, $old_user_data) {
    $user = get_userdata($user_id);
    if($old_user_data->user_email != $user->user_email) {
        update_field('field_acf_form_email', $user->user_email, 'user_'.$user_id);
    }
}
add_action('profile_update', 'sync_custom_field_on_change', 10, 2);

/**
 * Sync WordPress email on creation to custom field.
 */
function sync_custom_field_on_registration( $user_id ) {
    $user = get_userdata( $user_id );
    update_field('field_acf_form_email', $user->user_email, 'user_'.$user_id);
}
add_action( 'user_register', 'sync_custom_field_on_registration', 10, 1 );