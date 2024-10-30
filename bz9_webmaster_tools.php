<?php

/*
Plugin Name: BZ9 Webmaster Tools
Plugin URI: http://bz9.com
Description: The BZ9 WordPress Plugin simplifies the installation and management of over 17 affiliate and internet marketing tools for your blogs and WebPages.
Version: 1.9.1
Author: BZ9.com
Author URI: http://bz9.com
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

class bz9_tools_shortcode {

    private $handles = array();

    /**
     * Initial setup
     */
    function __construct() {
        if( is_admin() )
        {
            add_action( 'wp_ajax_bz9wt_shortpop', array( $this, 'bz9wt_shortpop_ajax' ) );
            add_action( 'init', array(&$this, 'bz9wt_custom_init' ), 9 );
            add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
            add_action( 'save_post', array( $this, 'save' ) );
            add_filter( 'manage_edit-bz9_tools_columns', array( $this, 'bz9wt_columns' ) ) ;
            add_action( 'manage_posts_custom_column', array( $this, 'bz9wt_populate_columns' ) );
            add_action( 'init', array(&$this, 'add_editor_button' ) );
            add_action( 'admin_menu', array( $this, 'bz9wt_register_submenu_page' ) );
            add_filter( 'enter_title_here', array( $this, 'bz9wt_enter_title_here' ) );
            if ( ( isset( $_GET['post_type'] ) && $_GET['post_type'] == 'bz9_tools' ) || ( isset( $post_type ) && $post_type == 'bz9_tools' ) || ( isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) == 'bz9_tools' ) )
            {
                add_action('admin_enqueue_scripts', array( $this, 'bz9wt_admin_scripts' ) );
            }
            add_action( 'admin_head', array( $this, 'bz9wt_plugin_header' ) );
            add_filter( 'post_row_actions', array( $this, 'bz9wt_remove_quick_edit' ) );
            add_filter( 'post_updated_messages', array( $this, 'bz9wt_updated_messages' ) );
        } else {
            add_shortcode( 'bz9_webmaster_tools', array(&$this, 'js_shortcode' ) );
        }
    }

    /**
     * Include popup window content
     */
    public function bz9wt_shortpop_ajax(){
        if ( !current_user_can( 'edit_pages' ) && !current_user_can( 'edit_posts' ) )
            die(__('You are not allowed to be here', 'bz9-webmaster-tools'));

        include_once('form.php');
        die();
    }

    function startsWith($haystack, $needle)
    {
        return strpos($haystack, $needle) === 0;
    }

    /**
     * Shortcode handler
     */
    function js_shortcode( $atts = array(), $content = null ){
        $out = '';
        extract( shortcode_atts( array(
            'style' => '',
            'saved' => '',
            'type'  => '',
        ), $atts ) );

        if ($saved != '')
        {
            $saved_tool = get_post_meta( $saved, 'bz9wt_tool', true );

            preg_match_all( '/var.+?;/i', $saved_tool, $matches_var );
            if( is_array( $matches_var[0] ) )
            {
               $var_sc = '';
                foreach( $matches_var[0] as $vkey => $vvalue )
                {
                    $var_sc .= $vvalue;
                }
                $out .= $this->process_sc( $var_sc, '', 'vars' );
            }

            preg_match_all( '/src=[\'"]([^\'"]+)[\'"]/i', $saved_tool, $matches );
            $content = $matches[1];
        }

        if( $content )
        {
            if( is_array( $content ) )
            {
                foreach ( $content as $key => $content_new )
                {
                    $out .= $this->process_sc( $content_new );
                }

            } else {
                $out .= $this->process_sc($content, $style, $type);
            }
        }
    return $out;
    }

    /**
     * Process shortcode
     */
    private function process_sc( $tool, $style=null, $type=null ){
        $handle = "bz9tool_".uniqid();
        $deps = "";
        $ver = "";

        //process vars sc
        if( $type == 'vars' )
        {
            $tool = str_replace( '&lt;','<',$tool );
            $tool = str_replace( '&gt;','>',$tool );
            $html = "<script type='text/javascript'>$tool</script>";
            return $html;
        }

        //validate url
        if( !filter_var( $tool, FILTER_VALIDATE_URL ) )
        {
            return;
        }

        //host test
        $res = parse_url( $tool );
        $allowed_host = array( "bz9.com","forms.aweber.com" );
        if( !in_array( $res['host'],$allowed_host ) )
        {
            return;
        }

        if( $style != "" )
        {
            //iframe code
            $html = '<iframe id="follow_container" frameborder="0" style="'.$style.'" allowtransparency="true" src="'.$tool.'"></iframe>';
            return $html;

        }

        //aweber
       if( $res['host'] == "forms.aweber.com" )
        {
            $html = "<script type='text/javascript' src='$tool'></script>";
            return $html;
        }

            //register the script
            wp_register_script( $handle, $tool, $deps, null, TRUE );
            //add the script to the array of handles
            $this->handles[] = $handle;

        add_action( 'wp_print_footer_scripts', array($this, 'call_js') );
    }

    /**
     * Print footer scripts
     */
    function call_js(){
        wp_print_scripts( $this->handles );
    }

    /**
     * Register editor button
     */
    function register_button( $buttons ) {
        array_push( $buttons, "|", "bz9_tools" );
        return $buttons;
    }

    /**
     * Add editor plugin
     */
    function add_plugin( $plugin_array ) {
        $plugin_array['bz9_tools'] = WP_PLUGIN_URL.'/bz9-webmaster-tools/js/bz9_webmaster_tools.js';
        return $plugin_array;
    }

    /**
     * Add editor button
     */
    function add_editor_button() {

        if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
            return;
        }

        if ( get_user_option( 'rich_editing' ) == 'true' ) {

            add_filter( 'mce_external_plugins', array( &$this, 'add_plugin' ) );
            add_filter( 'mce_buttons', array( &$this, 'register_button' ) );
        }

    }

    /**
     * Custom post setup
     */
    function bz9wt_custom_init() {

        load_plugin_textdomain('bz9-webmaster-tools', false, dirname(plugin_basename(__FILE__)) . '/languages/');

        $labels = array(
            'name' => __('Your Saved BZ9 Tools', 'bz9-webmaster-tools'),
            'singular_name' => __('BZ9 Tool', 'bz9-webmaster-tools'),
            'add_new' => __('Add New BZ9 Tool', 'bz9-webmaster-tools'),
            'add_new_item' => __('Add New BZ9 Tool', 'bz9-webmaster-tools'),
            'edit_item' => __('Edit BZ9 Tool', 'bz9-webmaster-tools'),
            'new_item' => __('New BZ9 Tool', 'bz9-webmaster-tools'),
            'all_items' => __('Saved BZ9 Tools', 'bz9-webmaster-tools'),
            'view_item' => __('View BZ9 Tool', 'bz9-webmaster-tools'),
            'search_items' => __('Search BZ9 Tools', 'bz9-webmaster-tools'),
            'not_found' =>  __('No BZ9 Tools found', 'bz9-webmaster-tools'),
            'not_found_in_trash' => __('No BZ9 Tools found in Trash', 'bz9-webmaster-tools'),
            'parent_item_colon' => '',
            'menu_name' => __('BZ9 Tools', 'bz9-webmaster-tools')
        );

        $args = array(
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'menu_position' => 5,
            'query_var' => false,
            'rewrite' => false,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => null,
            'menu_icon' => plugins_url( 'bz9-webmaster-tools/images/bz9tools.png' ),
            'supports' => array( 'title')
        );

        register_post_type( 'bz9_tools', $args );
    }

    /**
     * Set custom messages
     */
    function bz9wt_updated_messages( $messages ) {
        global $post, $post_ID;
        $messages['bz9_tools'] = array(
            0 => '',
            1 => __('Your BZ9 tool has been updated.', 'bz9-webmaster-tools'),
            2 => __('Custom field updated.', 'bz9-webmaster-tools'),
            3 => __('Custom field deleted.', 'bz9-webmaster-tools'),
            4 => __('Your BZ9 tool has been updated.', 'bz9-webmaster-tools'),
            5 => isset($_GET['revision']) ? sprintf( __('Tool restored to revision from %s', 'bz9-webmaster-tools'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
            6 => __('Your BZ9 tool has been saved.', 'bz9-webmaster-tools'),
            7 => __('Your BZ9 tool has been saved.', 'bz9-webmaster-tools'),
            8 => sprintf( __('Tool submitted.', 'bz9-webmaster-tools') .'<a target="_blank" href="%s">' .__('Preview Tool', 'bz9-webmaster-tools') .'</a>', esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
            9 => sprintf( __('Tool scheduled for', 'bz9-webmaster-tools') .': <strong>%1$s</strong>. <a target="_blank" href="%2$s">'. __('Preview Tool', 'bz9-webmaster-tools') .'</a>', date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
            10 => sprintf( __('Tool draft updated', 'bz9-webmaster-tools') .'. <a target="_blank" href="%s">'. __('Preview Tool', 'bz9-webmaster-tools') .'</a>', esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
        );
        return $messages;
    }

    /**
     * Adds the meta box container
     */
    public function add_meta_box() {
        add_meta_box(
            'bz9wt_descr',
            __('Tool Details', 'bz9-webmaster-tools'),
            array( &$this, 'render_meta_box_content' ),
            'bz9_tools',
            'normal',
            'high'
        );
    }

    /**
     * Render Meta Box content
     */
    public function render_meta_box_content( $post ) {
        // Use nonce for verification
        wp_nonce_field( plugin_basename( __FILE__ ), 'bz9wt_noncename' );

        $value = get_post_meta( $post->ID, 'bz9wt_descr', true );
        $value2 = get_post_meta( $post->ID, 'bz9wt_tool', true );

        echo '<label class="bz9wt_label" for="bz9wt_descr">';
        _e( 'Description', 'bz9-webmaster-tools' );
        echo '</label> ';
        echo '<input type="text" id="bz9wt_descr" name="bz9wt_descr" value="'.esc_attr( $value ).'" size="70" /><br/><br/>';

        echo '<p class="formfield"><label class="bz9wt_label" for="bz9wt_tool">';
        _e( 'Add or Edit BZ9 Webmaster Code', 'bz9-webmaster-tools');
        echo '<br><a href="http://bz9.com/client/new_webmaster.php" target="_blank">';
        _e( 'Click here to get BZ9 Webmaster Code', 'bz9-webmaster-tools' );
        echo '</a></label> ';
        echo '<textarea id="bz9wt_tool" name="bz9wt_tool" rows="4" cols="70">'.esc_attr( $value2 ).'</textarea></p>';
    }

    /**
     * Save Meta Box content
     */
    public function save( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }

        if ( ! isset( $_POST['bz9wt_noncename'] ) || ! wp_verify_nonce( $_POST['bz9wt_noncename'], plugin_basename( __FILE__ ) ) )
            return;

        //  we need to check if the current user is authorised to do this action.
        if ( 'bz9_tools' == $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_page', $post_id ) )
                return;
        } else {
            if ( ! current_user_can( 'edit_post', $post_id ) )
                return;
        }

        $post_ID = $_POST['post_ID'];
        //sanitize user input
        $mydata = sanitize_text_field( $_POST['bz9wt_descr'] );
        $mydata2 = stripslashes_deep($_POST['bz9wt_tool']);
        $valid_tool = $this->bz9wt_validate_tool( $mydata2 );
        // Do something with $mydata
        // either using
        if ( !add_post_meta( $post_ID, 'bz9wt_descr', $mydata, true ) ) {
            update_post_meta( $post_ID, 'bz9wt_descr', $mydata );
        }
        //if valid bz9 tool commit to db
        if ( $valid_tool ){
            if ( !add_post_meta( $post_ID, 'bz9wt_tool', $mydata2, true ) ) {
                update_post_meta( $post_ID, 'bz9wt_tool', $mydata2 );
            }
        }
    }

    /**
     * Validate user input
     */
    private function bz9wt_validate_tool( $content ){
        if ( !$content ) { return false; }
        preg_match_all( '/src=[\'"]([^\'"]+)[\'"]/i', $content, $matches );
        if( count( $matches ) > 0 ){
            foreach( $matches[1] as $key => $value){
                //validate url
                if( !filter_var( $matches[1][$key], FILTER_VALIDATE_URL ) )
                {
                    return false;
                }
                //host test
                $res = parse_url( $matches[1][$key] );
                $allowed_host = array( "bz9.com","forms.aweber.com" );
                if( !in_array( $res['host'],$allowed_host ) )
                {
                    return false;
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Change title text
     */
    function bz9wt_enter_title_here( $message ){
        global $post;
        if( 'bz9_tools' == $post->post_type ):
            $message = __('Enter Tool Name', 'bz9-webmaster-tools');
        endif;
        return $message;
    }

    /**
     * Add admin scripts / css
     */
    public function bz9wt_admin_scripts(){
        wp_enqueue_style ( 'bz9wt_admin_css', WP_PLUGIN_URL.'/bz9-webmaster-tools/css/bz9wt_admin.css' );
    }

    /**
     * Set admin page header
     */
    function bz9wt_plugin_header() {
        global $post_type;
        ?>
        <style>
            <?php if ( ( $_GET['post_type'] == 'bz9_tools' ) || ( $post_type == 'bz9_tools' ) ) : ?>
            #icon-edit { background:transparent url('<?php echo WP_PLUGIN_URL .'/bz9-webmaster-tools/images/bz9tools32.png';?>') no-repeat; }
            <?php endif; ?>
        </style>
    <?php
    }

    /**
     * Set saved columns
     */
    public function bz9wt_columns( $columns ){
        $new_columns['cb'] = '<input type="checkbox" />';
        $new_columns['title'] = __('Tool Name', 'bz9-webmaster-tools');
        $new_columns['description'] = __('Description', 'bz9-webmaster-tools');
        $new_columns['date'] = __('Date', 'bz9-webmaster-tools');

        return $new_columns;
    }

    /**
     * Populate columns
     */
    public function bz9wt_populate_columns( $column ){
        if ( 'description' == $column ) {
            $bz9wt_descr = esc_html( get_post_meta( get_the_ID(), 'bz9wt_descr', true ) );
            echo $bz9wt_descr;
        }
    }

    /**
     * Remove quick edit link
     */
    public function bz9wt_remove_quick_edit( $actions ){
        global $post;
        if( $post->post_type == 'bz9_tools' ) {
            unset( $actions['inline hide-if-no-js'] );
        }
        return $actions;
    }

    /**
     * Register pages
     */
    public function bz9wt_register_submenu_page(){
        add_submenu_page( 'edit.php?post_type=bz9_tools', __('How To Login & Open A FREE BZ9 Account', 'bz9-webmaster-tools'), __('Login To BZ9', 'bz9-webmaster-tools'), 'manage_options', 'bz9_tools_account', array( $this, 'bz9wt_account' ) );
        add_submenu_page( 'edit.php?post_type=bz9_tools', __('Setup BZ9 Tools', 'bz9-webmaster-tools'), __('Setup BZ9 Tools', 'bz9-webmaster-tools'), 'manage_options', 'bz9_tools', array( $this, 'bz9wt_about' ) );
        add_submenu_page( 'edit.php?post_type=bz9_tools', __('BZ9 Tutorials ... How To Use The BZ9 Tools Plugin', 'bz9-webmaster-tools'), __('BZ9 Tutorials', 'bz9-webmaster-tools'), 'manage_options', 'bz9_tools_tutorials', array( $this, 'bz9wt_tutorials' ) );
    }

    /**
     * Page headers
     */
    private function bz9wt_page_header(){
        ?>
        <div class="bz9wt_header_wrap">
        <div id="icon"><img src="<?php echo WP_PLUGIN_URL .'/bz9-webmaster-tools/images/bz9tools32.png';?>" /></div>
        <h2><?php echo get_admin_page_title(); ?></h2>
        </div>
        <?php
        return;
    }

    /**
     * About page
     */
    public function bz9wt_about(){
        $this->bz9wt_page_header();
        ?>
        <div align="center"><iframe width="650" height="520" src="http://www.viewbix.com/frame/67820c3e-a9de-445a-99d9-1b368a9bf238?w=650&h=520" frameborder="0" scrolling="no" allowTransparency="true"></iframe></div>
    <?php
    }

    /**
     * Account page
     */
    public function bz9wt_account(){
        $this->bz9wt_page_header();
        ?>
        <div align="center"><span style="font-family: Arial; font-weight: normal; font-style: normal; text-decoration: none; font-size: 12pt;"><?php _e('In order to create and edit your webmaster tools, please open a FREE BZ9 account or login to your existing account.', 'bz9-webmaster-tools'); ?></span></div><br>
        <div align="center"><a href="http://bz9.com/index.php/log-in/" target="_blank"><img border="0" src="http://bz9.com/images/login.png" alt="<?php _e('HTML tutorial', 'bz9-webmaster-tools'); ?>" width="735" height="174"></a></div>
        <br><br>
        <div align="center"><a href="http://bz9.com/index.php/new-account-free/" target="_blank"><img border="0" src="http://bz9.com/images/open.png" alt="<?php _e('HTML tutorial', 'bz9-webmaster-tools'); ?>" width="735" height="174"></a></div>

    <?php
    }

    /**
     * Tutorial page
     */
    public function bz9wt_tutorials(){
        $this->bz9wt_page_header();
        ?>
        <div align="center"><iframe width="650" height="1000" src="http://www.viewbix.com/gallery/2af9498e-7f8c-404e-95ff-9a83903e0963?w=650&h=834&gs=grid&st=true" frameborder="0" scrolling="no" allowTransparency="true"></iframe></div>
    <?php
    }

}

/**
 * Initiate plugin
 */
$bz9_tools_shortcode = new bz9_tools_shortcode;