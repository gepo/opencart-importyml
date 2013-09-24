<?php echo $header; ?>
<div id="content">
  <div class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
    <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
    <?php } ?>
  </div>
  <?php if ($error_warning) { ?>
  <div class="warning"><?php echo $error_warning; ?></div>
  <?php } ?>
  <?php if ($success) { ?>
  <div class="success"><?php echo $success; ?></div>
  <?php } ?>
  <div class="box">
    <div class="heading">
      <h1><img src="view/image/backup.png" alt="" /> <?php echo $heading_title; ?></h1>
      <div class="buttons"><a onclick="$('#form').submit();" class="button"><span><?php echo $button_import; ?></span></a></div>
    </div>
    <div class="content">
      <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
        <table class="form">
          <tr>
            <td colspan="2"><?php echo $entry_description; ?></td>
          </tr>
          <tr>
            <td width="25%"><?php echo $entry_restore; ?></td>
            <td><input type="file" name="upload" /></td>
          </tr>
          <tr>
            <td width="25%"><?php echo $entry_url; ?></td>
            <td><input type="text" name="url" size="50" /></td>
          </tr>
          <tr>
            <td width="25%"><?php echo $entry_update; ?></td>
            <td>
              <input type="checkbox" name="update[name]" /><?php echo $entry_field_name; ?><br />
              <input type="checkbox" name="update[description]" /><?php echo $entry_field_description; ?><br />
              <input type="checkbox" name="update[price]" /><?php echo $entry_field_price; ?><br />
              <input type="checkbox" name="update[image]" /><?php echo $entry_field_image; ?><br />
              <input type="checkbox" name="update[manufacturer]" /><?php echo $entry_field_manufacturer; ?><br />
              <input type="checkbox" name="update[attributes]" /><?php echo $entry_field_attribute; ?><br />
            </td>
          </tr>
    		  <tr>
    		    <td width="25%"><?php echo $entry_force; ?></td>
    			  <td><input type="checkbox" name="force" /></td>
    		  </tr>
          <tr>
            <td width="25%"><?php echo $entry_save_settings; ?></td>
            <td>
              <a onclick="$('#form').attr('action', '<?php echo $save; ?>'); $('#form').submit();" class="button"><?php echo $button_save; ?></a>
            </td>
          </tr>
        </table>
      </form>
    </div>
  </div>
</div>
<?php echo $footer; ?>