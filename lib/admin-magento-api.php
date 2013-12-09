<?php
  $page_icon = $this->plugin_url . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . $page . '.png';
  $settings = is_array ( get_site_option ( 'mconnector_settings' ) ) ? get_site_option ( 'mconnector_settings' ) : array();
  $api_username = array_key_exists ( 'api_username' , $settings ) ? $settings['api_username'] : "";
  $api_key = array_key_exists ( 'api_key' , $settings ) ? $settings['api_key'] : "";
  $magento_host = array_key_exists ( 'magento_host' , $settings ) ? $settings['magento_host'] : "";
?>
<div class="wrap">
  <div class="icon32">
    <img src="<?php echo $page_icon; ?>" />
  </div>
  <h2>Magento API Settings</h2>
  <form method="post" action="">
    <p><?php echo apply_filters ( 'mconnector_about_helper' ,__('Setup your Magento API credentials.', 'sw')); ?> </p>
    <div class="metabox-holder api-settings">
      <div class="postbox">
        <h3 class="hndle">
          <span><?php _e('API Credentials', 'sw'); ?></span>
        </h3>
        <div class="inside">
          <table class="form-table">
            <tbody>
              <tr>
                <th><?php _e('Magento Host', 'sw'); ?></th>
                <td>
                  <input type="text" id="mconnector_settings[magento_host]" name="mconnector_settings[magento_host]" value="<?php echo $magento_host; ?>" placeholder="https://your.magento.host" style="width:50%;" />
                </td>
              </tr>
              <tr>
                <th><?php _e('API Username', 'sw'); ?></th>
                <td>
                  <input type="text" id="mconnector_settings[api_username]" name="mconnector_settings[api_username]" value="<?php echo $api_username; ?>" placeholder="type your api username here!" style="width:50%;" />
                </td>
              </tr>
              <tr>
                <th><?php _e('API Key', 'sw'); ?></th>
                <td>
                  <input type="text" id="mconnector_settings[api_key]" name="mconnector_settings[api_key]" value="<?php echo $api_key; ?>" placeholder="type your api key here!" style="width:50%;" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php wp_nonce_field ( 'save_api', 'mconnector_admin_settings' ) ?>
    <p class="submit alignright">
      <input type="submit" name="submit_settings" class="button-primary" value="Save Changes">
    </p>
  </form>
</div>