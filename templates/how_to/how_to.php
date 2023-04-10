<?php

function warning($message, $extended = '') {
    if ($extended == '') {
        ?>
        <div class="tw-px-4 tw-py-2 tw-bg-orange-300 tw-text-orange-800 tw-flex tw-items-center tw-mb-3">
            <p class="tw-m-0 tw-font-bold tw-mr-4 tw-text-3xl tw-bg-orange-600 tw-aspect-square tw-px-4 tw-text-orange-200">!</p>
            <p class="tw-p-0 tw-m-0"><?php echo esc_html($message); ?></p>
        </div>
        <?php
    } else {
        ?>
        <details class="tw-px-4 tw-py-2 tw-bg-orange-300 tw-text-orange-800 tw-mb-3" title="Click me to get more Info.">
            <summary class="tw-flex tw-items-center">
                <p class="tw-m-0 tw-font-bold tw-mr-4 tw-text-3xl tw-bg-orange-600 tw-aspect-square tw-px-4 tw-text-orange-200">!</p>
                <p class="tw-p-0 tw-m-0"><?php echo esc_html($message); ?></p>
            </summary>
            <p><?php echo esc_html($extended) ?></p>
        </details>
        <?php
    }
}

function info($message) {
    ?>
    <div class="tw-px-4 tw-py-2 tw-flex tw-items-center tw-mb-3">
        <p class="tw-m-0 tw-font-bold tw-mr-2 tw-text-4xl tw-aspect-square tw-p-1 tw-px-2 tw-flex tw-items-center">💡</p>
        <p class="tw-p-0 tw-m-0"><?php echo esc_html($message); ?></p>
    </div>
    <?php
}

function image($image, $caption) {
    ?>
        <div class="tw-mt-4 tw-flex tw-flex-col tw-items-center">
            <img src="<?php echo esc_url($image) ?>" alt="<?php echo esc_html($caption) ?>" class="tw-w-full tw-shadow-md">
            <p class="tw-max-w-xl tw-inline-block"><i><?php echo esc_html($caption) ?></i></p>
        </div>
    <?php
}

function insertBreak() {
    echo '<br><br><br>';
}

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

<div class="mt-8 tw-leading-relaxed tw-flex tw-justify-center">
    <div class="tw-max-w-2xl" id="MGD_how_to">
        <style>
            #MGD_how_to, #MGD_how_to :is(p, li) {
                font-size: 1.2em;
            }
        </style>

        <h1>How to Use Gitdown</h1>

        <p>First off, Thank you for choosing Gitdown to power your website!</p>

        <p>Let's quickly go over how to use this plugin.</p>

        <p>I split this tutorial into two sections: The Setup and Managing Article. The Setup is a little bit harder than the managing, so let's get right into it.</p>


        <h2>Setup</h2>

        <p>You want to publish your markdown articles from a remote repository like GitHub to your WordPress blog, so you will have to first define the location of your Remote repository in the settings. The Gitdown settings are located at <i><a href="<?php echo esc_url(home_url('/wp-admin/options-reading.php')) ?>" target="_blank">Reading</a></i>.</p>


        <?php image(MGD_ROOT_URL.'templates/how_to/github-copy-repo-url.png', 'Where to find your github repository link.'); ?>


        <?php info('By default, there is a repository there that I have created so you can test out and understand how Gitdown works without having a repo ready yourself.'); ?>


        <?php warning('Gitdown wont be able to clone and fetch private repositories as of now.') ?>

        <p>Now that you have a repository set Gitdown will clone and fetch this repository to your <code><a href="<?php echo MGD_MIRROR_URL ?>" target="_blank">wp-content ↗</a></code>folder.</p>

        <?php insertBreak() ?>

        <p class="mt-8">Next, we need to tell Gitdown where to find the files representing the Articles within your repository. We do this with a <a href="https://www.php.net/manual/en/function.glob.php">glob pattern</a>.</p>

        <p>Glob Patterns are like Regular Expressions, but for File Systems, for example <code>*.md</code> means match all files in the current folder that end with <code>.md</code> or <code>**/article.txt</code> means match all files in any direct subfolder of the current directory ending that are called <code>article.txt</code></p>

        <p>Below you see <a href="https://globster.xyz" target="_blank">globster.xyz</a>, a fantastic little website that can help you figure out and understand glob patterns. Edit the pattern at the top to see which files light up.</p>

        <iframe style="width: 100%" height="450" src="<?php echo esc_url($globsterURL) ?>" frameborder="0" sandbox="allow-scripts"></iframe>

        <p>Now that you know how glob patterns work, you can use them in the Gitdown settings.</p>

        <?php info('You can specifiy multiple glob pattern by seperating them with a comma like this: *.md,*.txt'); ?>

        <?php insertBreak() ?>

        <p>We got the hard part out of the way lets also quickly look at the resolver functions.</p>

        <p>In the background every article that was found will be put through a function called a resolver and it will try to find out the meta information of the article.</p>

        <p>At the moment there are two resolver functions</p>

        <ul>
            <li>
                <b>Simple</b>: Will look for YAML Style front matter at the top of the markdown file and it will parse the, title, slug, description, category, status and obviously its content. It will also search for a file called <code>preview.png</code> and add that as the thumbnail.
            </li>
            <li>
                <b>Directory to Category</b>: This Resolver has only one difference to the simple on and that is that it will change the category from the article to the directory path of its file within the repo and it will set the file name as the title.
            </li>
        </ul>


        <p>You will most likely be fine with the simple resolver.</p>

        <p>Below you see an example of valid file.</p>

        <pre class="tw-p-3 tw-font-semibold tw-bg-neutral-300">---
