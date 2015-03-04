<?php
/**
* Plugin Name: Different Blog type post
* Plugin URI: http://elegantthemes.com/
* Description: A custom music review plugin built for example.
* Version: 1.0
* Author: George Ulloa
* Author URI
**/


function change_google_doc() {

    $labels = array(
        'name' => _x( 'Google Doc', 'google_doc' ),
        'singular_name' => _x( 'Google Doc', 'google_doc' ),
        'add_new' => _x( 'Add New', 'google_doc' ),
        'add_new_item' => _x( 'Add Google Doc', 'google_doc' ),
        'edit_item' => _x( 'Edit Google Doc', 'google_doc' ),
        'new_item' => _x( 'New Google Doc', 'google_doc' ),
        'view_item' => _x( 'View Google Doc', 'google_doc' ),
        'search_items' => _x( 'Search Google Doc', 'google_doc' ),
        'not_found' => _x( 'No Google Doc', 'google_doc' ),
        'not_found_in_trash' => _x( 'No Google Doc found in Trash', 'google_doc' ),
        'parent_item_colon' => _x( 'Parent Google Doc:', 'google_doc' ),
        'menu_name' => _x( 'Google Doc', 'google_doc' ),
    );

    $args = array(
        'labels' => $labels,
        'hierarchical' => true,
        'description' => 'Google Doc filterable by genre',
        'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'trackbacks', 'custom-fields', 'comments', 'revisions', 'page-attributes' ),
        'taxonomies' => array( 'type' ),
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-format-audio',
        'show_in_nav_menus' => true,
        'publicly_queryable' => true,
        'exclude_from_search' => false,
        'has_archive' => true,
        'query_var' => true,
        'can_export' => true,
        'rewrite' => true,
        'capability_type' => 'post'
    );

    register_post_type( 'google_doc', $args );
}

add_action( 'init', 'change_google_doc' );



function type_taxonomy() {
    register_taxonomy(
        'type',
        'google_doc',
        array(
            'hierarchical' => true,
            'label' => 'type',
            'query_var' => true,
            'rewrite' => array(
                'slug' => 'genre',
                'with_front' => false
            )
        )
    );
}
add_action( 'init', 'type_taxonomy');

// Function used to automatically create Google Doc page.
function create_google_doc_pages()
  {
   //post status and options
    $post = array(
          'comment_status' => 'open',
          'ping_status' =>  'closed' ,
          'post_date' => date('Y-m-d H:i:s'),
          'post_name' => 'google_doc',
          'post_status' => 'publish' ,
          'post_title' => 'Google Doc',
          'post_type' => 'page',
    );
    //insert page and save the id
    $newvalue = wp_insert_post( $post, false );
    //save the id in the database
    update_option( 'mrpage', $newvalue );
  }

  // // Activates function if plugin is activated
  register_activation_hook( __FILE__, 'create_google_doc_pages');



?>
