<?php
/*
Plugin Name: JEO API
Plugin URI: http://jeowp.org/jeo-api
Description: Plug a GeoJSON API into your JEO project
Version: 0.0.1
Author: Miguel Peixe
Author URI: http://jeowp.org/
License: MIT
*/

if(!class_exists('JEO_API_Plugin')) {

  class JEO_API_Plugin {

    public function __construct() {

      add_action('after_setup_theme', array($this, 'reset_api'), 100);

      if($this->is_enabled()) {
        add_rewrite_endpoint('geojson', EP_ALL);
        add_filter('query_vars', array($this, 'query_var'));
        add_filter('jeo_markers_geojson', array($this, 'jsonp_callback'));
        add_filter('jeo_markers_data', array($this, 'filter_markers'), 10, 2);
        add_filter('jeo_geojson_content_type', array($this, 'content_type'));
        add_action('jeo_markers_before_print', array($this, 'headers'));
        add_action('pre_get_posts', array($this, 'pre_get_posts'));
        add_action('template_redirect', array($this, 'template_redirect'));
      }

    }

    public static function activate() {
      // Do nothing
    }

    public static function deactivate() {
      // Do nothing
    }

    public function get_dir() {
      return apply_filters('jeo_api_dir', plugin_dir_url(__FILE__));
    }

    public function get_path() {
      return apply_filters('jeo_api_path', dirname(__FILE__));
    }

    // Deactivate theme's native GeoJSON API
    function reset_api() {
      if(class_exists('JEO_API')) {
        remove_filter('jeo_settings_tabs', array($GLOBALS['jeo_api'], 'admin_settings_tab'));
        remove_filter('jeo_settings_form_sections', array($GLOBALS['jeo_api'], 'admin_settings_form_section'));
        if(function_exists('jeo_get_options')) {
          $options = jeo_get_options();
          if($options && isset($options['api']) && $options['api']['enable']) {
            $options['api']['enable'] = false;
            update_option('jeo_settings', $options);
          }
        }
      }
    }

    function is_enabled() {
      // $options = jeo_get_options();
      // return ($options && isset($options['api']) && $options['api']['enable']);
      return false;
    }

    function get_options() {
      $options = jeo_get_options();
      if($options && isset($options['api'])) {
        return $options['api'];
      }
    }

    function query_var($vars) {
      $vars[] = 'geojson';
      $vars[] = 'download';
      return $vars;
    }

    function filter_markers($data, $query) {
      if(isset($query->query['geojson'])) {
        $features_with_geometry = array();
        foreach($data['features'] as $feature) {
          if(isset($feature['geometry']))
          $features_with_geometry[] = $feature;
        }
        $data['features'] = $features_with_geometry;
      }
      return $data;
    }

    function pre_get_posts($query) {
      if(isset($query->query['geojson'])) {
        $query->set('offset', null);
        $query->set('nopaging', null);
        $query->set('paged', (get_query_var('paged')) ? get_query_var('paged') : 1);
      }
    }

    function template_redirect() {
      global $wp_query;
      if(isset($wp_query->query['geojson'])) {
        $query = $this->query();
        $this->get_data(apply_filters('jeo_geojson_api_query', $query));
        exit;
      }
    }

    function jsonp_callback($geojson) {
      global $wp_query;
      if(isset($wp_query->query['geojson']) && isset($_GET['callback'])) {
        $jsonp_callback = preg_replace('/[^a-zA-Z0-9$_]/s', '', $_GET['callback']);
        $geojson = "$jsonp_callback($geojson)";
      }
      return $geojson;
    }

    function content_type($content_type) {
      global $wp_query;
      if(isset($wp_query->query['geojson']) && isset($_GET['callback'])) {
        $content_type = 'application/javascript';
      }
      return $content_type;
    }

    function headers() {
      global $wp_query;
      if(isset($wp_query->query['geojson'])) {
        header('X-Total-Count: ' . $wp_query->found_posts);
        header('Access-Control-Allow-Origin: *');
        if(isset($_GET['download'])) {
          $filename = apply_filters('jeo_geojson_filename', sanitize_title(get_bloginfo('name') . ' ' . wp_title(null, false)));
          header('Content-Disposition: attachment; filename="' . $filename . '.geojson"');
        }
      }
    }

    function get_api_url($query_args = array()) {
      global $wp_query;
      $query_args = (empty($query_args)) ? $wp_query->query : $query_args;
      $query_args = $query_args + array('geojson' => 1);
      return add_query_arg($query_args, home_url('/'));
    }

    function get_download_url($query_args = array()) {
      return add_query_arg(array('download' => 1), $this->get_api_url($query_args));
    }

  }

}

if(class_exists('JEO_API_Plugin')) {

  register_activation_hook(__FILE__, array('JEO_API_Plugin', 'activate'));
  register_deactivation_hook(__FILE__, array('JEO_API_Plugin', 'deactivate'));

  $jeo_api_plugin = new JEO_API_Plugin();

}

include_once($jeo_api_plugin->get_path() . '/settings.php');
