<?php
  global $blog_id;
  $page_icon = $this->plugin_url . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . $page . '.png';
  $settings = SWMagentoConnector::getSettings ();
?>
<div class="wrap">
  <div class="icon32">
    <img src="<?php echo $page_icon; ?>" />
  </div>
  <h2>Magento Connector</h2>
  <form method="post" action="">
    <p><?php echo apply_filters ( 'mconnector_about_helper' ,__('This plugin wraps the Magento API available since Magento 1.3.', 'sw')); ?> </p>
  </form>
  <div class="usage">
    <?php if ( isset ( $settings ['api_username'] ) && isset ( $settings ['api_key'] ) && isset ( $settings ['magento_host'] ) ): ?>
      <div class="metabox-holder api-settings">
        <div class="postbox">
          <h3>General Usage</h3>
          <div class="inside">
            <table style="wide">
              <thead>
                <tr>
                  <th width="50%">Short Code</th>
                  <th width="*">Description</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>[magento_categories]</td>
                  <td>Returns a hierarchical tree of categories.</td>
                </tr>
                <tr>
                  <td>[magento_products]</td>
                  <td>Returns the list of products.</td>
                </tr>
                <tr>
                  <td>[magento_product product_id=29]</td>
                  <td>Returns the product information.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    <?php else: ?>
      <p><?php echo apply_filters ( 'mconnector_about_helper' ,__('To start grab your API credentails and configure them', 'sw')); ?> <a href="<?php echo get_admin_url ($blog_id); ?>/admin.php?page=magento-api">here</a></p>
    <?php endif; ?>
  </div>
  <h4>
</div>