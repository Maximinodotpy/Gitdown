# Gitdown
*By [Maxim Maeder](https://maximmaeder.com/)*

Gitdown enables you to connect a Remote Repository to your Website and upload markdown articles from there.

![](https://raw.githubusercontent.com/Maximinodotpy/Gitdown/master/images/gitdown.png?token=GHSAT0AAAAAAB6Q4VS4LAW2ICXYCHUTH3JOY7GDDQQ)

## Usage

1. Install and Activate the Plugin
2. Go to *Settings* > *Reading* 
3. in the section *Gitdown Settings* specify your desired glob pattern and repository location.
4. Go to *Gitdown* (Its a new Admin Page)
5. Try to Fetch your repo
6. If Successful it will show you all the posts that it could find.
7. Now you can publish and delete the posts to your liking.

The default Resolver will look for Meta Data stored in the Markdown file. Below you see a working example.

```md
---
name: 'My Awesome Post'
slug: 'my-awesome-post'
description: 'The Description for my awesome post.'
---

... Articles content
```

If you don't define a slug it will simply take the name and transform it to a valid slug. Keep in mind that if you change the slug (or name if you don't have a slug) Gitdown wont recognize the article again so you have to delete it manually and reupload it.
 
## Notes

- **Articles**

    You can `Upload` and `Delete` single posts or you can `Publish All` and `Delete All`.

- **Thumbnails**

    Gitdown will try to upload featured images hosted on the Repository and It will also delete them if the articles is removed.

- **Glob Pattern**

    Use glob patterns to tell Gitdown where to look for posts in the repository.

## Possible Future Features
- Specify post status (`publish` or `draft`) in the meta data of the Article.
- Specify `Tags` and `Categories` in the meta data of the Article.
- Multiple Resolver functions and a custom Resolver Function.
- Multiple Glob Patterns.