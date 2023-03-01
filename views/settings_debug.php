<input type="checkbox" name="<?php echo MGD_SETTING_DEBUG?>" <?php echo (get_option(MGD_SETTING_DEBUG) == '1') ? 'checked' : '' ?> value="1" id="<?php echo MGD_SETTING_DEBUG?>">
<label class="description" for="<?php echo MGD_SETTING_DEBUG?>">Enable debugging ...</label>

<!-- <?php var_dump(MGD_SETTING_DEBUG) ?>
<br>
<br>
<?php var_dump(get_option(MGD_SETTING_DEBUG)) ?>
<?php var_dump(boolval(get_option(MGD_SETTING_DEBUG))) ?> -->