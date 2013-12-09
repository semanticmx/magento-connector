<?php
/**
 * Plugin Name: Magento Connector
 * Plugin URI: http://semanticweapons.com
 * Description: Magento API for WordPress
 * Version: 1.0
 * Author: Carlos Vences
 * Author http://semanticweapons.com
 * License: GPL2
 * Text Domain: sw
 * Domain Path: /languages
 * Network: true
 */
 
/*  Copyright 2014  Carlos Vences  (email : sales@semantic.mx)

   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License, version 2, as 
   published by the Free Software Foundation.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program; if not, write to the Free Software
   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class SWMagentoConnector {
  
  private $version = '1.0';
  
  private $plugins_url = '';
  private $plugin_url = '';
  private $plugin_dir = '';
  
  private $api_username = NULL;
  private $api_key = NULL;
  private $magento_host = NULL;
  
  static $instance = NULL;
  
  static $magento_proxy = NULL;
  static $current_store = NULL;
  static $categories = array();
  
  private function __construct () {
    $this->init ();
  }
  
  private function init () {
    $this->plugins_url = plugins_url ();
    $this->plugin_url = plugin_dir_url ( __FILE__ );
    $this->plugin_dir = plugin_dir_path ( __FILE__ );
    add_action ( 'admin_menu', array ( &$this, 'addAdminPages' ) );
    
    register_activation_hook ( __FILE__, array ( 'SWMagentoConnector', 'getInstance' ) );
    
    $settings = is_array ( get_site_option ( 'mconnector_settings' ) ) ? get_site_option ( 'mconnector_settings' ) : array();
    $api_username = array_key_exists ( 'api_username' , $settings ) ? $settings['api_username'] : "";
    $api_key = array_key_exists ( 'api_key' , $settings ) ? $settings['api_key'] : "";
    $magento_host = array_key_exists ( 'magento_host' , $settings ) ? $settings['magento_host'] : "";
    
    $this->api_username = $api_username;
    $this->api_key = $api_key;
    $this->magento_host = $magento_host;
    
    add_shortcode( 'magento_categories' , array ( 'SWMagentoConnector', 'doCategories' ) );
    add_shortcode( 'magento_products' , array ( 'SWMagentoConnector', 'doProducts' ) );
    add_shortcode( 'magento_product' , array ( 'SWMagentoConnector', 'doProduct' ) );
  }
  
  public static function doCategories ( $args ) {
    $instance = SWMagentoConnector::getInstance ();
    echo $instance->getCategories();
  }
  
  public static function doProducts ( $args ) {
    $instance = SWMagentoConnector::getInstance ();
    echo $instance->getProducts();
  }
  
  public static function doProduct ( $args ) {
    $instance = SWMagentoConnector::getInstance ();
    echo $instance->getProduct( $args ['product_id'] );
  }
  
  public function getAdminPage () {
    if ( !is_super_admin() ) return;

    if ( strtolower ( $_SERVER ['REQUEST_METHOD'] ) == 'post' ) {
      check_admin_referer ( 'save_api', 'mconnector_admin_settings' );
      $_POST ['mconnector_settings']  = stripslashes_deep ( $_POST ['mconnector_settings'] );
      $old_settings = is_array ( get_site_option ( 'mconnector_settings' ) ) ? get_site_option ( 'mconnector_settings' ) : array ();
      $settings = array_merge ( $old_settings, $_POST ['mconnector_settings'] );
      update_site_option ( 'mconnector_settings', $settings );
      echo '<div id="message" class="updated fade"><p>'.__('Settings Saved!', 'sw').'</p></div>';
    }
    
    $page = $_GET ['page'];
    include_once $this->plugin_dir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'admin-' . strtolower ( $page ) . '.php';
  }
    
  public function addAdminPages () {
    add_menu_page ( __('Magento Connector', 'sw'), __('Connector', 'sw'), 'edit_pages', 'magento-connector', array ( &$this, 'getAdminPage'), $this->plugin_url . 'img/icon_16x16.png' );
    $page = add_submenu_page ( 'magento-connector', __('API Settings', 'sw'), __('API Settings', 'sw'), 'edit_pages', 'magento-api', array ( &$this, 'getAdminPage') );
    do_action ( 'sw_admin_page_after_settings' );
  }
  
  private function getProxy () {
    if (!$this->magento_host && !$this->api_username && !$this->api_key) return FALSE;
    if ( !isset ( $magento_proxy ))
      $magento_proxy = new SoapClient ( $this->magento_host . '/api/v2_soap/?wsdl' );
    return $magento_proxy;
  }
  
  private function authenticate ( $object = NULL ) {
    $proxy = $this->getProxy();
    return $proxy->login ( $this->api_username, $this->api_key );
  }
  
  public function getCurrentStore () {
    $sessionId = $this->authenticate();
    $proxy = $this->getProxy();
    return $proxy->catalogCategoryCurrentStore ( $sessionId );
  }
  
  private function getCategoryList ( $parent_category ) {
    $content = '<ul id="category-' . $parent_category->category_id . '" class="parent_' . str_replace ( ' ', '-',  strtolower ( $parent_category->parent_id ) ) . ' level_' . $parent_category->level . '">';
    foreach ( $parent_category->children as $category ) {
      $content .= '<li id="category-' . $category->category_id . '" class="parent_' . str_replace ( ' ', '-',  strtolower ( $category->parent_id ) ) . ' level_' . $category->level . '" data-category-id="' . $category->category_id . '" data-category-name="' . $category->name . '">' . $category->name . '</li>';
      if ( is_array ( $category->children ) && ( count ( $category->children ) > 0 )  ) {
        $content .= $this->getCategoryList ($category);
      }
    }
    $content .= '</ul>';
    return $content;
  }
  
  public function getCategories () {
    $sessionId = $this->authenticate();
    $proxy = $this->getProxy();
    $root_catalog = $proxy->catalogCategoryTree ( $sessionId );
    $content = '<label class="error">No categories defined yet</label>';
    if ( is_array ( $root_catalog->children ) && ( count ( $root_catalog->children ) > 0 )  ) {
      $content = $this->getCategoryList ( $root_catalog );
    }
    return $content;
  }
  
  public function getCategory ( $cat_id ) {
    $sessionId = $this->authenticate ( TRUE );
    $proxy = $this->getProxy();
    $content = '<label class="error">No such category</label>';
    if ( !array_key_exists( $cat_id, SWMagentoConnector::$categories ) ) {
      SWMagentoConnector::$categories[$cat_id] = $proxy->catalogCategoryInfo ( $sessionId, $cat_id );
    }
    if ( is_object ( SWMagentoConnector::$categories[$cat_id] ) ) {
      $content = '<span id="category_' . SWMagentoConnector::$categories[$cat_id]->category_id . '" class="single category">' . SWMagentoConnector::$categories[$cat_id]->name . '</span>';
    }
    return $content;
  }
  
  private function getCategoryName ( $ids ) {
    if ( is_array ( $ids ) ) {
      $content = "";
      foreach ( $ids as $cat_id ) {
        $content .= $this->getCategory ( $cat_id );
      }
    }
    return $content;
  }
  
  public function getProducts () {
    $sessionId = $this->authenticate();
    $proxy = $this->getProxy();
    $products = $proxy->catalogProductList ( $sessionId );
    $content = '<label class="error">No products found on this store.</label>';
    if ( count ( $products ) > 0 ) {
      $content = '<ul id="product-list">';
      foreach ( $products as $product ) {
        $content .= '
        <li class="item">
          <h3 class="product-name">' . $product->name . '</h3><p><div class="product-sku">' . $product->sku . '</div>
          <div class="product-set">' . $product->set . '</div>
          <div class="product-type">' . $product->type . '</div>
          <a data-product-id="' . $product->product_id . '" href="/product?id=' . $product->product_id . '">'.__ ( 'View more', 'sw' ).'</a></p><small>' . $this->getCategoryName ( $product->category_ids ) . '</small>
        </li>';
      }
      $content .= '</ul>';
    }
    return $content;
  }
  
  public function getProduct ( $p_id = NULL ) {
    if ( $p_id === NULL ) return FALSE;
    
    $sessionId = $this->authenticate();
    $proxy = $this->getProxy();
    $product = $proxy->catalogProductInfo ( $sessionId, $p_id );
    $content = '<label class="error">No product found for ' . $p_id . '.</label>';
    if ( is_object ( $product ) ) {
      do_action( 'magento_before_product', $product );
      $content = '<section id="p_' . $product->product_id . '" class="product">';
      do_action( 'magento_before_product_name', $product->name );
      $content .= '<h2>' . $product->name . '</h2>';
      do_action( 'magento_before_product_price', $product->price );
      $content .= '<strong>' . $product->price . '</strong>';
      do_action( 'magento_before_product_excerpt', $product->short_description );
      $content .= '<p class="excerpt">' . $product->short_description . '</p>';
      do_action( 'magento_before_product_desc', $product->description );
      $content .= '<p>' . $product->description . '</p>';
      do_action( 'magento_before_product_weight', $product->weight );
      $content .= '<div class="weight">' . $product->weight . '</div>';
      do_action( 'magento_before_product_sku', $product->sku );
      $content .= '<div class="sku">' . $product->sku . '</div>';
      do_action( 'magento_before_product_set', $product->set );
      $content .= '<div class="set">' . $product->set . '</div>';
      do_action( 'magento_before_product_type', $product->type );
      $content .= '<div class="type">' . $product->type . '</div>';
      do_action( 'magento_before_product_categories', $product->categories );
      $content .= '<div class="categories">' . $this->getCategoryName ( $product->categories ) . '</div>';
      do_action( 'magento_before_product_created_at', $product->created_at );
      $content .= '<div class="created-at">' . $product->created_at . '</div>';
      do_action( 'magento_before_product_updated_at', $product->updated_at );
      $content .= '<div class="updated-at">' . $product->updated_at . '</div>';
      do_action( 'magento_before_product_status', $product->status );
      $content .= '<div class="status">' . $product->status . '</div>';
      $content .= '</section>';
      do_action( 'magento_after_product', $product );
    }
    return $content;
  }
  
  public static function getInstance () {
    if ( self::$instance === NULL )
      self::$instance = new SWMagentoConnector ();
    return self::$instance;
  }
  public static function getSettings () {
    $settings = is_array ( get_site_option ( 'mconnector_settings' ) ) ? get_site_option ( 'mconnector_settings' ) : array();
    return $settings;
  }
}

SWMagentoConnector::getInstance ();