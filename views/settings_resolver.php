<fieldset>

    <label for="resolver_simple">
        <input type="radio" name="<?php echo esc_html(MGD_SETTING_RESOLVER) ?>"  id="resolver_simple" value="simple" <?php echo esc_html(get_option(MGD_SETTING_RESOLVER)) == 'simple' ? 'checked' : '' ?>>
        <span>Simple</span>
        <p class="description">The simple resolver will look for meta data in the article and fill them out this way. If there is no slug specified it will turn the title into a valid slug and use that.</p>
    </label>
    
    <label for="resolver_directory_category">
        <input type="radio" name="<?php echo esc_html(MGD_SETTING_RESOLVER ) ?>" 
            id="resolver_directory_category" value="dir_cat" <?php echo esc_html( get_option(MGD_SETTING_RESOLVER )) == 'dir_cat' ? 'checked' : '' ?>>
        <span>Directory to Category</span>
        <p class="description">
            This function is based on the simple resolver but it will give the article a category derived from its directory location so something like <code>cats/why cats are awesome.md</code> will add the category cats to the article.
        </p>
    </label>
    <br>

    <!-- <label for="resolver_custom">
        <input type="radio" name="<?php echo esc_html( MGD_SETTING_RESOLVER ) ?>"  id="resolver_custom" value="custom" <?php echo esc_html( get_option(MGD_SETTING_RESOLVER )) == 'simple' ? 'checked' : '' ?>>
        <span>Custom</span>
        <p class="description">This custom resolver function should return an associative array with the following members: </p>
        <br>

        <textarea name="" id="" cols="30" rows="10" style="width: 100%; font-family: monospace" class="tw-text-bold">function($path) {
    // Return The Correct Object
}</textarea>
    </label>
    <br> -->
</fieldset>