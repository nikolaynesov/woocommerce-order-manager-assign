<?php
/*
Plugin Name: Assign Manager
Plugin URI: http://webberg.ru
Description: Assigns a first manager who changed the order status to an order.
Version: 0.1
Author: Nikolay Nesov 
Author URI: http://webberg.ru
License: GPL
Copyright: Nikolay Nesov
*/

$config = [
	"dir"                 => plugin_dir_path( __FILE__ ),
	"url"                 => plugin_dir_url ( __FILE__ ),
	"orders_cols_disable" => true
];

include_once( $config["dir"].'WooOMA/main.php' );

$WooOMA = new \WooOMA\main($config);

?>