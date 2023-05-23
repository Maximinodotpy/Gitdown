## Can I supply multiple glob patterns?

Yes, you can! Simply separate them by commas.

## Does Gitdown read nested categories from the markdown file?

Yes, it does! Something like `Animals/Cats`, will create these two classes in a nested fashion and add the last one as the category.

## Does Gitdown read tags?

Yes, It does! You can supply either a list of tags or a single tag.

## Does Gitdown support thumbnails?

Yes, It does! You simply have to provide a file called 'preview.png' in the same folder as your article and it will be added as the thumbnail. This means that you should have a folder for each article.

## Which keys and values can I provide in the Frontmatter of my Articles?

You find the description for each key [here](keys.md).

## Can I also manage Pages with Gitdown

Yes, you can! simply add `post_type: page` to your frontmatter and this file will be added as a page. You can then also define a parent page with `parent_page: <slug of the parent page>`.

## Can Gitdown Automatically update/sync my articles?

Yes it can, simply go to the Settings and activate automatic updating there. Just keep in mind that this could mean that you will create duplicate content by mistake if you change the slug of your article.

## Can I connect Private Repositories?

Yes you can you will have to create an URL with the authentication. Consult the following links to set this up.

- [Creating a URL to clone Private Github Repos](https://stackoverflow.com/questions/22755268/clone-a-private-repo-of-github-with-username-and-password)
- [Creating a custom Personal Access Token](https://docs.github.com/de/authentication/keeping-your-account-and-data-secure/creating-a-personal-access-token)