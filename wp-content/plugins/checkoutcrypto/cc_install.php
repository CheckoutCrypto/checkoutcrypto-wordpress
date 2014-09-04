<?php

global $cc_db_version;
$cc_db_version = "0.1";

function cc_install() {
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
   global $wpdb;
   global $cc_db_version;

   $table_name = $wpdb->prefix . "cc_coins";
      
   $sql = "CREATE TABLE ". $table_name . " (
	        `id` int(11) NOT NULL AUTO_INCREMENT,
			`coin_code` varchar(10) NOT NULL,
			`coin_name` varchar(50) NOT NULL,
			`coin_rate` decimal(30,8) NOT NULL DEFAULT 0.00000000,
			`coin_img` varchar(250) NOT NULL,
			`cc_balance` decimal(30,8) NOT NULL DEFAULT 0.00000000,
          `date_added` datetime NOT NULL,
          PRIMARY KEY (`id`)
        );";
   dbDelta( $sql );

   $table_name = $wpdb->prefix . "cc_orders";
      
   $sql = "CREATE TABLE ". $table_name . " (
	`id` int(11) NOT NULL AUTO_INCREMENT,
            `order_id` int(11) NOT NULL,
            `coin_code` varchar(10) NOT NULL,
            `coin_name` varchar(50) NOT NULL,
            `coin_rate` decimal(30,8) NOT NULL DEFAULT 0.00000000,
            `coin_paid` varchar(250) NOT NULL,
            `coin_address`varchar(250) DEFAULT NULL,
            `order_status` varchar(250) NOT NULL,
            `cc_queue_id` varchar(250) NOT NULL,
            `cc_queue_id_tmp` varchar(250) NOT NULL,
            `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        )";
   dbDelta( $sql );


   $table_name = $wpdb->prefix . "cc_settings";
      
   $sql = "CREATE TABLE ". $table_name . " (
	id mediumint(9) NOT NULL AUTO_INCREMENT,
	PRIMARY KEY id (id)
    );";
   dbDelta( $sql );
 
   add_option( "cc_db_version", $cc_db_version );
}

function cc_uninstall(){
   global $wpdb;

   $table_name = $wpdb->prefix . "cc_coins";
      
   $sql = "DROP TABLE ". $table_name;
   $wpdb->query("DROP TABLE {$table_name}");

   $table_name = $wpdb->prefix . "cc_orders";
      
   $sql = "DROP TABLE ". $table_name;
   $wpdb->query("DROP TABLE {$table_name}");


   $table_name = $wpdb->prefix . "cc_settings";
      
   $sql = "DROP TABLE ". $table_name;
   $wpdb->query("DROP TABLE {$table_name}");

}

/*
function cc_install_data() {
   global $wpdb;
   $welcome_name = "Mr. WordPress";
   $welcome_text = "Congratulations, you just completed the installation!";
   $table_name = $wpdb->prefix . "cc";
   $rows_affected = $wpdb->insert( $table_name, array( 'time' => current_time('mysql'), 'name' => $welcome_name, 'text' => $welcome_text ) );
}  */

/*function cc_upgrade(){
global $wpdb;
$installed_ver = get_option( "cc_db_version" );

if( $installed_ver != $cc_db_version ) {

    $sql = "CREATE TABLE ". $table_name . " (
	id mediumint(9) NOT NULL AUTO_INCREMENT,
	PRIMARY KEY id (id)
    );";
   dbDelta( $sql );

   $table_name = $wpdb->prefix . "cc_orders";
      
   $sql = "CREATE TABLE ". $table_name . " (
	id mediumint(9) NOT NULL AUTO_INCREMENT,
	PRIMARY KEY id (id)
    );";
   dbDelta( $sql );


   $table_name = $wpdb->prefix . "cc_settings";
      
   $sql = "CREATE TABLE ". $table_name . " (
	id mediumint(9) NOT NULL AUTO_INCREMENT,
	PRIMARY KEY id (id)
    );";
   dbDelta( $sql );
 
   add_option( "cc_db_version", $cc_db_version );

  require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  dbDelta( $sql );

  update_option( "cc_db_version", $cc_db_version );
} */

?>
