<?php
$disabled_all = '';
$disabled_scan = '';
if (!$last_cdn_update) // if CDN was not updated, then disable all controls 
{
  $disabled_all = ' disabled';
  $disabled_scan = ' disabled';  
}
else
if (!$last_scan)
{
  $disabled_all = ' disabled';
}

$is_empty = !count($files);

$disabled_bulk = $is_empty?' disabled':'';
?>

<?php
if (!$last_cdn_update)
{
?>
<div class="updated jsd_updated_message">
  <p>
  <?php _e('First time, you must', $this->ld); ?>
  <a class="button jsd_update_cdn" href="#" onclick="return false;" title="<?php _e('Update CDN data', $this->ld); ?>"><?php _e('update CDN data', $this->ld); ?></a>.
  <?php _e('Then you will be able scan your website and activate CDN service for appropriate files.', $this->ld); ?>
  </p>
</div>
<?php
}
else
if (!$last_scan)
{
?>
<div class="updated jsd_scan_message">
  <p>
    <?php _e('Now, you can', $this->ld); ?>
    <a class="button jsd_scan" href="#"<?php echo $disabled_scan; ?> onclick="return false;" title="<?php _e('Scan website', $this->ld); ?>"><?php _e('scan your website', $this->ld); ?></a>    
    .
  </p>
</div>
<?php
}
?>


<form name="save_form" method="POST" action="<?php echo $action_url; ?>">
<?php wp_nonce_field('jsdelivr_nonce'); ?>

<p>
  <div class="jsd_control_left">
    <select name="jsd_enabled"<?php echo $disabled_all; ?>>
      <option value="0"<?php echo (!$this->enabled?' selected':''); ?>><?php _e('Disabled', $this->ld); ?></option>
      <option value="1"<?php echo ($this->enabled?' selected':''); ?>><?php _e('Enabled', $this->ld); ?></option>
    </select>
    <input class="button-primary" type="submit"<?php echo $disabled_all; ?> name="save_options" title="<?php _e('Save all settings', $this->ld); ?>" value="<?php _e('Save all settings', $this->ld); ?>" id="submitbutton" />
    <div class="jsd_clear"></div>
  </div>
  <div class="jsd_status_text<?php echo ($this->enabled?' jsd_status_text_enabled':' jsd_status_text_disabled'); ?>">
    <?php $this->enabled?_e('Plugin is enabled'):_e('Plugin is disabled'); ?>
  </div>
  <div class="jsd_control_right">
    <a class="button jsd_scan" href="#"<?php echo $disabled_scan; ?> onclick="return false;" title="<?php _e('Scan website', $this->ld); ?>"><?php _e('Scan website', $this->ld); ?></a>    
    <div class="jsd_clear"></div>
  </div>
  <div class="jsd_clear"></div>          
</p>

<div class="jsd_bulk_actions">
  <b><?php _e('Bulk actions', $this->ld); ?></b>
  <label for="js_load_bulk_1"><?php _e('Script load', $this->ld); ?></label>
  <select name="js_load_bulk"<?php echo $disabled_bulk; ?> id="js_load_bulk_1">
    <option value="-1"><?php _e('Please select', $this->ld); ?></option>
    <option value="0"><?php _e('Regular', $this->ld); ?></option>
    <option value="1"><?php _e('Async', $this->ld); ?></option>
    <option value="2"><?php _e('Defer', $this->ld); ?></option>
  </select>  
  <label for="move_footer_bulk_1"><?php _e('Move to the footer', $this->ld); ?></label>
  <select name="move_footer_bulk"<?php echo $disabled_bulk; ?> id="move_footer_bulk_1">
    <option value="-1"><?php _e('Please select', $this->ld); ?></option>
    <option value="0"><?php _e('No', $this->ld); ?></option>
    <option value="1"><?php _e('Yes', $this->ld); ?></option>
  </select>                    
  <label for="status_bulk_1"><?php _e('Status', $this->ld); ?></label>
  <select name="status_bulk"<?php echo $disabled_bulk; ?> id="status_bulk_1">
    <option value="-1"><?php _e('Please select', $this->ld); ?></option>
    <option value="0"><?php _e('Disabled', $this->ld); ?></option>
    <option value="1"><?php _e('Enabled', $this->ld); ?></option>
  </select>                    
  <a class="button jsd_bulk_action"<?php echo $disabled_bulk; ?> href="#" onclick="return false;" title="<?php _e('Apply', $this->ld); ?>"><?php _e('Apply', $this->ld); ?></a>
