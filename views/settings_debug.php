<input type="checkbox" name="<?php echo GTW_SETTING_DEBUG?>" <?php echo (get_option(GTW_SETTING_DEBUG) == '1') ? 'checked' : '' ?> value="1" id="<?php echo GTW_SETTING_DEBUG?>">
<label class="description" for="<?php echo GTW_SETTING_DEBUG?>">Enable debugging ...</label>

<!-- <?php var_dump(GTW_SETTING_DEBUG) ?>
<br>
<br>
<?php var_dump(get_option(GTW_SETTING_DEBUG)) ?>
<?php var_dump(boolval(get_option(GTW_SETTING_DEBUG))) ?> -->