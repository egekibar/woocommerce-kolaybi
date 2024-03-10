<?php

namespace Plugin;

class Updater {
	public $plugin_slug;
	public $version;
	public $cache_key;
	public $plugin_basename;
	public $cache_allowed;

	public function __construct() {

		$this->plugin_slug = plugin_basename( dirname(__DIR__, 1) );
		$this->plugin_basename = "woocommerce-kolaybi-stable/woocommerce-kolaybi.php";
		$this->version = '1.1.1';
		$this->cache_key = 'updater_kolaybi';
		$this->cache_allowed = true;

		if ($this->plugin_slug !== "woocommerce-kolaybi-stable"){
			add_action('admin_notices', function () {
				?>
				<div class="notice notice-error is-dismissible">
					<p>Kolaybi entegrasyon eklentisinin klasör ismini "<b><?= $this->plugin_slug ?></b>" yapmazsanız güncelleme alamayacaksınız!</p>
				</div>
				<?php
			});
		}else{
			add_filter( 'plugins_api', array( $this, 'info' ), 20, 3 );
			add_filter( 'site_transient_update_plugins', array( $this, 'update' ) );
			add_action( 'upgrader_process_complete', array( $this, 'purge' ), 10, 2 );
		}
	}

	public function request(){

		$remote = get_transient( $this->cache_key );

		if( false === $remote || ! $this->cache_allowed ) {

			$remote = wp_remote_get(
				'https://raw.githubusercontent.com/egekibar/woocommerce-kolaybi/main/info.json',
				array(
					'timeout' => 10,
					'headers' => array(
						'Accept' => 'application/json'
					)
				)
			);

			if(
				is_wp_error( $remote )
				|| 200 !== wp_remote_retrieve_response_code( $remote )
				|| empty( wp_remote_retrieve_body( $remote ) )
			) {
				return false;
			}

			set_transient( $this->cache_key, $remote, DAY_IN_SECONDS );

		}

		$remote = json_decode( wp_remote_retrieve_body( $remote ) );

		return $remote;

	}


	function info( $res, $action, $args ) {

		// print_r( $action );
		// print_r( $args );

		// do nothing if you're not getting plugin information right now
		if( 'plugin_information' !== $action ) {
			return false;
		}

		// do nothing if it is not our plugin
		if( $this->plugin_slug !== $args->slug ) {
			return false;
		}

		// get updates
		$remote = $this->request();

		if( ! $remote ) {
			return false;
		}

		$res = new \stdClass();

		$res->name = $remote->name;
		$res->slug = $remote->slug;
		$res->version = $remote->version;
		$res->tested = $remote->tested;
		$res->requires = $remote->requires;
		$res->author = $remote->author;
		$res->author_profile = $remote->author_profile;
		$res->download_link = $remote->download_url;
		$res->trunk = $remote->download_url;
		$res->requires_php = $remote->requires_php;
		$res->last_updated = $remote->last_updated;

		$res->sections = array(
			'description' => $remote->sections->description,
			'installation' => $remote->sections->installation,
			'changelog' => $remote->sections->changelog
		);

		if( ! empty( $remote->banners ) ) {
			$res->banners = array(
				'low' => $remote->banners->low,
				'high' => $remote->banners->high
			);
		}

		return $res;

	}

	public function update( $transient ) {

		if ( empty($transient->checked ) ) {
			return $transient;
		}

		$remote = $this->request();

		if(
			$remote
			&& version_compare( $this->version, $remote->version, '<' )
			&& version_compare( $remote->requires, get_bloginfo( 'version' ), '<' )
			&& version_compare( $remote->requires_php, PHP_VERSION, '<' )
		) {
			$res = new \stdClass();
			$res->slug = $this->plugin_slug;
			$res->plugin = $this->plugin_basename;
			$res->new_version = $remote->version;
			$res->tested = $remote->tested;
			$res->package = $remote->download_url;

			$transient->response[ $res->plugin ] = $res;

		}

		return $transient;

	}

	public function purge(){

		if (
			$this->cache_allowed
			&& 'update' === $options['action']
			&& 'plugin' === $options[ 'type' ]
		) {
			delete_transient( $this->cache_key );
		}

	}
}