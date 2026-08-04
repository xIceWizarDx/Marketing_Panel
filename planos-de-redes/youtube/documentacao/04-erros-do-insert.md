<!-- ORIGEM: https://developers.google.com/youtube/v3/docs/videos/insert#errors -->

# Erros do videos.insert

| Motivo | Mensagem |
|---|---|
| `notifySubscribers` | boolean The notifySubscribers parameter indicates whether YouTube should send a notification about the new video to users who subscribe to the video's channel. A parameter value of True indicates that subscribers will be notified of newly uploaded videos. However, a channel owner who is uploading many videos might prefer to set the value to False to avoid sending a notification about each new video to the channel's subscribers. The default value is True. |
| `onBehalfOfContentOwner` | string This parameter can only be used in a properly authorized request. Note: This parameter is intended exclusively for YouTube content partners.The onBehalfOfContentOwner parameter indicates that the request's authorization credentials identify a YouTube CMS user who is acting on behalf of the content owner specified in the parameter value. This parameter is intended for YouTube content partners that own and manage many different YouTube channels. It allows content owners to authenticate once and get access to all their video and channel data, without having to provide authentication credentials for each individual channel. The CMS account that the user authenticates with must be linked to the specified YouTube content owner. |
| `onBehalfOfContentOwnerChannel` | string This parameter can only be used in a properly authorized request. This parameter can only be used in a properly authorized request. Note: This parameter is intended exclusively for YouTube content partners.The onBehalfOfContentOwnerChannel parameter specifies the YouTube channel ID of the channel to which a video is being added. This parameter is required when a request specifies a value for the onBehalfOfContentOwner parameter, and it can only be used in conjunction with that parameter. In addition, the request must be authorized using a CMS account that is linked to the content owner that the onBehalfOfContentOwner parameter specifies. Finally, the channel that the onBehalfOfContentOwnerChannel parameter value specifies must be linked to the content owner that the onBehalfOfContentOwner parameter specifies.This parameter is intended for YouTube content partners that own and manage many different YouTube channels. It allows content owners to authenticate once and perform actions on behalf of the channel specified in the parameter value, without having to provide authentication credentials for each separate channel. |
| `defaultLanguageNotSet` | The request is trying to add localized video details without specifying the default language of the video details. |
| `invalidCategoryId` | The snippet.categoryId property specifies an invalid category ID. Use the videoCategories.list method to retrieve supported categories. |
| `invalidDescription` | The request metadata specifies an invalid video description. |
| `invalidFilename` | The video filename specified in the Slug header is invalid. |
| `invalidPublishAt` | The request metadata specifies an invalid scheduled publishing time. |
| `invalidRecordingDetails` | The recordingDetails object in the request metadata specifies invalid recording details. |
| `invalidTags` | The request metadata specifies invalid video keywords. |
| `invalidTitle` | The request metadata specifies an invalid or empty video title. |
| `invalidVideoGameRating` | The request metadata specifies an invalid video game rating. |
| `invalidVideoMetadata` | The request metadata is invalid. |
| `mediaBodyRequired` | The request does not include the video content. |
| `uploadLimitExceeded` | The user has exceeded the number of videos they may upload. |
| `forbiddenLicenseSetting` | The request attempts to set an invalid license for the video. |
| `forbiddenPrivacySetting` | The request attempts to set an invalid privacy setting for the video. |
