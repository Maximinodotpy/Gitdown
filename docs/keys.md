# Frontmatter Keys

Below you see a table that tells you which key in the frontmatter corresponds to which property of your article in WordPress.

| Frontmatter Key | Wordpress Property | Default Value |
|---|---|---|
| `name` | The name of the Post. | None |
| `slug` | The Slug / Post Name of the Post which is the part that is sometimes appended to the URL. This is also the unique identifier of the posts in Gitdown. | None, or coerced from name |
| `description` | The Post Excerpt. | None |
| `tags` | Post Tags. | None |
| `category` | Post Categories. This can be a list or a single string that contains nested categories. | Default WordPress Category |
| `status` | The status of the post. `publish`, `draft` or `trash` | `publish` |
| `post_type` | Is this file a normal Post or is it a page | `post` |
| `parent_page` | The slug of the parent page | None |

[Here](example.md) you find an example of a working markdown file.