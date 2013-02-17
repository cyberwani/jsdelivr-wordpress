<?php
$plugin_page = (isset($_GET['page'])?$_GET['page'] == 'jsdelivr':false);
?>
<div class="updated jsd_updated_notice">
  <p>
    <?php if (!$plugin_page) { ?><b><?php _e('jsDelivr CDN:', self::ld); ?></b><?php } ?>
    <?php _e('New update of the CDN database available, please', self::ld); ?>
    <a class="button" style="margin-left: 5px; margin-right: 5px;" href="<?php echo admin_url('options-general.php?page=jsdelivr&cdn_update=1'); ?>" title="<?php _e('Update CDN data', self::ld); ?>"><?php _e('update CDN data', self::ld); ?></a>
    .
    <?php
    if (!$plugin_page)
    {
    ?>
      <a class="button jsd_dismiss_update_notice" style="margin-left: 10px;" href="#" onclick="return false;" title="<?php _e('Dismiss', self::ld); ?>"><?php _e('Dismiss', self::ld); ?></a>
      <script>
      jQuery(document).ready(function($)
      {
        $('.jsd_dismiss_update_notice').bind('click', function()
        {
          $.post('<?php echo admin_url('admin-ajax.php?action=jsdelivr_action'); ?>', {'jsd_action': 'dismiss_update_notice'}, function(r)
          {          
            $('.jsd_updated_notice').hide(400);
          });        
        });
      });  
      </script>
    <?php
    }
    ?>
  </p>
</div>    