</div>

<table class="widefat">
<thead>
    <tr>
        <th class="jsd_align_center"><input type="checkbox" name="jsd_checkbox_select_all" class="jsd_checkbox_select_all" value="1" /></th>
        <th><?php _e('File', $this->ld); ?></th>
        <th><?php _e('Package', $this->ld); ?></th>
        <th><?php _e('Status', $this->ld); ?></th>
        <th><?php _e('Match', $this->ld); ?></th>
    </tr>
</thead>
<tfoot>
    <tr>
        <th class="jsd_align_center"><input type="checkbox" name="jsd_checkbox_select_all" class="jsd_checkbox_select_all" value="1" /></th>
        <th><?php _e('File', $this->ld); ?></th>
        <th><?php _e('Package', $this->ld); ?></th>
        <th><?php _e('Status', $this->ld); ?></th>
        <th><?php _e('Match', $this->ld); ?></th>
    </tr>
</tfoot>
<tbody>
  <?php
  if ($is_empty)
  {
  ?>
  <tr><td colspan="5"><?php _e('File list is empty.', $this->ld); ?></td></tr>  
  <?php
  }
  
  $c = 0;
  $debug_info = '';  
  while(list(, $file) = @each($files))
  {
    $c++;
    
    // additional debug info    
    if ($this->debug)
    {
      $debug_info = $file['file_hash']."\r\r".$file['file_url']."\r\r".$file['file_html'];
    }
  ?>      
  <tr <?php echo $c%2?' class="alternate"':''; ?>>
    <td align="center" class="jsd_valign_middle">
      <input class="jsd_checkbox_select" type="checkbox" name="jsd_checkbox_select[<?php echo $file['file_id']; ?>]" id="jsd_checkbox_select<?php echo $file['file_id']; ?>" value="1" />
    </td>
    <td>
      <b><?php echo $file['file_full_filename']; ?></b><br />
      <b><?php _e('Version:', $this->ld); ?> </b><?php echo $file['file_version']?$file['file_version']:__('None', $this->ld); ?>
      
      <?php
      if ($file['file_type'] == JSDT_JAVASCRIPT && $file['file_match'] != JSDM_NONE)
      {
      ?>
      <div class="jsd_options_box">
        <label for="js_load_<?php echo $file['file_id']; ?>"><?php _e('Script load', $this->ld); ?></label>
        <select class="jsd_script_load" name="js_load[<?php echo $file['file_id']; ?>]" id="js_load_<?php echo $file['file_id']; ?>">
          <option value="0"><?php _e('Regular', $this->ld); ?></option>
          <option value="1"<?php echo ($file['file_async']?' selected':''); ?>><?php _e('Async', $this->ld); ?></option>
          <option value="2"<?php echo ($file['file_defer']?' selected':''); ?>><?php _e('Defer', $this->ld); ?></option>
        </select>                
        <input class="jsd_move_footer" type="checkbox"<?php echo ($file['file_footer']?' checked':''); ?> name="move_footer[<?php echo $file['file_id']; ?>]" id="move_footer_<?php echo $file['file_id']; ?>" value="1" />
        <label for="move_footer_<?php echo $file['file_id']; ?>"><?php _e('Move to the footer', $this->ld); ?></label>
        <label for="priority_<?php echo $file['file_id']; ?>"><?php _e('with priority', $this->ld); ?></label>
        <input type="number" class="jsd_input_number" name="priority[<?php echo $file['file_id']; ?>]" value="<?php echo $file['file_priority']; ?>" id="priority_<?php echo $file['file_id']; ?>" />
      </div>
      <?php      
      }
      ?>      
    </td>
    <td title="<?php echo $this->strip($file['cdn_description']); ?>">
      <?php
      if ($file['file_match'] != JSDM_NONE)
      {
        if ($file['file_gcdn'] && isset($this->gcdn[$file['file_gcdn']]))
        {
          $gcdn = $this->gcdn[$file['file_gcdn']];          
      ?>
          <b><?php _e('Name:', $this->ld); ?> </b><?php echo $gcdn['name']; ?><br />
          <b><?php _e('Version:', $this->ld); ?> </b><?php echo in_array($file['file_version'], $gcdn['versions'])?$file['file_version']:$gcdn['versions'][0]; ?><br />
          <br /><?php _e('Google Hosted Libraries', $this->ld); ?>          
      <?php
        }
        else
        {
      ?>
          <b><?php _e('Name:', $this->ld); ?> </b><?php echo $file['cdn_name']; ?><br />
          <b><?php _e('Version:', $this->ld); ?> </b><?php echo $file['cdn_version']; ?><br />
          <b><?php _e('Author:', $this->ld); ?> </b> <?php echo $file['cdn_author']; ?><br />
          <a href="<?php echo $this->strip($file['cdn_homepage']); ?>" target="_blank"><?php echo $file['cdn_homepage']; ?></a>
      <?php
        }
      }
      ?> 
    </td>
    <td class="jsd_valign_middle">
      <?php
      if ($file['file_match'] != JSDM_NONE)
      {
      ?>
        <div class="jsd_label_enabler<?php echo ($file['file_enabled']?' jsd_label_enabled':' jsd_label_disabled'); ?>">         
          <input class="jsd_checkbox_enabled" type="checkbox"<?php echo ($file['file_enabled'] == 1?' checked':''); ?> name="enabled[<?php echo $file['file_id']; ?>]" id="enabled_<?php echo $file['file_id']; ?>" value="1" />
          <label for="enabled_<?php echo $file['file_id']; ?>">          
            <?php $file['file_enabled']?_e('Enabled', $this->ld):_e('Disabled', $this->ld); ?>
          </label>
        </div>
      <?php
      }
      ?>
    </td>
    <td class="jsd_valign_middle" align="center" title="<?php echo $this->strip($debug_info); ?>">
      <input type="hidden" name="ids[]" value="<?php echo $file['file_id']; ?>" />
      <?php
      switch($file['file_match'])
      {
        case JSDM_FULL:
          $image_url = $this->url.'/backend/images/jsdm_full.png';
          $title = __('100% Match', $this->ld);
          break;
        case JSDM_MAYBE:
          $image_url = $this->url.'/backend/images/jsdm_maybe.png';
          $title = __('Same plugin, but maybe different versions', $this->ld);
          break;
        default:
          $image_url = $this->url.'/backend/images/jsdm_none.png';                  
          $title = __('Not matching', $this->ld);
      }
      ?>
      <img src="<?php echo $image_url; ?>" alt="<?php echo $title; ?>" title="<?php echo $title; ?>" />
    </td>
  </tr>
  <?php
  }
  ?>
