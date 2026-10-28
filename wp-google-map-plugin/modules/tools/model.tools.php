<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class: WPGMP_Model_Tools
 *
 * @author Flipper Code <hello@flippercode.com>
 * @version 3.0.0
 * @package Maps
 */

if ( ! class_exists( 'WPGMP_Model_Tools' ) ) {

	/**
	 * Backup model for Backup operation.
	 *
	 * @package Maps
	 * @author Flipper Code <hello@flippercode.com>
	 */
	class WPGMP_Model_Tools extends FlipperCode_Model_Base {

		/**
		 * Generate SQL query.
		 *
		 * @var string
		 */
		protected $query;

		/**
		 * Intialize Backup object.
		 */
		function __construct() {

		}
		/**
		 * Admin menu for Backup Operation
		 *
		 * @return array Admin menu navigation(s).
		 */
		function navigation() {
			return array(
				'wpgmp_manage_tools' => esc_html__( 'Plugin Tools', 'wp-google-map-plugin' ),
			);
		}
		/**
		 * Install table associated with Location entity.
		 *
		 * @return string SQL query to install map_locations table.
		 */
		function install() {

		}
		/**
		 * Upload backup from .sql file.
		 *
		 * @return string Success or Error response.
		 */
		public function clean_database() {
			global $_POST;

			if ( isset( $_REQUEST['_wpnonce'] ) ) {

				$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) );

				if ( ! wp_verify_nonce( $nonce, 'wpgmp-nonce' ) ) {

					die( 'Cheating...' );

				} else {
					$data = $_POST;
				}
			}

			if ( isset( $data['wpgmp_cleandatabase_tools'] ) ) {

				if( empty($data['wpgmp_clean_consent']) || (!empty($data['wpgmp_clean_consent']) && $data['wpgmp_clean_consent'] != 'DELETE' ) ){
					$response['error'] = esc_html__( 'Please enter "DELETE" in the provided textbox and then proceed to clear plugin\'s database.', 'wp-google-map-plugin' );
					return $response;
				}  

				if ( !empty( $data['wpgmp_clean_consent'] ) && $data['wpgmp_clean_consent'] == 'DELETE' ) {

					$backup_tables = array( TBL_LOCATION, TBL_GROUPMAP, TBL_MAP );
					$connection    = FlipperCode_Database::connect();
					foreach ( $backup_tables as  $table ) {
						$this->query = $connection->prepare( "DELETE FROM $table where %d", 1 );
						FlipperCode_Database::non_query( $this->query, $connection );
					}

					$response['success'] = esc_html__( 'All the saved locations, marker categories and maps were removed.', 'wp-google-map-plugin' );
				} 
			} else {

				$response['error'] = esc_html__( 'Something went wrong. Please try again.', 'wp-google-map-plugin' );
			}
			return $response;

		}
		/**
		 * Take backup to .sql file.
		 *
		 * @return string Success or Error response.
		 */
		public function upload_sampledata() {

			if ( isset( $_REQUEST['_wpnonce'] ) ) {

				$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) );

				if ( ! wp_verify_nonce( $nonce, 'wpgmp-nonce' ) ) {

					die( 'Cheating...' );

				} else {
					$data = $_POST;
				}
			}
			if ( isset( $_POST['wpgmp_sampledata_consent'] ) ) {

				if ( isset( $data['wpgmp_sampledata_consent'] ) && $data['wpgmp_sampledata_consent'] == 'YES' ) {

					global $wpdb;

					$success = true;

					$category_ids = array();

					$sample_data             = array();
				
					$sample_data['category'] = array(
						'Universities'     => array(WPGMP_IMAGES . '/icons/university.png', 1),
						'Tech Companies'   => array(WPGMP_IMAGES . '/icons/company.png', 2),
					);

					foreach ( $sample_data['category'] as $title => $category ) {
						$sdata                      = array();
						$sdata['group_map_title']   = $title;
						$sdata['group_parent']      = 0;
						$sdata['group_marker']      = wp_unslash( $category[0] );
						$sdata['extensions_fields'] = serialize( wp_unslash( array( 'cat_order' => $category[1] ) ) );
						$category_ids[]             = FlipperCode_Database::insert_or_update( TBL_GROUPMAP, $sdata, $where = '' );
					}

				
					$sample_data['locations'] = array(
						'San Diego State University' => array(
							'5500 Campanile Dr, San Diego, CA 92182, United States',
							'32.7757217',
							'-117.0718893',
							$category_ids[0],
							'A public research university known for its vibrant campus and strong academic programs. The university offers a wide range of academic programs, research opportunities, and student services.',
							'San Diego',
							'CA',
							'United States'
						),
						'Google HQ' => array(
							'1600 Amphitheatre Parkway, Mountain View, CA, United States',
							'37.4220656',
							'-122.0840897',
							$category_ids[1],
							'Google’s global headquarters in Silicon Valley, home to innovation and cutting-edge technology. The campus represents Google’s focus on software development, artificial intelligence, cloud computing, and other modern technologies.',
							'Mountain View',
							'CA',
							'United States'
						),
						'University of Virginia' => array(
							'1827 University Ave, Charlottesville, VA 22903, United States',
							'38.0335529',
							'-78.5079772',
							$category_ids[0],
							'A historic university founded by Thomas Jefferson, known for its architecture and research. It is recognized for its historic campus, academic programs, research, and educational community.',
							'Charlottesville',
							'VA',
							'United States'
						),
						'Microsoft Campus' => array(
							'1 Microsoft Way, Redmond, WA 98052, United States',
							'47.6396205',
							'-122.1282706',
							$category_ids[1],
							'Microsoft’s corporate campus featuring offices, labs, and visitor centers. It is one of Microsoft’s key locations and provides a good example of a corporate location displayed on an interactive map.',
							'Redmond',
							'WA',
							'United States'
						),
						'Texas A&M University' => array(
							'400 Bizzell St, College Station, TX 77843, United States',
							'30.6183558',
							'-96.3365232',
							$category_ids[0],
							'One of the largest universities in the U.S., known for engineering and agricultural sciences. It is known for its academic programs, research activities, engineering, agriculture, and strong campus community.',
							'College Station',
							'TX',
							'United States'
						),
					);	

					foreach ( $sample_data['locations'] as $title => $location ) {
	
						$sdata                       = array();
						$sdata['location_messages']  = wp_unslash( $location[4] );
						$sdata['location_group_map'] = serialize( wp_unslash( array( $location[3] ) ) );
						$sdata['location_title']     = $title;
						$sdata['location_address']   = $location[0];
						$sdata['location_latitude']  = $location[1];
						$sdata['location_longitude'] = $location[2];
						$sdata['location_city']      = $location[5];
						$sdata['location_state']     = $location[6];
						$sdata['location_country']   = $location[7];
						$sdata['location_author']    = get_current_user_id();
						$location_ids[]              = FlipperCode_Database::insert_or_update( TBL_LOCATION, $sdata, $where = '' );
					}

					$sample_data['maps'] = array(

						'map 1' => 'Tzo4OiJzdGRDbGFzcyI6MjI6e3M6NjoibWFwX2lkIjtzOjE6IjUiO3M6OToibWFwX3RpdGxlIjtzOjE4OiJBbGwgSW4gT25lIExpc3RpbmciO3M6OToibWFwX3dpZHRoIjtzOjA6IiI7czoxMDoibWFwX2hlaWdodCI7czozOiI0MDAiO3M6MTQ6Im1hcF96b29tX2xldmVsIjtzOjE6IjMiO3M6ODoibWFwX3R5cGUiO3M6NzoiUk9BRE1BUCI7czoxOToibWFwX3Njcm9sbGluZ193aGVlbCI7czo1OiJmYWxzZSI7czoxODoibWFwX3Zpc3VhbF9yZWZyZXNoIjtOO3M6MTM6Im1hcF80NWltYWdlcnkiO3M6MDoiIjtzOjIzOiJtYXBfc3RyZWV0X3ZpZXdfc2V0dGluZyI7YToyOntzOjExOiJwb3ZfaGVhZGluZyI7czowOiIiO3M6OToicG92X3BpdGNoIjtzOjA6IiI7fXM6Mjc6Im1hcF9yb3V0ZV9kaXJlY3Rpb25fc2V0dGluZyI7YToxOntzOjE1OiJyb3V0ZV9kaXJlY3Rpb24iO3M6NToiZmFsc2UiO31zOjE1OiJtYXBfYWxsX2NvbnRyb2wiO2E6NzU6e3M6MTc6Im1hcF9taW56b29tX2xldmVsIjtzOjE6IjAiO3M6MTc6Im1hcF9tYXh6b29tX2xldmVsIjtzOjI6IjE5IjtzOjIzOiJ6b29tX2xldmVsX2FmdGVyX3NlYXJjaCI7czoyOiIxMCI7czo3OiJnZXN0dXJlIjtzOjQ6ImF1dG8iO3M6Nzoic2NyZWVucyI7YTozOntzOjExOiJzbWFydHBob25lcyI7YTozOntzOjE2OiJtYXBfd2lkdGhfbW9iaWxlIjtzOjA6IiI7czoxNzoibWFwX2hlaWdodF9tb2JpbGUiO3M6MDoiIjtzOjIxOiJtYXBfem9vbV9sZXZlbF9tb2JpbGUiO3M6MToiNSI7fXM6NToiaXBhZHMiO2E6Mzp7czoxNjoibWFwX3dpZHRoX21vYmlsZSI7czowOiIiO3M6MTc6Im1hcF9oZWlnaHRfbW9iaWxlIjtzOjA6IiI7czoyMToibWFwX3pvb21fbGV2ZWxfbW9iaWxlIjtzOjE6IjUiO31zOjEzOiJsYXJnZS1zY3JlZW5zIjthOjM6e3M6MTY6Im1hcF93aWR0aF9tb2JpbGUiO3M6MDoiIjtzOjE3OiJtYXBfaGVpZ2h0X21vYmlsZSI7czowOiIiO3M6MjE6Im1hcF96b29tX2xldmVsX21vYmlsZSI7czoxOiI1Ijt9fXM6MTk6Im1hcF9jZW50ZXJfbGF0aXR1ZGUiO3M6OToiMzcuMDc5NzQ0IjtzOjIwOiJtYXBfY2VudGVyX2xvbmdpdHVkZSI7czoxMDoiLTkwLjMwMzg1MiI7czoyMzoiY2VudGVyX2NpcmNsZV9maWxsY29sb3IiO3M6NzoiIzhDQUVGMiI7czoyNToiY2VudGVyX2NpcmNsZV9maWxsb3BhY2l0eSI7czoyOiIuNSI7czoyNToiY2VudGVyX2NpcmNsZV9zdHJva2Vjb2xvciI7czo3OiIjOENBRUYyIjtzOjI3OiJjZW50ZXJfY2lyY2xlX3N0cm9rZW9wYWNpdHkiO3M6MjoiLjUiO3M6MjY6ImNlbnRlcl9jaXJjbGVfc3Ryb2tld2VpZ2h0IjtzOjE6IjEiO3M6MjA6ImNlbnRlcl9jaXJjbGVfcmFkaXVzIjtzOjE6IjUiO3M6Mjk6InNob3dfY2VudGVyX21hcmtlcl9pbmZvd2luZG93IjtzOjA6IiI7czoxODoibWFya2VyX2NlbnRlcl9pY29uIjtzOjEwMToiaHR0cDovLzEyNy4wLjAuMS9mY2xhYnMvd3BnbXAvd3AtY29udGVudC9wbHVnaW5zL3dwLWdvb2dsZS1tYXAtZ29sZC9hc3NldHMvaW1hZ2VzLy9kZWZhdWx0X21hcmtlci5wbmciO3M6MjA6IndwZ21wX2FjZl9maWVsZF9uYW1lIjtzOjA6IiI7czoyMToiaW5mb3dpbmRvd19vcGVub3B0aW9uIjtzOjU6ImNsaWNrIjtzOjE5OiJtYXJrZXJfZGVmYXVsdF9pY29uIjtzOjEzMDk6ImRhdGE6aW1hZ2Uvc3ZnK3htbDtjaGFyc2V0PVVURi04LCUzQ3N2ZyUyMHZlcnNpb24lM0QlMjIxLjElMjIlMjB4bWxucyUzRCUyMmh0dHAlM0ElMkYlMkZ3d3cudzMub3JnJTJGMjAwMCUyRnN2ZyUyMiUyMHhtbG5zJTNBeGxpbmslM0QlMjJodHRwJTNBJTJGJTJGd3d3LnczLm9yZyUyRjE5OTklMkZ4bGluayUyMiUyMHglM0QlMjIwcHglMjIlMjB5JTNEJTIyMHB4JTIyJTIwdmlld0JveCUzRCUyMjAlMjAwJTIwNTEyJTIwNTEyJTIyJTIwc3R5bGUlM0QlMjJlbmFibGUtYmFja2dyb3VuZCUzQW5ldyUyMDAlMjAwJTIwNTEyJTIwNTEyJTNCJTIyJTIweG1sJTNBc3BhY2UlM0QlMjJwcmVzZXJ2ZSUyMiUzRSUwQSUzQ3N0eWxlJTIwdHlwZSUzRCUyMnRleHQlMkZjc3MlMjIlM0UlMEElMDkuc3ZnX2ZkcTBwYS1zdDAlN0JmaWxsJTNBJTIwJTIzZDE0YjRiJTNCc3Ryb2tlJTNBJTIwJTIzMDAwMDAwJTNCc3Ryb2tlLXdpZHRoJTNBJTIwMCUzQnN0cm9rZS1taXRlcmxpbWl0JTNBMTAlM0IlN0QlMEElM0MlMkZzdHlsZSUzRSUwQSUzQ2clMjBpZCUzRCUyMkxheWVyXzElMjIlM0UlMEElMDklM0NwYXRoJTIwY2xhc3MlM0QlMjJzdmdfZmRxMHBhLXN0MCUyMiUyMGQlM0QlMjJNMzE5LjklMkMzMC4xQzI1NC01LjMlMkMxNjUuNiUyQzIzLjIlMkMxMjklMkM4Ny40Yy0xNi40JTJDMjYuOC0yMy41JTJDNTguNC0yMS43JTJDODkuNmMyLjIlMkMzNi45JTJDMTcuOSUyQzcxLjYlMkM0Mi45JTJDOTguOSUwQSUwOSUwOWM1NC40JTJDNTkuNiUyQzc5LjklMkMxMzguNiUyQzEwMCUyQzIxNS42YzEuNCUyQzUuMiUyQzguNyUyQzUuMiUyQzEwLjElMkMwLjFjMTYuNi01OS43JTJDMzUuNS0xMTkuMyUyQzY3LjQtMTcyLjdjMjAuNy0zMy44JTJDNTQuNS01OC43JTJDNjcuOS05NyUwQSUwOSUwOUM0MjIuMyUyQzE1MC40JTJDMzg5LjclMkM2Mi42JTJDMzE5LjklMkMzMC4xeiUyME0yNTYlMkMyODAuNGMtNjQuMyUyQzAtMTE2LjUtNTIuMS0xMTYuNS0xMTYuNWMwLTY0LjMlMkM1Mi4xLTExNi41JTJDMTE2LjUtMTE2LjUlMEElMDklMDlzMTE2LjUlMkM1Mi4xJTJDMTE2LjUlMkMxMTYuNUMzNzIuNSUyQzIyOC4yJTJDMzIwLjMlMkMyODAuNCUyQzI1NiUyQzI4MC40eiUyMiUzRSUzQyUyRnBhdGglM0UlMEElM0MlMkZnJTNFJTBBJTNDZyUyMGlkJTNEJTIyTGF5ZXJfMiUyMiUzRSUwQSUwOSUzQ2NpcmNsZSUyMGNsYXNzJTNEJTIyc3ZnX2ZkcTBwYS1zdDAlMjIlMjBjeCUzRCUyMjI1NiUyMiUyMGN5JTNEJTIyMTYzLjklMjIlMjByJTNEJTIyOTIuNSUyMiUzRSUzQyUyRmNpcmNsZSUzRSUwQSUzQyUyRmclM0UlMEElM0MlMkZzdmclM0UiO3M6Mjc6ImluZm93aW5kb3dfYm91bmNlX2FuaW1hdGlvbiI7czowOiIiO3M6MjA6ImluZm93aW5kb3dfem9vbWxldmVsIjtzOjA6IiI7czoxNjoiaW5mb3dpbmRvd193aWR0aCI7czowOiIiO3M6MjQ6ImxvY2F0aW9uX2luZm93aW5kb3dfc2tpbiI7YTozOntzOjQ6Im5hbWUiO3M6NToidWRpbmUiO3M6NDoidHlwZSI7czoxMDoiaW5mb3dpbmRvdyI7czoxMDoic291cmNlY29kZSI7czo2OTM6IiZsdDtkaXYgY2xhc3M9JnF1b3Q7ZmMtaXRlbS1ib3ggZmMtaXRlbS1uby1wYWRkaW5nJnF1b3Q7Jmd0Ow0KICAgIHttYXJrZXJfaW1hZ2V9DQogICAgJmx0O2RpdiBjbGFzcz0mcXVvdDtmYy1pdGVtY29udGVudC1wYWRkaW5nJnF1b3Q7Jmd0Ow0KICAgICAgICAmbHQ7ZGl2IGNsYXNzPSZxdW90O2ZjLWl0ZW0tcGFkZGluZy1jb250ZW50XzIwJnF1b3Q7Jmd0Ow0KICAgICAgICAgICAgJmx0O2RpdiBjbGFzcz0mcXVvdDtmYy1pdGVtLW1ldGEgZmMtaXRlbS1zZWNvbmRhcnktdGV4dC1jb2xvciBmYy1pdGVtLXRvcC1zcGFjZSBmYy10ZXh0LWNlbnRlciZxdW90OyZndDt7bWFya2VyX2NhdGVnb3J5fSZsdDsvZGl2Jmd0Ow0KICAgICAgICAgICAgJmx0O2RpdiBjbGFzcz0mcXVvdDtmYy1pdGVtLXRpdGxlIGZjLWl0ZW0tcHJpbWFyeS10ZXh0LWNvbG9yIGZjLXRleHQtY2VudGVyJnF1b3Q7Jmd0O3ttYXJrZXJfdGl0bGV9Jmx0Oy9kaXYmZ3Q7DQogICAgICAgICAgICAmbHQ7ZGl2IGNsYXNzPSZxdW90O2ZjLWl0ZW0tY29udGVudCBmYy1pdGVtLWJvZHktdGV4dC1jb2xvciBmYy1pdGVtLXRvcC1zcGFjZSZxdW90OyZndDsNCiAgICAgICAgICAgICAgICB7bWFya2VyX21lc3NhZ2V9DQogICAgICAgICAgICAmbHQ7L2RpdiZndDsNCg0KICAgICAgICAmbHQ7L2RpdiZndDsNCiAgICAmbHQ7L2RpdiZndDsNCiZsdDsvZGl2Jmd0OyI7fXM6MTU6ImRpc3BsYXlfbGlzdGluZyI7czo0OiJ0cnVlIjtzOjE4OiJsaXN0aW5nX29wZW5vcHRpb24iO3M6NToiY2xpY2siO3M6MjA6IndwZ21wX3NlYXJjaF9kaXNwbGF5IjtzOjQ6InRydWUiO3M6Mjc6IndwZ21wX3NlYXJjaGJhcl9wbGFjZWhvbGRlciI7czowOiIiO3M6MjU6IndwZ21wX3NlYXJjaF9wbGFjZWhvbGRlcnMiO3M6MDoiIjtzOjI2OiJ3cGdtcF9leGNsdWRlX3BsYWNlaG9sZGVycyI7czowOiIiO3M6MjQ6InNlYXJjaF9maWVsZF9hdXRvc3VnZ2VzdCI7czo0OiJ0cnVlIjtzOjI5OiJ3cGdtcF9kaXNwbGF5X2NhdGVnb3J5X2ZpbHRlciI7czo0OiJ0cnVlIjtzOjI2OiJ3cGdtcF9jYXRlZ29yeV9wbGFjZWhvbGRlciI7czowOiIiO3M6Mjg6IndwZ21wX2Rpc3BsYXlfc29ydGluZ19maWx0ZXIiO3M6NDoidHJ1ZSI7czoyNzoid3BnbXBfZGlzcGxheV9yYWRpdXNfZmlsdGVyIjtzOjQ6InRydWUiO3M6MjI6IndwZ21wX3JhZGl1c19kaW1lbnNpb24iO3M6NToibWlsZXMiO3M6MjA6IndwZ21wX3JhZGl1c19vcHRpb25zIjtzOjA6IiI7czozODoid3BnbXBfZGlzcGxheV9sb2NhdGlvbl9wZXJfcGFnZV9maWx0ZXIiO3M6NDoidHJ1ZSI7czoyNjoid3BnbXBfZGlzcGxheV9wcmludF9vcHRpb24iO3M6NDoidHJ1ZSI7czoyMDoid3BnbXBfbGlzdGluZ19udW1iZXIiO3M6MjoiMTAiO3M6MjA6IndwZ21wX2JlZm9yZV9saXN0aW5nIjtzOjEzOiJNYXAgTG9jYXRpb25zIjtzOjE1OiJ3cGdtcF9saXN0X2dyaWQiO3M6MTg6IndwZ21wX2xpc3RpbmdfbGlzdCI7czoyNToid3BnbXBfY2F0ZWdvcnlkaXNwbGF5c29ydCI7czo1OiJ0aXRsZSI7czoyNzoid3BnbXBfY2F0ZWdvcnlkaXNwbGF5c29ydGJ5IjtzOjM6ImFzYyI7czoyMDoid3BnbXBfZGVmYXVsdF9yYWRpdXMiO3M6MDoiIjtzOjMwOiJ3cGdtcF9kZWZhdWx0X3JhZGl1c19kaW1lbnNpb24iO3M6NToibWlsZXMiO3M6OToiaXRlbV9za2luIjthOjM6e3M6NDoibmFtZSI7czo3OiJkZWZhdWx0IjtzOjQ6InR5cGUiO3M6NDoiaXRlbSI7czoxMDoic291cmNlY29kZSI7czoxMTk1OiImbHQ7ZGl2IGNsYXNzPSZxdW90O3dwZ21wX2xvY2F0aW9ucyZxdW90OyZndDsNCiAgICAmbHQ7ZGl2IGNsYXNzPSZxdW90O3dwZ21wX2xvY2F0aW9uc19oZWFkJnF1b3Q7Jmd0Ow0KICAgICAgICAmbHQ7ZGl2IGNsYXNzPSZxdW90O3dwZ21wX2xvY2F0aW9uX3RpdGxlJnF1b3Q7Jmd0Ow0KICAgICAgICAgICAgJmx0O2EgaHJlZj0mcXVvdDsmcXVvdDsgY2xhc3M9JnF1b3Q7cGxhY2VfdGl0bGUmcXVvdDsgZGF0YS16b29tPSZxdW90O3ttYXJrZXJfem9vbX0mcXVvdDsgZGF0YS1tYXJrZXI9JnF1b3Q7e21hcmtlcl9pZH0mcXVvdDsmZ3Q7e21hcmtlcl90aXRsZX0mbHQ7L2EmZ3Q7DQogICAgICAgICZsdDsvZGl2Jmd0Ow0KICAgICAgICAmbHQ7ZGl2IGNsYXNzPSZxdW90O3dwZ21wX2xvY2F0aW9uX21ldGEmcXVvdDsmZ3Q7DQogICAgICAgICAgICAmbHQ7c3BhbiBjbGFzcz0mcXVvdDt3cGdtcF9sb2NhdGlvbl9jYXRlZ29yeSBmYy1pbmZvYm94LWNhdGVnb3JpZXMmcXVvdDsmZ3Q7e21hcmtlcl9jYXRlZ29yeX0mbHQ7L3NwYW4mZ3Q7DQogICAgICAgICZsdDsvZGl2Jmd0Ow0KICAgICZsdDsvZGl2Jmd0Ow0KICAgICZsdDtkaXYgY2xhc3M9JnF1b3Q7d3BnbXBfbG9jYXRpb25zX2NvbnRlbnQmcXVvdDsmZ3Q7DQogICAgICAgIHttYXJrZXJfaW1hZ2V9IHttYXJrZXJfbWVzc2FnZX0NCgkJJmx0O2RpdiZndDsNCgkJCSZsdDthZGRyZXNzIHN0eWxlPSZxdW90O2ZvbnQtc2l6ZTogLjk1cmVtO3BhZGRpbmctdG9wOjVweDsmcXVvdDsmZ3Q7Jmx0O3NwYW4gc3R5bGU9JnF1b3Q7Zm9udC13ZWlnaHQ6NTAwOyBmb250LXNpemU6Ljk1cmVtOyZxdW90OyZndDtBZGRyZXNzOiZsdDsvc3BhbiZndDsge21hcmtlcl9hZGRyZXNzfSZsdDsvYWRkcmVzcyZndDsNCgkJJmx0Oy9kaXYmZ3Q7DQoJCSZsdDtkaXYgY2xhc3M9JnF1b3Q7d3BnbXAtYWN0aW9ucyZxdW90OyZndDsNCgkJICAmbHQ7YSBocmVmPSZxdW90O3tnZXRfZGlyZWN0aW9uc19saW5rfSZxdW90OyB0YXJnZXQ9JnF1b3Q7X2JsYW5rJnF1b3Q7IGNsYXNzPSZxdW90O3dwZ21wLWFjdGlvbi1saW5rJnF1b3Q7Jmd0O0dldCBEaXJlY3Rpb25zJmx0Oy9hJmd0Ow0KCQkmbHQ7L2RpdiZndDsNCiAgICAmbHQ7L2RpdiZndDsNCiAgICAmbHQ7ZGl2IGNsYXNzPSZxdW90O3dwZ21wX2xvY2F0aW9uc19mb290JnF1b3Q7Jmd0OyZsdDsvZGl2Jmd0Ow0KJmx0Oy9kaXYmZ3Q7Ijt9czoxNToiaGVhdF9tYXBfcmFkaXVzIjtzOjI6IjIwIjtzOjE2OiJoZWF0X21hcF9vcGFjaXR5IjtzOjM6IjAuNiI7czoxNjoiZmlsdGVyc19wb3NpdGlvbiI7czo3OiJkZWZhdWx0IjtzOjIxOiJtYXBfcmVzZXRfYnV0dG9uX3RleHQiO3M6NToiUmVzZXQiO3M6MTk6ImFwcGx5X2N1c3RvbV9kZXNpZ24iO3M6NDoidHJ1ZSI7czoxNjoid3BnbXBfY3VzdG9tX2NzcyI7czowOiIiO3M6MjA6IndwZ21wX2Jhc2VfZm9udF9zaXplIjtzOjQ6IjE0cHgiO3M6MTI6ImNvbG9yX3NjaGVtYSI7czoxNToiIzNGNTFCNV8jNjE2MTYxIjtzOjE5OiJ3cGdtcF9wcmltYXJ5X2NvbG9yIjtzOjE6IiMiO3M6MjE6IndwZ21wX3NlY29uZGFyeV9jb2xvciI7czoxOiIjIjtzOjEyOiJjdXN0b21fc3R5bGUiO3M6MDoiIjtzOjE5OiJjb250cm9sX3RyYW5zcGFyZW50IjtzOjQ6InRydWUiO3M6MjE6Inpvb21fY29udHJvbF9wb3NpdGlvbiI7czo4OiJUT1BfTEVGVCI7czoxODoiem9vbV9jb250cm9sX3N0eWxlIjtzOjU6IkxBUkdFIjtzOjI1OiJtYXBfdHlwZV9jb250cm9sX3Bvc2l0aW9uIjtzOjk6IlRPUF9SSUdIVCI7czoyMjoibWFwX3R5cGVfY29udHJvbF9zdHlsZSI7czoxNDoiSE9SSVpPTlRBTF9CQVIiO3M6Mjg6ImZ1bGxfc2NyZWVuX2NvbnRyb2xfcG9zaXRpb24iO3M6OToiVE9QX1JJR0hUIjtzOjI4OiJzdHJlZXRfdmlld19jb250cm9sX3Bvc2l0aW9uIjtzOjg6IlRPUF9MRUZUIjtzOjIzOiJjYW1lcmFfY29udHJvbF9wb3NpdGlvbiI7czo4OiJUT1BfTEVGVCI7czoyMzoic2VhcmNoX2NvbnRyb2xfcG9zaXRpb24iO3M6ODoiVE9QX0xFRlQiO3M6MjU6ImxvY2F0ZW1lX2NvbnRyb2xfcG9zaXRpb24iO3M6ODoiVE9QX0xFRlQiO3M6MTM6ImZyb21fbGF0aXR1ZGUiO3M6MDoiIjtzOjE0OiJmcm9tX2xvbmdpdHVkZSI7czowOiIiO3M6MTE6InRvX2xhdGl0dWRlIjtzOjA6IiI7czoxMjoidG9fbG9uZ2l0dWRlIjtzOjA6IiI7czoxMDoiem9vbV9sZXZlbCI7czoxOiIxIjtzOjExOiJnZW9qc29uX3VybCI7czowOiIiO3M6MTY6ImZjX2N1c3RvbV9zdHlsZXMiO3M6NDY3NzoieyIwIjp7ImluZm93aW5kb3ctdWRpbmUiOnsiZmMtaXRlbS1ib3guZmMtaXRlbS1uby1wYWRkaW5nIjoiYmFja2dyb3VuZC1pbWFnZTpub25lO2ZvbnQtZmFtaWx5Oi1hcHBsZS1zeXN0ZW0sIEJsaW5rTWFjU3lzdGVtRm9udCwgXCJTZWdvZSBVSVwiLCBSb2JvdG8sIE94eWdlbi1TYW5zLCBVYnVudHUsIENhbnRhcmVsbCwgXCJIZWx2ZXRpY2EgTmV1ZVwiLCBzYW5zLXNlcmlmO2ZvbnQtd2VpZ2h0OjQwMDtmb250LXNpemU6MTRweDtjb2xvcjpyZ2IoMTE5LCAxMTksIDExOSk7bGluZS1oZWlnaHQ6MThweDtiYWNrZ3JvdW5kLWNvbG9yOnJnYigyNTUsIDI1NSwgMjU1KTtmb250LXN0eWxlOm5vcm1hbDt0ZXh0LWFsaWduOnN0YXJ0O3RleHQtZGVjb3JhdGlvbjpub25lO21hcmdpbi10b3A6MHB4O21hcmdpbi1ib3R0b206MHB4O21hcmdpbi1sZWZ0OjBweDttYXJnaW4tcmlnaHQ6MHB4O3BhZGRpbmctdG9wOjBweDtwYWRkaW5nLWJvdHRvbTowcHg7cGFkZGluZy1sZWZ0OjBweDtwYWRkaW5nLXJpZ2h0OjBweDsifX0sIjEiOnsiaW5mb3dpbmRvdy11ZGluZSI6eyJmYy1pdGVtLW1ldGEuZmMtaXRlbS1zZWNvbmRhcnktdGV4dC1jb2xvci5mYy1pdGVtLXRvcC1zcGFjZS5mYy10ZXh0LWNlbnRlciI6ImJhY2tncm91bmQtaW1hZ2U6bm9uZTtmb250LWZhbWlseTotYXBwbGUtc3lzdGVtLCBCbGlua01hY1N5c3RlbUZvbnQsIFwiU2Vnb2UgVUlcIiwgUm9ib3RvLCBPeHlnZW4tU2FucywgVWJ1bnR1LCBDYW50YXJlbGwsIFwiSGVsdmV0aWNhIE5ldWVcIiwgc2Fucy1zZXJpZjtmb250LXdlaWdodDo0MDA7Zm9udC1zaXplOjE0cHg7Y29sb3I6cmdiKDExOSwgMTE5LCAxMTkpO2xpbmUtaGVpZ2h0OjE4cHg7YmFja2dyb3VuZC1jb2xvcjpyZ2JhKDAsIDAsIDAsIDApO2ZvbnQtc3R5bGU6bm9ybWFsO3RleHQtYWxpZ246Y2VudGVyO3RleHQtZGVjb3JhdGlvbjpub25lO21hcmdpbi10b3A6MHB4O21hcmdpbi1ib3R0b206MTBweDttYXJnaW4tbGVmdDowcHg7bWFyZ2luLXJpZ2h0OjBweDtwYWRkaW5nLXRvcDowcHg7cGFkZGluZy1ib3R0b206MHB4O3BhZGRpbmctbGVmdDowcHg7cGFkZGluZy1yaWdodDowcHg7In19LCIyIjp7ImluZm93aW5kb3ctdWRpbmUiOnsiZmMtaXRlbS10aXRsZS5mYy1pdGVtLXByaW1hcnktdGV4dC1jb2xvci5mYy10ZXh0LWNlbnRlciI6ImJhY2tncm91bmQtaW1hZ2U6bm9uZTtmb250LWZhbWlseTotYXBwbGUtc3lzdGVtLCBCbGlua01hY1N5c3RlbUZvbnQsIFwiU2Vnb2UgVUlcIiwgUm9ib3RvLCBPeHlnZW4tU2FucywgVWJ1bnR1LCBDYW50YXJlbGwsIFwiSGVsdmV0aWNhIE5ldWVcIiwgc2Fucy1zZXJpZjtmb250LXdlaWdodDo3MDA7Zm9udC1zaXplOjE2cHg7Y29sb3I6cmdiKDY4LCA2OCwgNjgpO2xpbmUtaGVpZ2h0OjIwcHg7YmFja2dyb3VuZC1jb2xvcjpyZ2JhKDAsIDAsIDAsIDApO2ZvbnQtc3R5bGU6bm9ybWFsO3RleHQtYWxpZ246Y2VudGVyO3RleHQtZGVjb3JhdGlvbjpub25lO21hcmdpbi10b3A6MHB4O21hcmdpbi1ib3R0b206MTVweDttYXJnaW4tbGVmdDowcHg7bWFyZ2luLXJpZ2h0OjBweDtwYWRkaW5nLXRvcDowcHg7cGFkZGluZy1ib3R0b206MHB4O3BhZGRpbmctbGVmdDowcHg7cGFkZGluZy1yaWdodDowcHg7In19LCIzIjp7ImluZm93aW5kb3ctdWRpbmUiOnsiZmMtaXRlbS1jb250ZW50LmZjLWl0ZW0tYm9keS10ZXh0LWNvbG9yLmZjLWl0ZW0tdG9wLXNwYWNlIjoiYmFja2dyb3VuZC1pbWFnZTpub25lO2ZvbnQtZmFtaWx5Oi1hcHBsZS1zeXN0ZW0sIEJsaW5rTWFjU3lzdGVtRm9udCwgXCJTZWdvZSBVSVwiLCBSb2JvdG8sIE94eWdlbi1TYW5zLCBVYnVudHUsIENhbnRhcmVsbCwgXCJIZWx2ZXRpY2EgTmV1ZVwiLCBzYW5zLXNlcmlmO2ZvbnQtd2VpZ2h0OjQwMDtmb250LXNpemU6MTRweDtjb2xvcjpyZ2IoMTE5LCAxMTksIDExOSk7bGluZS1oZWlnaHQ6MThweDtiYWNrZ3JvdW5kLWNvbG9yOnJnYmEoMCwgMCwgMCwgMCk7Zm9udC1zdHlsZTpub3JtYWw7dGV4dC1hbGlnbjpzdGFydDt0ZXh0LWRlY29yYXRpb246bm9uZTttYXJnaW4tdG9wOjVweDttYXJnaW4tYm90dG9tOjEwcHg7bWFyZ2luLWxlZnQ6MHB4O21hcmdpbi1yaWdodDowcHg7cGFkZGluZy10b3A6MHB4O3BhZGRpbmctYm90dG9tOjBweDtwYWRkaW5nLWxlZnQ6MHB4O3BhZGRpbmctcmlnaHQ6MHB4OyJ9fSwiNCI6eyJpdGVtLWRlZmF1bHQiOnsid3BnbXBfbG9jYXRpb25zIjoiYmFja2dyb3VuZC1pbWFnZTpub25lO2ZvbnQtZmFtaWx5Oi1hcHBsZS1zeXN0ZW0sIEJsaW5rTWFjU3lzdGVtRm9udCwgXCJTZWdvZSBVSVwiLCBSb2JvdG8sIE94eWdlbi1TYW5zLCBVYnVudHUsIENhbnRhcmVsbCwgXCJIZWx2ZXRpY2EgTmV1ZVwiLCBzYW5zLXNlcmlmO2ZvbnQtd2VpZ2h0OjQwMDtmb250LXNpemU6MTZweDtjb2xvcjpyZ2IoMTE5LCAxMTksIDExOSk7bGluZS1oZWlnaHQ6MjAuOHB4O2JhY2tncm91bmQtY29sb3I6cmdiKDI1NSwgMjU1LCAyNTUpO2ZvbnQtc3R5bGU6bm9ybWFsO3RleHQtYWxpZ246c3RhcnQ7dGV4dC1kZWNvcmF0aW9uOm5vbmU7bWFyZ2luLXRvcDowcHg7bWFyZ2luLWJvdHRvbTowcHg7bWFyZ2luLWxlZnQ6MHB4O21hcmdpbi1yaWdodDowcHg7cGFkZGluZy10b3A6MjRweDtwYWRkaW5nLWJvdHRvbToyNHB4O3BhZGRpbmctbGVmdDoyMHB4O3BhZGRpbmctcmlnaHQ6MjBweDsifX0sIjUiOnsiaXRlbS1kZWZhdWx0Ijp7IndwZ21wX2xvY2F0aW9uc19oZWFkIjoiYmFja2dyb3VuZC1pbWFnZTpub25lO2ZvbnQtZmFtaWx5Oi1hcHBsZS1zeXN0ZW0sIEJsaW5rTWFjU3lzdGVtRm9udCwgXCJTZWdvZSBVSVwiLCBSb2JvdG8sIE94eWdlbi1TYW5zLCBVYnVudHUsIENhbnRhcmVsbCwgXCJIZWx2ZXRpY2EgTmV1ZVwiLCBzYW5zLXNlcmlmO2ZvbnQtd2VpZ2h0OjQwMDtmb250LXNpemU6MTZweDtjb2xvcjpyZ2IoMTE5LCAxMTksIDExOSk7bGluZS1oZWlnaHQ6MjAuOHB4O2JhY2tncm91bmQtY29sb3I6cmdiYSgwLCAwLCAwLCAwKTtmb250LXN0eWxlOm5vcm1hbDt0ZXh0LWFsaWduOnN0YXJ0O3RleHQtZGVjb3JhdGlvbjpub25lO21hcmdpbi10b3A6MHB4O21hcmdpbi1ib3R0b206MTBweDttYXJnaW4tbGVmdDowcHg7bWFyZ2luLXJpZ2h0OjBweDtwYWRkaW5nLXRvcDowcHg7cGFkZGluZy1ib3R0b206MHB4O3BhZGRpbmctbGVmdDowcHg7cGFkZGluZy1yaWdodDowcHg7In19LCI2Ijp7Iml0ZW0tZGVmYXVsdCI6eyJwbGFjZV90aXRsZSI6ImJhY2tncm91bmQtaW1hZ2U6bm9uZTtmb250LWZhbWlseTotYXBwbGUtc3lzdGVtLCBCbGlua01hY1N5c3RlbUZvbnQsIFwiU2Vnb2UgVUlcIiwgUm9ib3RvLCBPeHlnZW4tU2FucywgVWJ1bnR1LCBDYW50YXJlbGwsIFwiSGVsdmV0aWNhIE5ldWVcIiwgc2Fucy1zZXJpZjtmb250LXdlaWdodDo3MDA7Zm9udC1zaXplOjIwcHg7Y29sb3I6cmdiKDY3LCAxNDQsIDI1NSk7bGluZS1oZWlnaHQ6MjhweDtiYWNrZ3JvdW5kLWNvbG9yOnJnYmEoMCwgMCwgMCwgMCk7Zm9udC1zdHlsZTpub3JtYWw7dGV4dC1hbGlnbjpzdGFydDt0ZXh0LWRlY29yYXRpb246bm9uZTttYXJnaW4tdG9wOjBweDttYXJnaW4tYm90dG9tOjBweDttYXJnaW4tbGVmdDowcHg7bWFyZ2luLXJpZ2h0OjBweDtwYWRkaW5nLXRvcDowcHg7cGFkZGluZy1ib3R0b206MHB4O3BhZGRpbmctbGVmdDowcHg7cGFkZGluZy1yaWdodDowcHg7In19LCI3Ijp7Iml0ZW0tZGVmYXVsdCI6eyJ3cGdtcF9sb2NhdGlvbl9tZXRhIjoiYmFja2dyb3VuZC1pbWFnZTpub25lO2ZvbnQtZmFtaWx5Oi1hcHBsZS1zeXN0ZW0sIEJsaW5rTWFjU3lzdGVtRm9udCwgXCJTZWdvZSBVSVwiLCBSb2JvdG8sIE94eWdlbi1TYW5zLCBVYnVudHUsIENhbnRhcmVsbCwgXCJIZWx2ZXRpY2EgTmV1ZVwiLCBzYW5zLXNlcmlmO2ZvbnQtd2VpZ2h0OjQwMDtmb250LXNpemU6MTZweDtjb2xvcjpyZ2IoMTE5LCAxMTksIDExOSk7bGluZS1oZWlnaHQ6MjAuOHB4O2JhY2tncm91bmQtY29sb3I6cmdiYSgwLCAwLCAwLCAwKTtmb250LXN0eWxlOm5vcm1hbDt0ZXh0LWFsaWduOnN0YXJ0O3RleHQtZGVjb3JhdGlvbjpub25lO21hcmdpbi10b3A6MHB4O21hcmdpbi1ib3R0b206MHB4O21hcmdpbi1sZWZ0OjBweDttYXJnaW4tcmlnaHQ6MHB4O3BhZGRpbmctdG9wOjBweDtwYWRkaW5nLWJvdHRvbTowcHg7cGFkZGluZy1sZWZ0OjBweDtwYWRkaW5nLXJpZ2h0OjBweDsifX0sIjgiOnsiaXRlbS1kZWZhdWx0Ijp7IndwZ21wX2xvY2F0aW9uc19jb250ZW50IjoiYmFja2dyb3VuZC1pbWFnZTpub25lO2ZvbnQtZmFtaWx5Oi1hcHBsZS1zeXN0ZW0sIEJsaW5rTWFjU3lzdGVtRm9udCwgXCJTZWdvZSBVSVwiLCBSb2JvdG8sIE94eWdlbi1TYW5zLCBVYnVudHUsIENhbnRhcmVsbCwgXCJIZWx2ZXRpY2EgTmV1ZVwiLCBzYW5zLXNlcmlmO2ZvbnQtd2VpZ2h0OjQwMDtmb250LXNpemU6MTZweDtjb2xvcjpyZ2IoMTE5LCAxMTksIDExOSk7bGluZS1oZWlnaHQ6MjAuOHB4O2JhY2tncm91bmQtY29sb3I6cmdiYSgwLCAwLCAwLCAwKTtmb250LXN0eWxlOm5vcm1hbDt0ZXh0LWFsaWduOnN0YXJ0O3RleHQtZGVjb3JhdGlvbjpub25lO21hcmdpbi10b3A6MHB4O21hcmdpbi1ib3R0b206MHB4O21hcmdpbi1sZWZ0OjBweDttYXJnaW4tcmlnaHQ6MHB4O3BhZGRpbmctdG9wOjBweDtwYWRkaW5nLWJvdHRvbTowcHg7cGFkZGluZy1sZWZ0OjBweDtwYWRkaW5nLXJpZ2h0OjBweDsifX19IjtzOjE4OiJpbmZvd2luZG93X3NldHRpbmciO3M6NjkzOiImbHQ7ZGl2IGNsYXNzPSZxdW90O2ZjLWl0ZW0tYm94IGZjLWl0ZW0tbm8tcGFkZGluZyZxdW90OyZndDsNCiAgICB7bWFya2VyX2ltYWdlfQ0KICAgICZsdDtkaXYgY2xhc3M9JnF1b3Q7ZmMtaXRlbWNvbnRlbnQtcGFkZGluZyZxdW90OyZndDsNCiAgICAgICAgJmx0O2RpdiBjbGFzcz0mcXVvdDtmYy1pdGVtLXBhZGRpbmctY29udGVudF8yMCZxdW90OyZndDsNCiAgICAgICAgICAgICZsdDtkaXYgY2xhc3M9JnF1b3Q7ZmMtaXRlbS1tZXRhIGZjLWl0ZW0tc2Vjb25kYXJ5LXRleHQtY29sb3IgZmMtaXRlbS10b3Atc3BhY2UgZmMtdGV4dC1jZW50ZXImcXVvdDsmZ3Q7e21hcmtlcl9jYXRlZ29yeX0mbHQ7L2RpdiZndDsNCiAgICAgICAgICAgICZsdDtkaXYgY2xhc3M9JnF1b3Q7ZmMtaXRlbS10aXRsZSBmYy1pdGVtLXByaW1hcnktdGV4dC1jb2xvciBmYy10ZXh0LWNlbnRlciZxdW90OyZndDt7bWFya2VyX3RpdGxlfSZsdDsvZGl2Jmd0Ow0KICAgICAgICAgICAgJmx0O2RpdiBjbGFzcz0mcXVvdDtmYy1pdGVtLWNvbnRlbnQgZmMtaXRlbS1ib2R5LXRleHQtY29sb3IgZmMtaXRlbS10b3Atc3BhY2UmcXVvdDsmZ3Q7DQogICAgICAgICAgICAgICAge21hcmtlcl9tZXNzYWdlfQ0KICAgICAgICAgICAgJmx0Oy9kaXYmZ3Q7DQoNCiAgICAgICAgJmx0Oy9kaXYmZ3Q7DQogICAgJmx0Oy9kaXYmZ3Q7DQombHQ7L2RpdiZndDsiO3M6Mjc6IndwZ21wX2NhdGVnb3J5ZGlzcGxheWZvcm1hdCI7czoxMTk1OiImbHQ7ZGl2IGNsYXNzPSZxdW90O3dwZ21wX2xvY2F0aW9ucyZxdW90OyZndDsNCiAgICAmbHQ7ZGl2IGNsYXNzPSZxdW90O3dwZ21wX2xvY2F0aW9uc19oZWFkJnF1b3Q7Jmd0Ow0KICAgICAgICAmbHQ7ZGl2IGNsYXNzPSZxdW90O3dwZ21wX2xvY2F0aW9uX3RpdGxlJnF1b3Q7Jmd0Ow0KICAgICAgICAgICAgJmx0O2EgaHJlZj0mcXVvdDsmcXVvdDsgY2xhc3M9JnF1b3Q7cGxhY2VfdGl0bGUmcXVvdDsgZGF0YS16b29tPSZxdW90O3ttYXJrZXJfem9vbX0mcXVvdDsgZGF0YS1tYXJrZXI9JnF1b3Q7e21hcmtlcl9pZH0mcXVvdDsmZ3Q7e21hcmtlcl90aXRsZX0mbHQ7L2EmZ3Q7DQogICAgICAgICZsdDsvZGl2Jmd0Ow0KICAgICAgICAmbHQ7ZGl2IGNsYXNzPSZxdW90O3dwZ21wX2xvY2F0aW9uX21ldGEmcXVvdDsmZ3Q7DQogICAgICAgICAgICAmbHQ7c3BhbiBjbGFzcz0mcXVvdDt3cGdtcF9sb2NhdGlvbl9jYXRlZ29yeSBmYy1pbmZvYm94LWNhdGVnb3JpZXMmcXVvdDsmZ3Q7e21hcmtlcl9jYXRlZ29yeX0mbHQ7L3NwYW4mZ3Q7DQogICAgICAgICZsdDsvZGl2Jmd0Ow0KICAgICZsdDsvZGl2Jmd0Ow0KICAgICZsdDtkaXYgY2xhc3M9JnF1b3Q7d3BnbXBfbG9jYXRpb25zX2NvbnRlbnQmcXVvdDsmZ3Q7DQogICAgICAgIHttYXJrZXJfaW1hZ2V9IHttYXJrZXJfbWVzc2FnZX0NCgkJJmx0O2RpdiZndDsNCgkJCSZsdDthZGRyZXNzIHN0eWxlPSZxdW90O2ZvbnQtc2l6ZTogLjk1cmVtO3BhZGRpbmctdG9wOjVweDsmcXVvdDsmZ3Q7Jmx0O3NwYW4gc3R5bGU9JnF1b3Q7Zm9udC13ZWlnaHQ6NTAwOyBmb250LXNpemU6Ljk1cmVtOyZxdW90OyZndDtBZGRyZXNzOiZsdDsvc3BhbiZndDsge21hcmtlcl9hZGRyZXNzfSZsdDsvYWRkcmVzcyZndDsNCgkJJmx0Oy9kaXYmZ3Q7DQoJCSZsdDtkaXYgY2xhc3M9JnF1b3Q7d3BnbXAtYWN0aW9ucyZxdW90OyZndDsNCgkJICAmbHQ7YSBocmVmPSZxdW90O3tnZXRfZGlyZWN0aW9uc19saW5rfSZxdW90OyB0YXJnZXQ9JnF1b3Q7X2JsYW5rJnF1b3Q7IGNsYXNzPSZxdW90O3dwZ21wLWFjdGlvbi1saW5rJnF1b3Q7Jmd0O0dldCBEaXJlY3Rpb25zJmx0Oy9hJmd0Ow0KCQkmbHQ7L2RpdiZndDsNCiAgICAmbHQ7L2RpdiZndDsNCiAgICAmbHQ7ZGl2IGNsYXNzPSZxdW90O3dwZ21wX2xvY2F0aW9uc19mb290JnF1b3Q7Jmd0OyZsdDsvZGl2Jmd0Ow0KJmx0Oy9kaXYmZ3Q7Ijt9czoyMzoibWFwX2luZm9fd2luZG93X3NldHRpbmciO047czoxNjoic3R5bGVfZ29vZ2xlX21hcCI7YTo0OntzOjE0OiJtYXBmZWF0dXJldHlwZSI7YToxMDp7aTowO3M6MjA6IlNlbGVjdCBGZWF0dXJlZCBUeXBlIjtpOjE7czoyMDoiU2VsZWN0IEZlYXR1cmVkIFR5cGUiO2k6MjtzOjIwOiJTZWxlY3QgRmVhdHVyZWQgVHlwZSI7aTozO3M6MjA6IlNlbGVjdCBGZWF0dXJlZCBUeXBlIjtpOjQ7czoyMDoiU2VsZWN0IEZlYXR1cmVkIFR5cGUiO2k6NTtzOjIwOiJTZWxlY3QgRmVhdHVyZWQgVHlwZSI7aTo2O3M6MjA6IlNlbGVjdCBGZWF0dXJlZCBUeXBlIjtpOjc7czoyMDoiU2VsZWN0IEZlYXR1cmVkIFR5cGUiO2k6ODtzOjIwOiJTZWxlY3QgRmVhdHVyZWQgVHlwZSI7aTo5O3M6MjA6IlNlbGVjdCBGZWF0dXJlZCBUeXBlIjt9czoxNDoibWFwZWxlbWVudHR5cGUiO2E6MTA6e2k6MDtzOjE5OiJTZWxlY3QgRWxlbWVudCBUeXBlIjtpOjE7czoxOToiU2VsZWN0IEVsZW1lbnQgVHlwZSI7aToyO3M6MTk6IlNlbGVjdCBFbGVtZW50IFR5cGUiO2k6MztzOjE5OiJTZWxlY3QgRWxlbWVudCBUeXBlIjtpOjQ7czoxOToiU2VsZWN0IEVsZW1lbnQgVHlwZSI7aTo1O3M6MTk6IlNlbGVjdCBFbGVtZW50IFR5cGUiO2k6NjtzOjE5OiJTZWxlY3QgRWxlbWVudCBUeXBlIjtpOjc7czoxOToiU2VsZWN0IEVsZW1lbnQgVHlwZSI7aTo4O3M6MTk6IlNlbGVjdCBFbGVtZW50IFR5cGUiO2k6OTtzOjE5OiJTZWxlY3QgRWxlbWVudCBUeXBlIjt9czo1OiJjb2xvciI7YToxMDp7aTowO3M6MToiIyI7aToxO3M6MToiIyI7aToyO3M6MToiIyI7aTozO3M6MToiIyI7aTo0O3M6MToiIyI7aTo1O3M6MToiIyI7aTo2O3M6MToiIyI7aTo3O3M6MToiIyI7aTo4O3M6MToiIyI7aTo5O3M6MToiIyI7fXM6MTA6InZpc2liaWxpdHkiO2E6MTA6e2k6MDtzOjI6Im9uIjtpOjE7czoyOiJvbiI7aToyO3M6Mjoib24iO2k6MztzOjI6Im9uIjtpOjQ7czoyOiJvbiI7aTo1O3M6Mjoib24iO2k6NjtzOjI6Im9uIjtpOjc7czoyOiJvbiI7aTo4O3M6Mjoib24iO2k6OTtzOjI6Im9uIjt9fXM6MTM6Im1hcF9sb2NhdGlvbnMiO2E6NTp7aTowO2k6MjM7aToxO2k6MjU7aToyO2k6MjE7aTozO2k6MjQ7aTo0O2k6MjI7fXM6MTc6Im1hcF9sYXllcl9zZXR0aW5nIjthOjE6e3M6OToibWFwX2xpbmtzIjtzOjA6IiI7fXM6MTk6Im1hcF9wb2x5Z29uX3NldHRpbmciO047czoyMDoibWFwX3BvbHlsaW5lX3NldHRpbmciO047czoxOToibWFwX2NsdXN0ZXJfc2V0dGluZyI7YTo1OntzOjQ6ImdyaWQiO3M6MjoiMTUiO3M6ODoibWF4X3pvb20iO3M6MToiMSI7czoxMzoibG9jYXRpb25fem9vbSI7czoyOiIxMCI7czo0OiJpY29uIjtzOjU6IjQucG5nIjtzOjEwOiJob3Zlcl9pY29uIjtzOjU6IjQucG5nIjt9czoxOToibWFwX292ZXJsYXlfc2V0dGluZyI7YTo2OntzOjIwOiJvdmVybGF5X2JvcmRlcl9jb2xvciI7czoxOiIjIjtzOjEzOiJvdmVybGF5X3dpZHRoIjtzOjM6IjIwMCI7czoxNDoib3ZlcmxheV9oZWlnaHQiO3M6MzoiMjAwIjtzOjE2OiJvdmVybGF5X2ZvbnRzaXplIjtzOjI6IjE2IjtzOjIwOiJvdmVybGF5X2JvcmRlcl93aWR0aCI7czoxOiIyIjtzOjIwOiJvdmVybGF5X2JvcmRlcl9zdHlsZSI7czo2OiJkb3R0ZWQiO31zOjExOiJtYXBfZ2VvdGFncyI7czowOiIiO3M6MjI6Im1hcF9pbmZvd2luZG93X3NldHRpbmciO047fQ==',
					);

					foreach ( $sample_data['maps'] as $title => $export_code ) {

						$import_code = wp_unslash( $export_code );
						if ( trim( $import_code ) != '' ) {
							$map_settings = maybe_unserialize( base64_decode( $import_code ) );

							if ( is_object( $map_settings ) ) {
								$sdata                  = array();
								$data                   = (array) $map_settings;
								$sdata['map_locations'] = serialize( wp_unslash( $location_ids ) );

								if ( isset( $data['extensions_fields'] ) ) {
									$sdata['map_all_control']['extensions_fields'] = $data['extensions_fields'];
								}

								if ( isset( $data['map_all_control']['map_control_settings'] ) ) {
									$arr = array();
									$i   = 0;
									foreach ( $data['map_all_control']['map_control_settings'] as $key => $val ) {
										if ( $val['html'] != '' ) {
											$arr[ $i ]['html']     = $val['html'];
											$arr[ $i ]['position'] = $val['position'];
											$i++;
										}
									}
									$sdata['map_all_control']['map_control_settings'] = $arr;
								}

								if ( isset( $data['map_all_control']['custom_filters'] ) ) {
									$custom_filters = array();
									foreach ( $data['map_all_control']['custom_filters'] as $k => $val ) {
										if ( $val['slug'] == '' ) {
											unset( $data['map_all_control']['custom_filters'][ $k ] );
										} else {
											$custom_filters[] = $val;
										}
									}
									$sdata['map_all_control']['custom_filters'] = $custom_filters;
								}

								if ( isset( $data['map_all_control']['location_infowindow_skin']['sourcecode'] ) ) {
									$sdata['map_all_control']['infowindow_setting'] = $data['map_all_control']['location_infowindow_skin']['sourcecode'];
								}

								if ( isset( $data['map_all_control']['post_infowindow_skin']['sourcecode'] ) ) {
									$sdata['map_all_control']['infowindow_geotags_setting'] = $data['map_all_control']['post_infowindow_skin']['sourcecode'];
								}

								if ( isset( $data['map_all_control']['item_skin']['sourcecode'] ) ) {
									$sdata['map_all_control']['wpgmp_categorydisplayformat'] = $data['map_all_control']['item_skin']['sourcecode'];
								}

								$sdata['map_title']                   = sanitize_text_field( wp_unslash( $data['map_title'] ) );
								$sdata['map_width']                   = str_replace( 'px', '', sanitize_text_field( wp_unslash( $data['map_width'] ) ) );
								$sdata['map_height']                  = str_replace( 'px', '', sanitize_text_field( wp_unslash( $data['map_height'] ) ) );
								$sdata['map_zoom_level']              = intval( wp_unslash( $data['map_zoom_level'] ) );
								$sdata['map_type']                    = sanitize_text_field( wp_unslash( $data['map_type'] ) );
								$sdata['map_scrolling_wheel']         = sanitize_text_field( wp_unslash( $data['map_scrolling_wheel'] ) );
								$sdata['map_45imagery']               = sanitize_text_field( wp_unslash( $data['map_45imagery'] ) );
								$sdata['map_street_view_setting']     = serialize( wp_unslash( $data['map_street_view_setting'] ) );
								$sdata['map_all_control']             = serialize( wp_unslash( $data['map_all_control'] ) );
								$sdata['map_info_window_setting']     = serialize( wp_unslash( $data['map_info_window_setting'] ) );
								$sdata['style_google_map']            = serialize( wp_unslash( $data['style_google_map'] ) );
								$sdata['map_layer_setting']           = serialize( wp_unslash( $data['map_layer_setting'] ) );
								$sdata['map_polygon_setting']         = serialize( wp_unslash( $data['map_polygon_setting'] ) );
								$sdata['map_cluster_setting']         = serialize( wp_unslash( $data['map_cluster_setting'] ) );
								$sdata['map_overlay_setting']         = serialize( wp_unslash( $data['map_overlay_setting'] ) );
								$sdata['map_infowindow_setting']      = serialize( wp_unslash( $data['map_infowindow_setting'] ) );
								$sdata['map_geotags']                 = serialize( wp_unslash( $data['map_geotags'] ) );
								$map_ids[]                            = FlipperCode_Database::insert_or_update( TBL_MAP, $sdata, $where = '' );
							}
						}
					}

					if ( $success == true ) {

						$response['success'] = esc_html__( 'Sample Data has been created successfully. Go to Manage Maps and use the map shortcode.', 'wp-google-map-plugin' );

					} else {
						$response['error'] = esc_html__( 'Something went wrong. Please try again.', 'wp-google-map-plugin' );
					}
				} else {
					
					$response['error'] = esc_html__( 'Please enter "YES" in the provided textbox and then submit the form to install sample data.', 'wp-google-map-plugin' );
				}
				
				return $response;
			}
		}

	}
}
