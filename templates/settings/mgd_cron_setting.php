<input class="regular-text code" type="checkbox" id="<?php echo esc_html(MGD_SETTING_CRON)?>" name="<?php echo esc_html(MGD_SETTING_CRON)?>" <?php echo ((bool)get_option(MGD_SETTING_CRON)) ? 'checked' : '' ?>> <label for="<?php echo esc_html(MGD_SETTING_CRON) ?>">Enable Automatic Updates</label>
<p class="description">Do you want that gitdown automatically updates your posts? <span>(❗Experimental)</span></p>