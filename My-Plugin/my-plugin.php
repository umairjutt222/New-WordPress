<?php 
/** 
 * My-Plugin 
 * 
 * @package MyPlugin 
 * @author Umair Iqbal
 * @copyright 2020 Umair Iqbal 
 * @license GPL-2.0-or-later 
 * 
 * @wordpress-plugin 
 * Plugin Name: My Plugin 
 * Plugin URI: https://github.com/Umair10110/Umair10110 
 * Description: This is a API Testing Plugin. 
 * Version: 0.0.1 
 * Author: Umair Iqbal
 * Author URI: https://github.com/Umair10110/Umair10110 
 * Text Domain: hello-world 
 */




function wp_demo_shortcode() { 
  
    
    $message = 'Hello world!'; 
    $endpoint = 'https://jsonplaceholder.typicode.com/todos';

    $request = wp_remote_get($endpoint);
    $data = json_decode( wp_remote_retrieve_body( $request ) );
    $output = '<ol>';
    foreach ($data as $line) {
    $output .= '<li>';
    $output .= 'id=' . $line->id . '<br>';
    $output .= 'userId=' . $line->userId . '<br>';
    $output .= 'title=' . $line->title . '<br>';
    $output .= $line->completed == 1 ? 'completed=True' : 'completed=false' . '<br>';
    $output .= '</li>';
}
$output .= '</ol>';
    return $output;
    }
    
    add_shortcode('getting_data', 'wp_demo_shortcode');

        add_shortcode('data_submission_form', 'data_submission_form_shortcode');
        function data_submission_form_shortcode() {
            ob_start();
            ?>
            <form id="data-submission-form" method="post" action="<?php echo esc_attr( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="submit_data">
                <input type="text" name="title" placeholder="Title" required>
                <input type="text" name="completed" placeholder="Completed (true/false)" required>
                <button type="submit">Submit Data</button>
            </form>
            <?php
            
            return ob_get_clean();
            
        }

add_action('admin_post_submit_data', 'handle_data_submission');
add_action('admin_post_nopriv_submit_data', 'handle_data_submission');
function handle_data_submission() {
  
    $title = sanitize_text_field($_POST['title']);
    $completed = ($_POST['completed'] === 'true') ? true : false;
    
    
    $data = array(
        'title'=>$title,
        'completed' => $completed,
        'userId' => '1000'
    );
    echo json_encode($data);
   
    $response = wp_remote_post('https://jsonplaceholder.typicode.com/todos', array(
        'body' => json_encode($data),
        'headers' => array('Content-Type' => 'application/json'),
    ));

    if (is_wp_error($response)) {
       
        $error_message = $response->get_error_message();
        echo $error_message;
    } else {
        
        echo "data store successfully";
        echo "</br>";
        
    } 
    // wp_redirect($_SERVER['HTTP_REFERER']);
    exit;
}




function custom_data_endpoint_callback($request) {
    
    $table = $request->get_param('table');

    global $wpdb;
    $table_name = $wpdb->prefix . $table;

    
    $data = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);

    return rest_ensure_response($data);
}

function register_custom_data_endpoint() {
    register_rest_route('custom/v1', 'get-data/', array(
        'methods' => 'GET',
        'callback' => 'custom_data_endpoint_callback',
    ));
}
add_action('rest_api_init', 'register_custom_data_endpoint');



function custom_endpoint_init() {
    add_rewrite_rule('^store-data/([^/]+)/([^/]+)/?','index.php?custom_table=$matches[1]&data=$matches[2]','top');
}
add_action('init', 'custom_endpoint_init');
function handle_custom_endpoint() {
    global $wp_query;

    if (isset($wp_query->query_vars['custom_table']) && isset($wp_query->query_vars['data'])) {
        $table_name = $wp_query->query_vars['custom_table'];
        $data_to_store = sanitize_text_field($wp_query->query_vars['data']);

        
        global $wpdb;
        $full_table_name = $wpdb->prefix . $table_name;
        $wpdb->insert($full_table_name, array('column_name' => $data_to_store));

       
        wp_redirect(home_url()); 
        exit();
    }
}
add_action('template_redirect', 'handle_custom_endpoint');


// add_action( 'rest_api_init', function () {
//     register_rest_route( 'myplugin/v1', '/store-data/(?P<table>\w+)', array(
//       'methods' => 'POST',
//       'callback' => 'myplugin_store_data',
//     ) );
//   } );
  
//   function myplugin_store_data( $request ) {
//     $table = $request->get_param( 'table' );
//     $data = $request->get_body_params();
  
//     global $wpdb;
//     $result = $wpdb->insert( $wpdb->prefix . $table, $data );
  
//     if ( $result ) {
//       return new WP_REST_Response( array( 'success' => true ), 200 );
//     } else {
//       return new WP_Error( 'database_error', 'Failed to store data', array( 'status' => 500 ) );
//     }
//   }
  

?>

