<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WP Plugin</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen">


    <?php

    use Mni\FrontYAML\Parser;

    $postData = [
        [
            'name' => 'How to make a chrome Extension',
            'github_link' => 'https://github.com/Maximinodotpy/articles',
            'slug' => 'how-to-make-a-chrome-extension',
            'featured_image' => 'https://image.jpg',
            'tags' => ['programming', 'html'],
            'category' => ['hello']

        ],
        [
            'name' => 'How to make a chrome Extension',
            'github_link' => 'https://github.com/Maximinodotpy/articles',
            'slug' => 'how-to-make-a-chrome-extension',
            'featured_image' => 'https://image.jpg',
            'tags' => ['programming', 'html'],
            'category' => ['hello']

        ],
        [
            'name' => 'How to make a chrome Extension',
            'github_link' => 'https://github.com/Maximinodotpy/articles',
            'slug' => 'how-to-make-a-chrome-extension',
            'featured_image' => 'https://image.jpg',
            'tags' => ['programming', 'html'],
            'category' => ['hello']

        ],
        [
            'name' => 'How to make a chrome Extension',
            'github_link' => 'https://github.com/Maximinodotpy/articles',
            'slug' => 'how-to-make-a-chrome-extension',
            'featured_image' => 'https://image.jpg',
            'tags' => ['programming', 'html'],
            'category' => ['hello']

        ],
        [
            'name' => 'How to make a chrome Extension',
            'github_link' => 'https://github.com/Maximinodotpy/articles',
            'slug' => 'how-to-make-a-chrome-extension',
            'featured_image' => 'https://image.jpg',
            'tags' => ['programming', 'html'],
            'category' => ['hello']

        ],
        [
            'name' => 'How to make a chrome Extension',
            'github_link' => 'https://github.com/Maximinodotpy/articles',
            'slug' => 'how-to-make-a-chrome-extension',
            'featured_image' => 'https://image.jpg',
            'tags' => ['programming', 'html'],
            'category' => ['hello']

        ],
        [
            'name' => 'How to make a chrome Extension',
            'github_link' => 'https://github.com/Maximinodotpy/articles',
            'slug' => 'how-to-make-a-chrome-extension',
            'featured_image' => 'https://image.jpg',
            'tags' => ['programming', 'html'],
            'category' => ['hello']

        ],
    ]


    ?>


    <div class="wrap bg-neutral-800 text-neutral-300 h-full p-4">

        <div class="max-w-screen-md mx-auto">

            <h1 class="text-2xl">GitHub to WordPress</h1>

            <?php

            include 'config.php';

            echo 'maxim ist cool';

            /* Cloning the Repository */
            /* $repo = 'https://github.com/Maximinodotpy/articles.git';
    shell_exec('git clone '.$repo.' '.MIRROR_PATH); */

            $globPath = MIRROR_PATH . '**/_blog/article.md';

            echo '<pre>';
            echo $globPath;
            echo '<br>';

            print_r(glob($globPath));
            echo '</pre>';

            include 'vendor\autoload.php';

            $str = '---
foo: bar
slug: gell-fdsa-fasd-fdad
spassti: [0, 0, 0]
---
This is **strong**.';

            $parser = new Mni\FrontYAML\Parser;

            $document = $parser->parse($str);

            $yaml = $document->getYAML();
            $html = $document->getContent();

            echo '<pre>';
            print_r($yaml);
            print_r($html);
            echo '</pre>';

            ?>

            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Laborum hic distinctio illo dolores earum ex delectus officia facere nesciunt alias, repudiandae optio suscipit a harum quae, molestiae repellat deleniti esse.</p>

            <div class="flex mt-4">
                <a href="#" class="bg-neutral-700 p-2">Post Manager</a>
                <a href="#" class="p-2">Resolve Settings</a>
            </div>

            <div class="bg-neutral-700">
                <div>

                    <table class="w-full">
                        <thead>
                            <tr class="sticky top-0 bg-neutral-700 shadow-xl z-20">
                                <th class="text-left p-2">GitHub</th>
                                <th class="text-left">Wordpress</th>
                                <th class="text-left" colspan="3">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">

                            <?php

                            foreach ($postData as $key => $val) {

                                echo '
                            
        
                                <tr class="odd:bg-neutral-700 border-neutral-500">
                                    <td class="px-2 py-4">
                                        <div class="text-xl mb-2">' . $val['name'] . '</div>
                                        <div class="mb-2">Lorem ipsum dolor sit amet, consectetur adipisicing elit ...</div>
                                        <div class="text-neutral-400">
                                            <a href="#" class="mr-4">GitHub</a>
    
                                            <details class="inline-block relative">
                                            
                                                <summary class="hover:cursor-pointer">Raw Data</summary>
        
                                                <div class="absolute top-full bg-neutral-500 shadow-lg z-10 p-2 text-neutral-300 max-w-96">
                                                    <pre>';
                                print_r($val);

                                echo '</pre>
                                                </div>
    
        
                                            </details>
                                        </div>
    
                                    </td>
                                    <td class="italic">Not Posted</td>
        
                                    <td><button class="h-full">Post</button></td>
                                    <td><button class="h-full">Update</button></td>
                                    <td><button class="h-full">Delete</button></td>
                                </tr>
                            
                            ';
                            }

                            ?>


                        </tbody>
                    </table>

                </div>
            </div>
        </div>

    </div>


</body>

</html>