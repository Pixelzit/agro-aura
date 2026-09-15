<?php
function register_new_widget_areas() {

    // 1. Header Top
    register_sidebar( array(
        'name'          => 'Header Top',
        'id'            => 'header-top',
        'description'   => 'Header ke bilkul top pe',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ) );

    // 2. Home Category Section
    // register_sidebar( array(
    //     'name'          => 'Home Category Section',
    //     'id'            => 'home-category-section',
    //     'description'   => 'Home page category section',
    //     'before_widget' => '<div id="%1$s" class="widget %2$s">',
    //     'after_widget'  => '</div>',
    //     'before_title'  => '<h4 class="widget-title">',
    //     'after_title'   => '</h4>',
    // ) );
}
add_action( 'widgets_init', 'register_new_widget_areas' );