<?php
	
	namespace WooOMA;
	
	class main {
		
		private $post_type      = "shop_order";
		private $post_meta_name = "order_manager";
		private $wpdb;
		
		
		public function __construct ($config) {
			
			global $wpdb;
			
			$this->config = $config;
			$this->wpdb   = $wpdb;
			
			$this->add_action();
			
			
			add_action( 'admin_enqueue_scripts', array( $this, 'scripts_enqueue') );
			
		}
		
		public function add_action () {
			add_action ("save_post_".$this->post_type, array($this, "save_post"));
			add_action( 'woocommerce_admin_order_data_after_order_details', array($this, "display_edit_page"), 10, 1 );
			add_action( 'manage_edit-shop_order_columns', array($this, "orders_col_name"), 10, 1 );
			add_action( 'manage_shop_order_posts_custom_column', array($this, "orders_col_val"), 10, 1 );
			//add_action( 'show_user_profile', array($this, "user_profile") );
			add_action( 'personal_options', array($this, "user_profile") );
		}
		
		public function save_post($post_id) {
			
			global $post;
			
			if (isset($post) && !empty($post)) {
			
				$manager = get_post_meta( $post->ID, $this->post_meta_name, true );
				
				if ( !is_user_logged_in() || !user_can (get_current_user_id(), "manage_woocommerce") || !empty($manager)) 
					return;
				
				update_post_meta($post->ID, $this->post_meta_name, get_current_user_id());
				
			}
			
		}
		
		
		function display_edit_page ($order) {
			
			echo '<div id="woo-oma-order-edit-gap">&nbsp;</div>';
			echo '<div id="woo-oma-order-edit">';
			echo "<strong>Manager</strong>: ";
			
			$manager = get_post_meta( $order->id, $this->post_meta_name, true );
			
			if (!empty($manager)) {
				$user_info = get_userdata($manager);
				if (!empty($user_info)) {
					echo '<a href="'.get_edit_user_link( $manager ).'">'.$user_info->user_login;
					if (isset($user_info->first_name)) {
						echo " (";
						echo $user_info->first_name;
						if (isset($user_info->last_name))
							echo " ".$user_info->last_name;
						echo ")";
					}
					echo '</a>';
				}
				else
					echo "Not found";
			}
			else
				echo "No";
				
			echo "</div>";
		 
		}
		
		
		
		public function orders_col_name ($columns) {
			
			$new_columns = (is_array($columns)) ? $columns : array();
			
			$new_columns['manager'] = 'Manager';
			
			if (isset($this->config["orders_cols_disable"]) && !empty($this->config["orders_cols_disable"])) {
				unset( $new_columns['order_actions'] );
				unset( $new_columns['order_notes'] );
				unset( $new_columns['customer_message'] );
			}
		
			$new_columns['order_actions'] = $columns['order_actions'];
			
			return $new_columns;
		}
		
		
		public function orders_col_val ($column) {
			
			global $post;
			
			if ( $column == 'manager' ) {  
				$manager = get_post_meta( $post->ID, $this->post_meta_name, true );
				if (!empty($manager)) {
					$user_info = get_userdata($manager);
					if (!empty($user_info)) {
						echo '<a href="'.get_edit_user_link( $manager ).'">'.$user_info->user_login;
						echo '</a>';
					}
					else
						echo "Removed";
				}
				else
					echo "&mdash;&mdash;&mdash;";
			}
			
			
		}
		
		public function user_profile ($user) {
			if (current_user_can( "view_woocommerce_reports" ) && user_can($user->ID, "view_woocommerce_reports")) {
				
				$query = "
						SELECT {$this->wpdb->posts}.ID AS id
						FROM {$this->wpdb->posts}
						INNER JOIN {$this->wpdb->prefix}postmeta ON {$this->wpdb->prefix}postmeta.post_id = {$this->wpdb->posts}.ID 
						WHERE {$this->wpdb->prefix}postmeta.meta_key = '{$this->post_meta_name}' 
						AND {$this->wpdb->prefix}postmeta.meta_value = '{$user->ID}' 
						AND {$this->wpdb->posts}.post_type = 'shop_order' 
						AND {$this->wpdb->posts}.post_status = 'wc-completed'
				";
				
				$this_month_first_day  = date("Y-m-01 01:01:01");
				$this_month_last_day   = date("Y-m-t H:i:s"); 	
				$this_month_handled    = 0;
				
				$that_month_time       = strtotime("-1 month");
				$that_month_first_day  = date("Y-m-01 01:01:01", $that_month_time);
				$that_month_last_day   = date("Y-m-t H:i:s", $that_month_time);
				$that_month_handled    = 0;
				
				
				
				$query_dated = $query." AND post_modified >= '$this_month_first_day' AND post_modified <= '$this_month_last_day'";
				
				$results    = $this->wpdb->get_results($query_dated);
				
				if (!empty($results)) {
					
					foreach ($results as $result) {
						
						$order       = new \WC_Order( $result->id );
						$items       = $order->get_items();
						$spent_order = 0;
						
						if (!empty($items)) {
							foreach ($items as $item)
								$spent_order +=$item['line_total'];
						}
						
						$this_month_handled += $spent_order;
						
					}
					
				}
				
				$query_dated = $query." AND post_modified >= '$that_month_first_day' AND post_modified <= '$that_month_last_day'";
				
				$results_that_month    = $this->wpdb->get_results($query_dated);
				
				if (!empty($results_that_month)) {
					
					foreach ($results_that_month as $result) {
						
						$order       = new \WC_Order( $result->id );
						$items       = $order->get_items();
						$spent_order = 0;
						
						if (!empty($items)) {
							foreach ($items as $item)
								$spent_order +=$item['line_total'];
						}
						
						$that_month_handled += $spent_order;
						
					}
					
				}
				
				
				echo "<div class='woo-oma-stats-inline'>Current Month Handled: {$this_month_handled}".get_woocommerce_currency_symbol()." (".count($results)." orders)</div>";
				echo "<div class='woo-oma-stats-inline'>Previous Month Handled: {$that_month_handled}".get_woocommerce_currency_symbol()." (".count($results_that_month)." orders)</div>";
				
				
			}
		}
		
		public function scripts_enqueue () {
			
			$js_path  = $this->config["url"]."assets/js/";
			$css_path = $this->config["url"]."assets/css/";
			
			wp_enqueue_style( 'woo-oma-style', $css_path."styles.css" );
		}

		
		
	}

?>