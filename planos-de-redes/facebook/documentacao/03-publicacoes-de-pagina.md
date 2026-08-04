<!-- ORIGEM: https://developers.facebook.com/documentation/pages-api/posts.md -->

# Posts



This guide explains how to create, publish, and update a post, and reply to a post on your Facebook Page as the Page, and delete a post using the Pages API from Meta.

## Before you start

This guide assumes you have read the [Overview](https://developers.facebook.com/documentation/pages-api/overview)

For a person who can perform tasks on the page, you will need to implement Facebook Login to ask for the following permissions and receive a Page access token:

* `pages_manage_engagement`
* `pages_manage_posts`  
* `pages_read_engagement`
* `pages_read_user_engagement`
* `publish_video` permission, if you are publishing a video to the Page

Your app user must be able to perform the `CREATE_CONTENT`, `MANAGE`, and `MODERATE` tasks on the Page in the API requests.

If your app users do not own or manage the Page in the API requests, your app will need a User access token and the following features:

* Page Public Content Access

### Best practices

When testing an API call, you can include the `access_token` parameter set to your access token. However, when making secure calls from your app, use the [access token class.](https://developers.facebook.com/documentation/facebook-login/guides/access-tokens#portabletokens)

## Publish posts

To publish a post to a Page, send a `POST` request to the `/page_id/feed` endpoint, where `page_id` is the ID for your Page, with the following parameters:

* `message` set to the text for your post
* `link` set to your URL if you want to post a link
* `published` set to `true` to publish the post immediately (default) or `false` to publish later
    * Include `scheduled_publish_time` if set to `false` with the date in one of the following formats:
         * An integer UNIX timestamp [in seconds] (e.g. `1530432000`)
         * An [ISO 8061](https://en.wikipedia.org/wiki/ISO_8601) timestamp string (e.g. `2018-09-01T10:15:30+01:00`)
         * Any string otherwise parsable by PHP's [`strtotime()`](http://php.net/manual/en/function.strtotime.php) (e.g. `+2 weeks`, `tomorrow`)

#### Notes about scheduled posts

* The publish date must be between 10 minutes and 30 days from the time of the API request.
* If you are relying on `strtotime()`'s relative date strings you can [read-after-write](https://developers.facebook.com/docs/graph-api/advanced#read-after-write) the `scheduled_publish_time` of the created post to make sure it is what is expected.

#### Example Request

*Formatted for readability. Replace **bold, italics values**, such as **page_id**, with your values.*

```curl
curl -X POST "https://graph.facebook.com/v25.0/page_id/feed" \
     -H "Content-Type: application/json" \
     -d '{
           "message":"your_message_text",
           "link":"your_url",
           "published":"false",
           "scheduled_publish_time":"unix_time_stamp_of_a_future_date",
         }'
```

On success, your app receives the following JSON response with the ID for the post:

```json
{
  "id": "page_post_id"
}
```

### Add audience targeting

To limit who can see a Page post, you can add the `targeting.geo_locations` object or `feed_targeting.geo_locations` parameter in your `POST` request.

```curl
-d '{
      ...
      "targeting": {
        "geo_locations": {
          "countries": [
            "CA"
          ],
          "cities": [
            {
              "key": "296875",
              "name": "Toronto"
            }
          ]
        }
      },
      ...
    }'
```

#### Troubleshooting

In some cases using both a country and a region within that country will result in an error: "Some of your locations overlap. Try removing a location." In these cases target the region or the country depending on the coverage you want.

## Publish Media Posts

You can publish photos and videos to a Page.

### Publish a photo

To publish a photo to a Page, send a `POST` request to the `/page_id/photos` endpoint, where `page_id` is the ID for your Page, with the `url` parameter set to the photo for your post.

#### Example Request

*Formatted for readability. Replace **bold, italics values**, such as **page_id**, with your values.*

```curl
curl -X POST "https://graph.facebook.com/v25.0/page_id/photos" \
     -H "Content-Type: application/json" \
     -d '{
           "url":"path_to_photo",
```

On success, your app receives the following JSON response with the ID for the photo and the ID for the post:

```json
{
  "id":"photo_id",
  "post_id":"page_post_id"
}
```

### Publish a video

Please visit the [**Video API documentation** to publish a video post to your Page](https://developers.facebook.com/documentation/video-api/guides/publishing).

## Get Posts {#posts}

To get a list of Page posts, send a `GET` request to the `/page_id/feed` endpoint.

#### Example Request

*Formatted for readability. Replace **bold, italics values**, such as **page_id**, with your values.*

```curl
curl -i -X GET "https://graph.facebook.com/v25.0/page_id/feed"
```

On success, your app receives the following JSON response with an array of objects that include the post ID, the time the post was created, and the content for the post, for each post on your Page:

```json
{
  "data": [
    {
      "created_time": "2019-01-02T18:31:28+0000",
      "message": "This is my test post on my Page.",
      "id": "page_post_id"
    }
  ],
...
}
```

#### Limitations

- **Live Videos** - If a Page post contains a video that has expired, such as a live broadcast, you can get some post fields but not fields related to the video. The video has its own privacy rules. If the video has expired, you must be the page admin to view its information.

- **Message CTA** - Any access token can be used to request publicly shared Page posts as long as your app has been approved for the [Page Public Content Access Feature](https://developers.facebook.com/docs/apps/review/feature#reference-PAGES_ACCESS). However, posts with message CTAs cannot be accessed using another Page's access token since pages cannot message other pages.

### Page Post URLs

The URL, or permalink, for a Page post is `https://www.facebook.com/`***`page_post_id`***.

## Update a Post

To update a Page post, send a `POST` request to the `/page_post_id` endpoint with the parameters you want to update set to the new content.

#### Example Request

*Formatted for readability. Replace **bold, italics values**, such as **page_post_id**, with your values.*

```curl
curl -X POST "https://graph.facebook.com/v25.0/page_post_id" \
     -H "Content-Type: application/json" \
     -d '{
           "message":"I am updating my Page post",
         }'
```

On success, your app receives the following JSON response with `success` set to true:

```json
{
  "success": true
}
```

#### Limitations

An app can only update a Page post if the post was made using that app.

## Delete a Post

To delete a Page post, send a `DELETE` request to the `/page_post_id` endpoint where `page_post_id` is the ID for post you want to delete.

#### Example Request

*Formatted for readability. Replace **bold, italics values**, such as **page_post_id**, with your values.*

```
curl -i -X DELETE "https://graph.facebook.com/v25.0/page_post_id"
```

On success, your app receives the following JSON response with `success` set to `true`:

```json
{
  "success": true
}
```

## Next Steps

Learn how to [comment on Page posts and @mention](https://developers.facebook.com/docs/pages/comments) a specific person or Page who posted or commented on your Page.

## See Also

#### Video API guides

- [Video Uploads Guide](https://developers.facebook.com/documentation/video-api/guides/publishing)

- [Video API Guide](https://developers.facebook.com/docs/graph-api/video)

#### References

- [Page Reference](https://developers.facebook.com/docs/graph-api/reference/page)

- [Page Feed Reference](https://developers.facebook.com/docs/graph-api/reference/page/feed)

- [Page Post Reference](https://developers.facebook.com/docs/graph-api/reference/page-post)

- [Permissions Reference](https://developers.facebook.com/docs/permissions)

- [Photo Reference](https://developers.facebook.com/docs/graph-api/reference/photo)

- [Page Tasks](https://developers.facebook.com/documentation/pages-api/overview#tasks)

- [Video Reference](https://developers.facebook.com/docs/graph-api/reference/video)