<span class="tw-text-blue-500">name</span><span class="tw-text-neutral-500">:</span> <span class="tw-text-neutral-500">'</span>My Cool Post<span class="tw-text-neutral-500">'</span>
<span class="tw-text-blue-500">slug</span><span class="tw-text-neutral-500">:</span> <span class="tw-text-neutral-500">'</span>my-cool-post<span class="tw-text-neutral-500">'</span>
<span class="tw-text-blue-500">description</span><span class="tw-text-neutral-500">:</span> <span class="tw-text-neutral-500">'</span>This is my first and cool post ...<span class="tw-text-neutral-500">'</span>
<span class="tw-text-blue-500">category</span><span class="tw-text-neutral-500">:</span> [<span class="tw-text-neutral-500">'</span>Posts/Cool<span class="tw-text-neutral-500">'</span>, <span class="tw-text-neutral-500">'</span>Memories<span class="tw-text-neutral-500">'</span>]
<span class="tw-text-blue-500">status</span><span class="tw-text-neutral-500">:</span> <span class="tw-text-neutral-500">'</span>draft<span class="tw-text-neutral-500">'</span>
---
Article content in the normal markdown format.

...</pre>

        <p>Now that you have setup everything you can go to the main admin page of the plugin, where you see all your found posts.</p>

        <?php warning('Articles are connected via their slug/post_name which means Gitdown will think an article that was on your Blog before that matches another article in your repo are the same and it will overwrite it so It is advised to make a backup of your articles. This also means that if you change the slug of any article it will no longer match its counterpart on git or wordpress.') ?>
        <?php info('Gitdown will create the categories that were specified in your frontmatter and it even allows nested categories like this: Vehicles/Cars/Ford'); ?>

        <?php insertBreak() ?>

        <p class="mt-8">To Recap, setting up consists of ...</p>
        <ol>
            <li>Specifiying the repository.</li>
            <li>Configurating a glob pattern.</li>
            <li>Setting a resolver function.</li>
        </ol>


        <?php insertBreak() ?>


        <h2>Managing Articles</h2>

        <?php image(MGD_ROOT_URL.'templates/how_to/managing.png', 'Gitdown\'s User Interface'); ?>

        <p>Gitdown's User Interface is pretty straight forward, it consist of some buttons at the top to update / publish / delete all posts. These buttons are also individually available for each article.</p>

        <p>The second column will show you wether the post is on wordpress, which as stated earlier means that gitdown found a post with the same slug.</p>

        <h3>Publishing</h3>

        <p>Now for some tips when publishing articles.</p>

        <p>You can specify custom frontmatter keys that will be added as post meta for the given article. You can use this for themes.</p>

        <p>By specifiying <code>post_type: page</code> you can add markdown articles as Pages and not Posts.</p>

        <p>Thats it have fun writing and publishing with Gitdown! 😀</p>

        <?php warning('You should never edit posts on wordpress that originate from a repository since Gitdown will overwrite your changes the next time you click on update.', 'Gitdown Actually prohibits you from quick editing synced posts and it shows a warning message in the Block Editor. So you should always edit posts in the repository and then update the article via the ui or enable auto updates.'); ?>

    </div>
</div>