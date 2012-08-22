<?php
/*
Plugin Name: jsDelivr - Wordpress CDN Plugin
Plugin URI: http://www.jsdelivr.com
Description: Free Wordpress CDN Plugin 
Author: jsDelivr
Version: 0.1
Author URI: http://www.jsdelivr.com
License: GPLv2 
*/

// jsdelivr - main class      
if (!class_exists('jsdelivr'))
{
  global $wpdb;
  $wpdb->jsd_cdnp = $wpdb->prefix.'jsdelivr_cdn_packages'; // table with CDN packages
  $wpdb->jsd_cdnf = $wpdb->prefix.'jsdelivr_cdn_files'; // table with CDN hosted files
  $wpdb->jsd_files = $wpdb->prefix.'jsdelivr_files'; // list of scanned files from pages
  
  define('JSDT_UNKNOWN', 0);
  define('JSDT_CSS', 1);
  define('JSDT_IMAGE', 2);
  define('JSDT_JAVASCRIPT', 3);
  
  define('JSDM_NONE', 0);
  define('JSDM_MAYBE', 1);
  define('JSDM_FULL', 2);
  
  class jsdelivr
  {
    // turn on/off debug mode
    protected $debug = true;
      
    protected $ld = 'jsdelivr'; // name of localization domain    
    protected $url; // plugin URL
    protected $path; // plugin path
    
    protected $enabled;
        
    // internal values
    private $user_agent = 'jsDelivr WP CDN Plugin';
    private $update_cdn_url = 'http://www.jsdelivr.com/hash.php';
    private $timestamp_url = 'http://www.jsdelivr.com/timestamp.txt';
    private $cdn_baseurl = '//cdn.jsdelivr.net';
    
    // libs hosted by Google CDN
    private $gcdn_baseurl = '//ajax.googleapis.com/ajax/libs';
    private $gcdn = array(
              'jquery' => array(
                  'name' => 'jQuery',
                  'versions' =>  array(
                          '1.8.0', '1.7.2', '1.7.1', '1.7.0', '1.6.4', '1.6.3', '1.6.2', '1.6.1', '1.6.0', '1.5.2', 
                          '1.5.1', '1.5.0', '1.4.4', '1.4.3', '1.4.2', '1.4.1', '1.4.0', '1.3.2', '1.3.1', '1.3.0', 
                          '1.2.6', '1.2.3'
                        ),
                  'files' => array('jquery.js', 'jquery.min.js')                        
                )        
          );
        
    function __construct()
    {                      
      // check used protocol and set proper cdn base URL
      $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443)?"https":"http";
      $this->cdn_baseurl = $protocol.':'.$this->cdn_baseurl;
      
      $this->enabled = get_option('jsdelivr_enabled', false);
            
      if (is_admin())
      {
        $this->url = WP_PLUGIN_URL.'/'.dirname(plugin_basename(__FILE__));
        $this->path = WP_PLUGIN_DIR.'/'.dirname(plugin_basename(__FILE__));

        // load language file
        $locale = get_locale();
        $mo = $this->path.'/languages/'.$locale.'.mo';
        load_textdomain($this->ld, $mo);
        
        add_action('admin_menu', array(&$this, 'admin_menu')); 
        add_action('wp_ajax_jsdelivr_action', array(&$this, 'action'));
        
        if ($this->debug)
        {
          add_action('wp_ajax_jsdelivr_action_debug', array(&$this, 'action_debug'));        
        }

        // wp_cron action
        add_action('jsdelivr_check_update', array(&$this, 'check_update_cdn'));
        
        // show notice when update is available
        if (!isset($_GET['cdn_update']) && get_option('jsdelivr_last_cdn_update', false) &&
            get_option('jsdelivr_last_cdn_update', false) != get_option('jsdelivr_update_cdn_timestamp', false) &&
            ((isset($_GET['page'])?$_GET['page'] == 'jsdelivr':false) || !get_option('jsdelivr_update_notice_dismiss', false))
          )
        {
          add_action('admin_notices', array(&$this, 'update_notice'));
        }
                 
        // install hook
        register_activation_hook(__FILE__, array(&$this, 'activation'));
        register_deactivation_hook(__FILE__, array(&$this, 'deactivation'));
      }
      else
      {
        // plugin is enabled
        if ($this->enabled)
        {
          add_action('init', array(&$this, 'init_onthefly'), 0);
        }
      }
    }
    
    // deactivation
    function deactivation()
    {
      wp_clear_scheduled_hook('jsdelivr_check_update');    
    }
        
    // install/activation
    function activation()
    {      
      global $wpdb;

      $sql1 = "CREATE TABLE {$wpdb->jsd_cdnf} (
                 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                 package_id bigint(20) unsigned NOT NULL,
                 file varchar(255) COLLATE utf8_bin NOT NULL,
                 hash varchar(32) COLLATE utf8_bin NOT NULL,
                 PRIMARY KEY (id)
               );";

      $sql2 = "CREATE TABLE {$wpdb->jsd_cdnp} (
                 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                 name varchar(255) COLLATE utf8_bin NOT NULL,
                 zip varchar(255) COLLATE utf8_bin NOT NULL,
                 version varchar(255) COLLATE utf8_bin NOT NULL,
                 description text COLLATE utf8_bin NOT NULL,
                 homepage varchar(255) COLLATE utf8_bin NOT NULL,
                 author varchar(255) COLLATE utf8_bin NOT NULL,
                 PRIMARY KEY (id)
               );";                  
      
      $sql3 = "CREATE TABLE {$wpdb->jsd_files} (
                 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                 type tinyint(1) unsigned NOT NULL,
                 filename varchar(255) COLLATE utf8_bin NOT NULL,
                 full_filename varchar(255) COLLATE utf8_bin NOT NULL,
                 version varchar(255) COLLATE utf8_bin NOT NULL,
                 hash varchar(32) COLLATE utf8_bin NOT NULL,
                 url varchar(255) COLLATE utf8_bin NOT NULL,
                 html text COLLATE utf8_bin NOT NULL,
                 cdn_id bigint(20) unsigned NOT NULL,
                 gcdn varchar(255) COLLATE utf8_bin NOT NULL,
                 match_status tinyint(1) unsigned NOT NULL,
                 enabled tinyint(1) unsigned NOT NULL,
                 footer tinyint(1) unsigned NOT NULL,
                 defer tinyint(1) unsigned NOT NULL,
                 async tinyint(1) unsigned NOT NULL,
                 priority int(12) NOT NULL,
                 PRIMARY KEY (id)
               );";
      
      require_once(ABSPATH.'wp-admin/includes/upgrade.php');
      dbDelta(array($sql1, $sql2, $sql3));      
      
      if (!wp_next_scheduled('jsdelivr_check_update'))
      {
        wp_schedule_event(time(), 'daily', 'jsdelivr_check_update');
      }                             
    }

    // uninstall
    static function uninstall()
    {
      global $wpdb;
      
      $wpdb->query("DROP TABLE {$wpdb->jsd_cdnf}");
      $wpdb->query("DROP TABLE {$wpdb->jsd_cdnp}");
      $wpdb->query("DROP TABLE {$wpdb->jsd_files}");
      
      delete_option('jsdelivr_update_cdn_timestamp');
      delete_option('jsdelivr_last_cdn_update');
      delete_option('jsdelivr_last_scan');
      delete_option('jsdelivr_enabled');
      delete_option('jsdelivr_update_notice_dismiss');
    }
    
    // update notice at top of admin area
    function update_notice()
    {
      require_once $this->path.'/backend/update_notice.php';
    }    

    // add menu in Settings    
    function admin_menu()
    {
      $hook = add_options_page(__('jsDelivr CDN', $this->ld), __('jsDelivr CDN', $this->ld), 'manage_options', 'jsdelivr', array(&$this, 'options_page'));
      add_filter('plugin_action_links_'.plugin_basename(__FILE__), array(&$this, 'filter_plugin_actions'), 10, 2);
      
      // add help tab
      if (function_exists('get_current_screen'))
      {
        add_action("load-$hook", array(&$this, 'admin_load'));
      }
      else // this is for backward compatibility < WP 3.3
      {
        ob_start();
        require_once $this->path.'/backend/overview.php';
        $content = ob_get_contents();
        ob_end_clean();
        add_contextual_help($hook, $content);
      }
            
      add_action("admin_print_styles-$hook", array(&$this, 'add_admin_css'));                             
      add_action("admin_print_scripts-$hook", array(&$this, 'add_admin_js'));      
    }
    
    // it's called when page is loaded
    function admin_load()
    {
      // add help tab
      $screen = get_current_screen();
      
      // overview tab
      $screen->add_help_tab(array( 
            'id' => 'jsdelivr_overview',
            'title' => __('Overview', $this->ld),
            'callback' => array(&$this, 'help_overview')            
          ));
       
      // debug tab for developers   
      if ($this->debug)
      {
        $screen->add_help_tab(array( 
            'id' => 'jsdelivr_debug',
            'title' => __('Debug', $this->ld),
            'callback' => array(&$this, 'help_debug')            
          ));                        
      }                  
    }
    
    function help_overview() { require_once $this->path.'/backend/overview.php'; }
    function help_debug() { require_once $this->path.'/backend/debug.php'; }
    
    
    function add_admin_css()
    {
      wp_enqueue_style('jsdelivr_styles', $this->url.'/backend/styles.css', array(), '1.0', 'all');          
    }
    
    function add_admin_js()
    {
      wp_enqueue_script('jquery');
      wp_enqueue_script('jquery.blockUI', $this->url.'/3rdparty/jquery.blockUI.js', array('jquery'), '2.42');          
      wp_enqueue_script('jsdelivr', $this->url.'/backend/jsdelivr.js', array('jquery'), '1.0', false);          
      wp_localize_script('jsdelivr', 'jsdelivr_data',
            array(
              'action_admin' => admin_url('admin-ajax.php?action=jsdelivr_action'),
              'default_url' => admin_url('options-general.php?page=jsdelivr&t='.time()),
              'cdn_update' => isset($_GET['cdn_update'])?true:false,
              'text' => array(
                  'please_wait' => __('Please wait...', $this->ld),
                  'error_scan' => __('Error occured during scan process.', $this->ld),
                  'error_update_cdn' => __('Error occured during update process.', $this->ld)
                )
              ));              
    }
                    
    // menu Settings on Plugin page
    function filter_plugin_actions($l, $file)
    {
      $settings_link = '<a href="options-general.php?page=jsdelivr">'.__('Settings').'</a>';
      array_unshift($l, $settings_link); 
      return $l;       
    }
    
    // debug actions, available only if debug mode is activated
    function action_debug()
    {
      $action = isset($_GET['jsd_action'])?$_GET['jsd_action']:false;
      
      switch($action)
      {
        case 'check_update':
          $this->check_update_cdn();
          break;
          
        case 'reset':
          global $wpdb;
          $wpdb->query("TRUNCATE TABLE {$wpdb->jsd_cdnf}");
          $wpdb->query("TRUNCATE TABLE {$wpdb->jsd_cdnp}");
          $wpdb->query("TRUNCATE TABLE {$wpdb->jsd_files}");      
          delete_option('jsdelivr_update_cdn_timestamp');
          delete_option('jsdelivr_last_cdn_update');
          delete_option('jsdelivr_last_scan');
          delete_option('jsdelivr_enabled');
          delete_option('jsdelivr_update_notice_dismiss');          
          break;
      }
      
      wp_redirect(admin_url('options-general.php?page=jsdelivr'));
      exit;
    }
    
    // handle AJAX actions
    function action()
    {
      if (!isset($_POST['jsd_action'])) return;
            
      header("Content-Type: application/json");
      
      switch($_POST['jsd_action'])
      {
        case 'scan':
          $r = $this->scan();
          if ($r)
          {
            echo json_encode(array('status' => 1));           
          }
          else
          {
            echo json_encode(array('status' => 2));                       
          }
          break;
          
        case 'update_cdn':
          $r = $this->update_cdn();
          if ($r)
          {
            echo json_encode(array('status' => 1));                     
          }
          else
          {
            echo json_encode(array('status' => 2));                     
          }        
          break;
          
        case 'dismiss_update_notice':
          update_option('jsdelivr_update_notice_dismiss', true);
          break;      
      }
              
      exit();
    }
    
    // options page
    function options_page()
    {
      global $wpdb;            
      $action_url = admin_url('options-general.php?page=jsdelivr');
      
      require_once $this->path.'/backend/top.php';            
                              
      // save options
      if (isset($_POST['save_options']) && $_POST['save_options'])
      {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'jsdelivr_nonce'))
        {
          die(__('Whoops! There was a problem with the data you posted. Please go back and try again.', $this->ld));
        }
        
        $ids = isset($_POST['ids'])?$_POST['ids']:array();
        $js_load = isset($_POST['js_load'])?$_POST['js_load']:array();
        $move_footer = isset($_POST['move_footer'])?$_POST['move_footer']:array();
        $priority = isset($_POST['priority'])?$_POST['priority']:array();
        $enabled = isset($_POST['enabled'])?$_POST['enabled']:array();
                
        while(list(, $id) = @each($ids))
        {
          $wpdb->query($wpdb->prepare("
                  UPDATE {$wpdb->jsd_files}
                  SET enabled = %d, 
                      footer = %d, 
                      defer = %d, 
                      async = %d, 
                      priority = %d
                  WHERE id = %d
                ", 
                  isset($enabled[$id])?1:0,
                  isset($move_footer[$id])?$move_footer[$id]:0,
                  isset($js_load[$id])?($js_load[$id]==2?1:0):0,
                  isset($js_load[$id])?($js_load[$id]==1?1:0):0,                  
                  isset($priority[$id])?$priority[$id]:0,                  
                  $id
                ));
        }
        
        $this->enabled = isset($_POST['jsd_enabled'])?$_POST['jsd_enabled']:false;
        update_option('jsdelivr_enabled', $this->enabled);
        
        $this->generate_replace_data();
        
        // cache flush
        $this->cache_flush();
        
        echo '<div class="updated"><p>'.__('Settings were sucessfully saved.', $this->ld).'</p></div>'; 
      }

      // get info about last cdn update/scan
      $last_cdn_update = get_option('jsdelivr_last_cdn_update', false);
      $last_scan = get_option('jsdelivr_last_scan', false); 
      
      $files = $wpdb->get_results("
                      SELECT files.id AS file_id,
                             files.type AS file_type,
                             files.filename AS file_filename,
                             files.full_filename AS file_full_filename,
                             files.url AS file_url,
                             files.html AS file_html,
                             files.version AS file_version,
                             files.match_status AS file_match,
                             files.enabled AS file_enabled,
                             files.footer AS file_footer,
                             files.defer AS file_defer,
                             files.async AS file_async,
                             files.priority AS file_priority,
                             files.hash AS file_hash,
                             files.gcdn AS file_gcdn,
                             cdn_files.file AS cdn_filename,
                             cdn_packages.name AS cdn_name,
                             cdn_packages.version AS cdn_version,
                             cdn_packages.description AS cdn_description,
                             cdn_packages.homepage AS cdn_homepage,
                             cdn_packages.author AS cdn_author  
                      FROM {$wpdb->jsd_files} AS files
                      LEFT JOIN {$wpdb->jsd_cdnf} AS cdn_files ON cdn_files.id = files.cdn_id
                      LEFT JOIN {$wpdb->jsd_cdnp} AS cdn_packages ON cdn_packages.id = cdn_files.package_id
                      WHERE (files.match_status > 0) OR
                            (files.match_status = 0 AND files.type = ".JSDT_JAVASCRIPT.")
                      ORDER BY files.match_status DESC, files.type DESC, cdn_packages.id ASC", ARRAY_A);
     
      require_once $this->path.'/backend/options.php';
    }
                      
    // on the fly - buffer callback
    function callback_onthefly($buffer)
    {            
      $data_from = get_option('jsdelivr_data_from', false);
      $data_to = get_option('jsdelivr_data_to', false);
      
      if ($data_from && $data_to)
      {                
        return str_replace($data_from, $data_to, $buffer);
      }
      else
      {
        return $buffer;
      }
    }
    
    // on the fly - init function
    function init_onthefly()
    {
      ob_start(array(&$this, 'callback_onthefly'));
    }    
        
    // loads content of website at URL address
    protected function get_content($url)
    {      
      if (function_exists('curl_init'))
      {
        $ch = curl_init();
        curl_setopt_array($ch,
                array(CURLOPT_URL => $url,
                      CURLOPT_FRESH_CONNECT => true,
                      CURLOPT_FOLLOWLOCATION => false,
                      CURLOPT_USERAGENT => $this->user_agent,
                      CURLOPT_HEADER => true,
                      CURLOPT_RETURNTRANSFER => true 
                ));
                   						
        $data = curl_exec($ch);        
        $delimiter = strpos($data, "\r\n\r\n");
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // handle redirect
        if ($http_code == 301 || $http_code == 302)
        {        
          $header = substr($data, 0, $delimiter);
          $matches = array();
          preg_match('/Location:(.*?)\n/', $header, $matches);
          $url = @parse_url(trim(array_pop($matches)));
          if ($url)
          {            
            $last_url = parse_url(curl_getinfo($ch, CURLINFO_EFFECTIVE_URL)); 
            if (!$url['scheme']) $url['scheme'] = $last_url['scheme'];
            if (!$url['host']) $url['host'] = $last_url['host'];
            if (!$url['path']) $url['path'] = $last_url['path'];        
            $new_url = $url['scheme'] . '://' . $url['host'] . $url['path'] . ($url['query']?'?'.$url['query']:'');
            $data = $this->get_content($new_url);
          }
        }
        else
        {                                                                      
          $data = substr($data, $delimiter + 4, strlen($data) - $delimiter - 4);
        }         
        curl_close($ch);
      }
      else
      {
        // TODO: add more methods ?        
        $data = false;          
      }
      return $data;             
    }
    
    // check update CDN timestamp    
    function check_update_cdn()
    {
      $timestamp = trim($this->get_content($this->timestamp_url));
      
      if (!$timestamp)
      {
        return false;
      }
      
      if ($timestamp == get_option('jsdelivr_last_cdn_update', false) ||
          $timestamp == get_option('jsdelivr_update_cdn_timestamp', false))
      {
        return false;
      }
      
      update_option('jsdelivr_update_cdn_timestamp', $timestamp);
      update_option('jsdelivr_update_notice_dismiss', false);
      return $timestamp;    
    }
    
    // update data from CDN server
    protected function update_cdn()    
    {
      global $wpdb;

      if (!$data = $this->get_content($this->update_cdn_url))
      {
        return false;
      }
      
      // truncate tables
      $wpdb->query("TRUNCATE TABLE ".$wpdb->jsd_cdnp); 
      $wpdb->query("TRUNCATE TABLE ".$wpdb->jsd_cdnf); 
    
      $packages = json_decode($data, true);

      while(list(, $package) = @each($packages['package']))
      {
        $wpdb->query($wpdb->prepare("
                   INSERT INTO {$wpdb->jsd_cdnp}
                   SET name = %s,
                       zip = %s,
                       version = %s,
                       description = %s,
                       homepage = %s,
                       author = %s 
              ", $package['name'], $package['zip'], $package['version'], $package['description'],
                $package['homepage'], $package['author']));

        $insert_id = $wpdb->insert_id;
        
        $sql_values = array();
        while(list($file_id, $file_name) = @each($package['files']))
        {
          $sql_values[] = "($insert_id, '".$wpdb->escape($file_name)."', '".$wpdb->escape($package['hashes'][$file_id])."')";
        }
        
        if (count($sql_values) > 0)
        {                
          $sql = "INSERT INTO {$wpdb->jsd_cdnf} (`package_id`, `file`, `hash`) VALUES ".@implode(',', $sql_values);
          $wpdb->query($sql);
        }                
      }
      
      if (!$timestamp = get_option('jsdelivr_update_cdn_timestamp', false))
      {      
        $timestamp = $this->check_update_cdn();
      }                              
      update_option('jsdelivr_last_cdn_update', $timestamp);

      $this->generate_replace_data();
          
      return true;
    }
        
    // creates list of scripts and CSS links
    protected function parse_html($data)
    {                                            
      if (preg_match_all('/(<link.[^>]+href\s*=\s*[\'"](.[^\."\']+\.css)(\?ver=(.[^\'"]+?)|)[\'"].*?'.'>)|(<script.[^>]+src\s*=\s*[\'|"](.[^\?"\']+)(\?ver=(.[^\'"]+)|)[\'|"].*?<\/script>)|(<img.+?src\s*=\s*[\'|"](.[^\'"]+).*?'.'>)/is', $data, $o))
      {
        $main_url = get_site_url().'/';
        $list = array(); 
        while(list($k, $v) = @each($o[0]))
        {          
          $url = trim($o[2][$k]?$o[2][$k]:($o[6][$k]?$o[6][$k]:($o[10][$k]?$o[10][$k]:'')));

          // skip google hosted files
          if (stripos($url, '.googleapis.com') !== false) continue;
          
          $version = trim($o[4][$k]?$o[4][$k]:($o[8][$k]?$o[8][$k]:''));
          $file = str_replace($main_url, ABSPATH, $url);
          
          $key = md5($url.$version);          
          if (isset($list[$key])) continue;
                              
          $list[$key] = array(
                'type' => $o[1][$k]?JSDT_CSS:($o[5][$k]?JSDT_JAVASCRIPT:($o[10][$k]?JSDT_IMAGE:JSDT_UNKNOWN)), // 0- unknown, 1- css, 2- image,3- javascript
                'html' => $v,
                'url' => $url,
                'file' => $file,
                'rel_filename' => str_replace($main_url, '', $url),
                'hash' => md5_file($file),                 
                'version' => $version
              );        
        }
        return $list;                
      }
      return array();
    }
    
    // match for google hosted file
    protected function match_gcdn($file, $version)
    {
      reset($this->gcdn);
      while(list($name, $data) = @each($this->gcdn))
      {
        if (in_array($file, $data['files']))
        {
          if (in_array($version, $data['versions']))
          {
            $match = JSDM_FULL;          
          }
          else
          {
            $match = JSDM_MAYBE;
          }          
          return array(
                    'name' => $name,
                    'match' => $match          
                  );
        }
      }
      return false; 
    }
    
    // flush cache of 3rd party plugins
    protected function cache_flush()
    {
      // W3 Total Cache
      if (function_exists('w3tc_pgcache_flush'))
      {
        w3tc_pgcache_flush();      
      }
      
      // WP Super Cache
      if (function_exists('wp_cache_clear_cache'))
      {
        wp_cache_clear_cache();
      }    
    }
    
    // scan page and save script/css files into DB
    protected function scan()
    {
      global $wpdb;            
      update_option('jsdelivr_enabled', false);
      
      // get old settings
      $r = $wpdb->get_results("
                      SELECT hash, enabled, footer, defer, async, priority
                      FROM {$wpdb->jsd_files}
                ", ARRAY_A);
      $old_settings = array();
      while(list(, $l) = @each($r))
      {
        $old_settings[$l['hash']] = $l;
      }      
      
      // truncate table with files
      $wpdb->query("TRUNCATE TABLE ".$wpdb->jsd_files);
    
      $pages = array();
      $pages[] = get_bloginfo('home'); // it can scan more URLs, but this is for testing 

      // cache flush
      $this->cache_flush();
      
      $html = '';
      while(list(, $page) = @each($pages))
      {
        if ($c = $this->get_content($page))
        {
          $html.= $c;         
        }        
      }
              
      $list = $this->parse_html($html);
      
      // prepare query and insert into DB
      $sql_values = array();
      while(list(, $item) = @each($list))
      {
        $file = $wpdb->escape($file_a = basename($item['file']));
        $version = $wpdb->escape($item['version']);
        $hash = $wpdb->escape($item['hash']);
        
        $cdn_id = 0;
        $gcdn = '';
                
        if ($match = $this->match_gcdn($file_a, $item['version']))
        {
          $gcdn = $match['name'];
          $match_r = $match['match'];  
        }
        else
        {
          // matching by CDN database                
          $match = $wpdb->get_row("
                   SELECT
                      (SELECT cdn_files.id
                       FROM {$wpdb->jsd_cdnf} AS cdn_files, {$wpdb->jsd_cdnp} AS cdn_packages
                       WHERE (cdn_files.file LIKE '%/%".$file."' OR cdn_files.file = '".$file."') AND
                             cdn_packages.version LIKE '%".$version."%' AND
                             '".$version."' != '' AND
                             cdn_packages.id = cdn_files.package_id
                       LIMIT 1  
                      ) AS match_full,
                      (SELECT cdn_files.id
                       FROM {$wpdb->jsd_cdnf} AS cdn_files
                       WHERE (cdn_files.file LIKE '%/%".$file."' OR cdn_files.file = '".$file."')
                       LIMIT 1                               
                      ) AS match_filename,
                      (SELECT cdn_files.id
                       FROM {$wpdb->jsd_cdnf} AS cdn_files
                       WHERE cdn_files.hash = '".$hash."'
                       LIMIT 1  
                      ) AS match_md5
                ", ARRAY_A);
                        
          if (($match['match_md5']) || ($match['match_full'] && $item['type'] != JSDT_IMAGE)) // full match
          {
            $cdn_id = $match['match_md5']?$match['match_md5']:$match['match_full'];
            $match_r = JSDM_FULL;
          }
          else
          if ($match['match_filename'] && ($item['type'] != JSDT_IMAGE)) // filenames are same, we are not sure if versions are correct
          {          
            $cdn_id = $match['match_filename'];
            $match_r = JSDM_MAYBE;
          }
          else
          {
            $match_r = JSDM_NONE;
          }
        }
        
        if ($old = $old_settings[$item['hash']])
        {
          $enabled = ($match_r != JSDM_NONE?$old['enabled']:'0');
          $footer = $old['footer'];
          $defer = $old['defer'];
          $async = $old['async'];
          $priority = $old['priority'];
        }
        else
        {
          $enabled = $footer = $defer = $async = $priority = 0;
        }
                                                            
        $sql_values[] = "(".$item['type'].",
                          '".$file."',
                          '".$wpdb->escape($item['rel_filename'])."',
                          '".$version."',
                          '".$hash."',
                          '".$wpdb->escape($item['url'])."',
                          '".$wpdb->escape($item['html'])."',
                          ".(int)$cdn_id.", '".$wpdb->escape($gcdn)."', ".(int)$match_r.", $enabled, $footer, $defer, $async, $priority)";         
      }
      
      if (count($sql_values) > 0)
      {
        $sql = "INSERT INTO {$wpdb->jsd_files} 
              (`type`, `filename`, `full_filename`, `version`, `hash`, `url`, `html`, `cdn_id`, `gcdn`, `match_status`, `enabled`, `footer`, `defer`, `async`, `priority`) 
              VALUES ".@implode(',', $sql_values);

        $wpdb->query($sql);        
      }      

      update_option('jsdelivr_last_scan', time());
      update_option('jsdelivr_enabled', $this->enabled);
      
      $this->generate_replace_data();

      // cache flush
      $this->cache_flush();
      
      return true;
    }
    
       
    // get CDN enabled files
    protected function get_enabled_files($only_with_options = false)
    {
      global $wpdb;
      
      $w = '';
      if ($only_with_options)
      {
        $w = "AND (files.async = 1 OR files.defer = 1 OR files.footer > 1)";
      }
            
      $r = $wpdb->get_results("
                      SELECT files.type AS file_type,
                             files.filename AS file_filename,
                             files.url AS file_url,                             
                             files.full_filename AS file_full,
                             files.version AS file_version,
                             files.html AS file_html,
                             files.footer AS file_footer,
                             files.defer AS file_defer,
                             files.async AS file_async,
                             files.gcdn AS file_gcdn,
                             CONCAT('/', cdn_packages.name, '/', cdn_packages.version, '/', cdn_files.file) AS cdn_url_part
                      FROM {$wpdb->jsd_files} AS files
                      LEFT JOIN {$wpdb->jsd_cdnf} AS cdn_files ON cdn_files.id = files.cdn_id 
                      LEFT JOIN {$wpdb->jsd_cdnp} AS cdn_packages ON cdn_packages.id = cdn_files.package_id                          
                      WHERE files.enabled = 1 AND
                            (files.cdn_id > 0 OR files.gcdn != '')
                            $w                             
                      ORDER BY files.priority ASC
                ", ARRAY_A);
      return $r;        
    }
    
    // generate replace data for on-the-fly method
    protected function generate_replace_data()
    {
      // get list of CDN enabled files from database
      $r = $this->get_enabled_files();      
                
      $header_from = array();
      $header_to = array();
      $footer = array();
      while(list(, $l) = @each($r))
      {
        if ($l['file_type'] == JSDT_JAVASCRIPT)
        {
          $header_from[] = $l['file_html'];
          $options = '';
          if ($l['file_async'])
          {
            $options = " async='async'";
          }
          else
          if ($l['file_defer'])
          {
            $options = " defer='defer'";
          }
          
          if ($l['file_gcdn'] && isset($this->gcdn[$l['file_gcdn']]))
          {
            $gcdn = $this->gcdn[$l['file_gcdn']];
            $url = $this->gcdn_baseurl.'/'.$l['file_gcdn'].'/'.(in_array($l['file_version'], $gcdn['versions'])?$l['file_version']:$gcdn['versions'][0]).'/'.$l['file_filename'];
          }
          else
          {
            $url = $this->cdn_baseurl.$l['cdn_url_part']; 
          }

          $script = "<script type='text/javascript'".$options." src='".$url."'></script>";
        
          if ($l['file_footer'])
          {
            $header_to[] = '';
            $footer[] = $script;
          }
          else
          {
            $header_to[] = $script;
          }
        }
        else
        if ($l['file_type'] == JSDT_CSS || $l['file_type'] == JSDT_IMAGE)
        {
          $header_from[] = $l['file_url'];
          $header_to[] = $this->cdn_baseurl.$l['cdn_url_part'];
        }
      }

      if (count($footer) > 0)
      {
        $header_from[] = '</body>';
        $header_to[] = @implode(PHP_EOL, $footer).PHP_EOL.'</body>';
      }
      
      update_option('jsdelivr_data_from', $header_from);
      update_option('jsdelivr_data_to', $header_to);
      
      return true;          
    }    
                
    protected function strip($t)
    {
      return htmlentities(stripslashes($t), ENT_COMPAT, 'UTF-8');
    }            
  }
}

if (class_exists('jsdelivr'))
{
  register_uninstall_hook(__FILE__, array('jsdelivr', 'uninstall'));      
  new jsdelivr();      
}
?>