</tbody>
</table>
<div class="jsd_bulk_actions">
  <b><?php _e('Bulk actions', $this->ld); ?></b>
  <label for="js_load_bulk_2"><?php _e('Script load', $this->ld); ?></label>
  <select name="js_load_bulk"<?php echo $disabled_bulk; ?> id="js_load_bulk_2">
    <option value="-1"><?php _e('Please select', $this->ld); ?></option>
    <option value="0"><?php _e('Regular', $this->ld); ?></option>
    <option value="1"><?php _e('Async', $this->ld); ?></option>
    <option value="2"><?php _e('Defer', $this->ld); ?></option>
  </select>  
  <label for="move_footer_bulk_2"><?php _e('Move to the footer', $this->ld); ?></label>
  <select name="move_footer_bulk"<?php echo $disabled_bulk; ?> id="move_footer_bulk_2">
    <option value="-1"><?php _e('Please select', $this->ld); ?></option>
    <option value="0"><?php _e('No', $this->ld); ?></option>
    <option value="1"><?php _e('Yes', $this->ld); ?></option>
  </select>                    
  <label for="status_bulk_2"><?php _e('Status', $this->ld); ?></label>
  <select name="status_bulk"<?php echo $disabled_bulk; ?> id="status_bulk_2">
    <option value="-1"><?php _e('Please select', $this->ld); ?></option>
    <option value="0"><?php _e('Disabled', $this->ld); ?></option>
    <option value="1"><?php _e('Enabled', $this->ld); ?></option>
  </select>                    
  <a class="button jsd_bulk_action"<?php echo $disabled_bulk; ?> href="#" onclick="return false;" title="<?php _e('Apply', $this->ld); ?>"><?php _e('Apply', $this->ld); ?></a>
</div>
<p>
  <input class="button-primary" type="submit"<?php echo $disabled_all; ?> name="save_options" title="<?php _e('Save all settings', $this->ld); ?>" value="<?php _e('Save all settings', $this->ld); ?>" id="submitbutton" />
</p>
<div class="jsd_clear"></div>
</form>

</div>