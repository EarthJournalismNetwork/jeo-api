<?php

if(!class_exists('JEO_API_Plugin_Settings')) {

  class JEO_API_Plugin_Settings {

    public function __construct() {

      add_action('admin_menu', array($this, 'admin_menu'));
      add_action('admin_init', array($this, 'init_plugin_settings'));

    }

    function get_options() {
      return get_option('jeo_api');
    }

    function admin_menu() {
      add_options_page(__('JEO GeoJSON API', 'jeo_api'), __('GeoJSON API', 'jeo_api'), 'manage_options', 'jeo_api', array($this, 'admin_page'));
    }

    function admin_page() {
      $this->options = $this->get_options();
      ?>
      <div class="wrap">
        <?php screen_icon(); ?>
        <h2><?php _e('JEO GeoJSON API', 'newsroom'); ?></h2>
        <form method="post" action="options.php">
          <?php
          settings_fields('jeo_api_settings_group');
          do_settings_sections('jeo_api');
          submit_button();
          ?>
        </form>
      </div>
      <?php
    }

    function init_plugin_settings() {

      /*
       * Settings sections
       */
      add_settings_section(
        'jeo_api_settings_general_section',
        __('General settings', 'jeo_api'),
        '',
        'jeo_api'
      );

      add_settings_section(
        'jeo_api_settings_fields_section',
        __('API output fields', 'jeo_api'),
        '',
        'jeo_api'
      );

      add_settings_section(
        'jeo_api_settings_taxonomy_section',
        __('Taxonomies', 'jeo_api'),
        '',
        'jeo_api'
      );

      /*
       * Settings fields
       */


      // Register
      register_setting('jeo_api_settings_group', 'jeo_api');

    }

  }

}

if(class_exists('JEO_API_Plugin_Settings')) {
  $jeo_api_plugin_settings = new JEO_API_Plugin_Settings();
}
