<div>
    <h2><?php _e('Resolver', 'gitdown') ?></h2>
    
    <p><?php _e('Gitdown tries to find and understand your articles in a given repository in a few different ways. This process is called "Resolving" and it entails three steps, that you should follow when editing the settings.', 'gitdown') ?></p>

    <ol>
        <li>
            <b><?php _e('Repository', 'gitdown') ?></b>
            <div><?php _e('Before you can get started you have to specifiy where your repository is located. By default there is an example repository that you can use to test and learn how this plugin works.', 'gitdown') ?></div>
        </li>
        <li>
            <b><?php _e('Glob Pattern', 'gitdown') ?></b>
            <div class="tw-mb-6"><?php _e('With the glob pattern you specifiy where in your repository the files are. For example: <code>**/*.md</code> will match any file that ends with .md. Use <a href="https://globster.xyz/">globster.xyz</a> to learn and test your glob pattern or you can try it out right down below this paragraph.', 'gitdown') ?></div>

            <br>

            <?php 

            $globsterURL = 'https://globster.xyz/?q=**%2Farticle.md&f=%2Findex.md%2C%2F00%20-%20My%20First%20Article%2Findex.html%2C%2F00%20-%20My%20First%20Article%2Farticle.md%2C%2F01%20-%20How%20to%20minify%20CSS%20with%20Python%2Fminify.py%2C%2F01%20-%20How%20to%20minify%20CSS%20with%20Python%2Farticle.md%2C%2F02%20-%20How%20to%20setup%20a%20wordpress%20theme%2Farticle.md&embed=1';
            
            `
/index.md
/00 - My First Article/index.html
/00 - My First Article/article.md
/01 - How to minify CSS with Python/minify.py
/01 - How to minify CSS with Python/article.md
/02 - How to setup a wordpress theme/article.md
`
            
            ?>

            <iframe style="width: 100%" height="400" src="<?php echo $globsterURL ?>" frameborder="0" sandbox="allow-scripts"></iframe>

        </li>
        <li>
            <b><?php _e('Resolver Function', 'gitdown') ?></b>
            <div><?php _e('As of now you dont have to worry too much about the resolver function as there are only two: Simple and Directory to Category. They will both look for Front matter info within the markdown files and fill out meta information about the post this way.', 'gitdown') ?></div>
        </li>
    </ol>
</div>