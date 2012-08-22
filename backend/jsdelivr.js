jQuery(document).ready(function($)
{
  var _self = this;

  this.scan = function()
  {      
    _self.show_wait();
    $.post(jsdelivr_data.action_admin, {'jsd_action': 'scan'}, function(r)
    {
      if ((typeof r.status !== 'undefined') && r.status == 1)
      {
        location.href = jsdelivr_data.default_url
      }
      else
      {
        _self.hide_wait();
        alert(jsdelivr_data.text.error_scan);
      }              
    });    
  }    

  this.update_cdn = function()
  {
    _self.show_wait();
    $.post(jsdelivr_data.action_admin, {'jsd_action': 'update_cdn'}, function(r)
    {
      if ((typeof r.status !== 'undefined') && r.status == 1)
      {
        location.href = jsdelivr_data.default_url
      }
      else
      {
        _self.hide_wait();
        alert(jsdelivr_data.text.error_update_cdn);
      }              
    });            
  }
  
  this.show_wait = function()
  {
    $.blockUI(
    {
      message: '<div class="jsd_wait_message"><div class="jsd_wait_loader"></div><div class="jsd_wait_text">'+jsdelivr_data.text.please_wait+'</div><div class="jsd_clear"></div></div>',
      css:
      { 
        border: 'none', 
        padding: '15px', 
        backgroundColor: '#000', 
        '-webkit-border-radius': '10px', 
        '-moz-border-radius': '10px', 
        opacity: .8, 
        color: '#fff' 
      }
    });           
  }
  
  this.hide_wait = function()
  {
    $.unblockUI();        
  }
  
  // bind controls  
  $('.jsd_scan').bind('click', function()
  {
    if ($(this).attr('disabled')) return false;
    _self.scan();    
  });

  $('.jsd_update_cdn').bind('click', function()
  {
    _self.update_cdn();    
  });
      
  $('.jsd_checkbox_select_all').bind('click', function()
  {
    var check = $(this).attr('checked')?true:false;
    $('.jsd_checkbox_select').attr('checked', check);      
    $('.jsd_checkbox_select_all').attr('checked', check);          
  });
  
  $('.jsd_bulk_action').bind('click', function()
  {
    if ($(this).attr('disabled')) return false;

    var p = $(this).parent();
    var script_load = p.find('select[name=js_load_bulk]').val();
    var move_footer = p.find('select[name=move_footer_bulk]').val();
    var status = p.find('select[name=status_bulk]').val(); 
    
    if (script_load != -1)
    {
      $('.jsd_checkbox_select:checked').parent().parent().find('.jsd_script_load').val(script_load);
    }
    
    if (move_footer != -1)
    {
      $('.jsd_checkbox_select:checked').parent().parent().find('.jsd_move_footer').attr('checked', move_footer > 0);
    }
    
    if (status != -1)
    {
      $('.jsd_checkbox_select:checked').parent().parent().find('.jsd_checkbox_enabled').attr('checked', status > 0);
    }
  });
    
  if (jsdelivr_data.cdn_update)
  {
    window.setTimeout(function()
    {
      _self.update_cdn();    
    }, 500);    
  }        
});