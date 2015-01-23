# CiiMS API



[![Latest Version](http://img.shields.io/packagist/v/ciims-modules/api.svg?style=flat)]()



[![Downloads](http://img.shields.io/packagist/dt/ciims-modules/api.svg?style=flat)]()



[![Gittip](http://img.shields.io/gittip/charlesportwoodii.svg?style=flat "Gittip")](https://www.gittip.com/charlesportwoodii/)



[![License](http://img.shields.io/badge/license-MIT-orange.svg?style=flat "License")](https://github.com/charlesportwoodii/ciims-modules-api/blob/master/LICENSE.md)







The CiiMS API module provides basic access to common methods and data. The CiiMS API is a JSON REST API which supports GET, POST, and DELETE. POSTS requests should be sent as JSON encoded form fields for simplicity.







## API Overview



This documentation provides a comprehensive overview of everything that the CiiMS API has to offer. This documentation offers a full explaination of each API endpoint, as well as example request and responses.



In this documentation, the following format will be used to describe the available endpoints. In the event there are no parameters, that section will not be included in the method signature. Additional parameters may be made available in the description.



__[HTTP_VERB] [/uri/path] [params] Method Description__







## License



See LICENSE.md







### API Objectives



The API has been designed with serveral components in mind:







- Performance



- Security



- Simplicity







### Accessing the API



The CiiMS API can be accessed via the ```/api``` endpoint of your CiiMS instance.







### Appropriate Request Headers



When making a request to the API you have 2 options for interaction, you can either send raw JSON via ```application/json``` as a raw request __OR__ you can send ```application/x-www-form-urlencoded``` form data and serialize your parameters as you would in jQuery. If any raw request body is recieved the API will assume that the data you sent is ```application/json``` and will interpret the data as that.







### Responses



All responses from the API will be returned as JSON objects and will at minimum contain the HTTP response code sent with the headers, a error message if applicable, and an object called "response" which will contain the response. If an occur occurs, (depending on the resource), the response will be an empty JSON object or NULL.







    { "status" : <http_status_code>, "message" : null, "response" : { } }







------







# Authentication

While some API endpoints are made publicly available, most require authentication to access. 



## [POST] [/user/token] Authenticating



When you authenticate against the CiiMS API, you'll be presented with a long-life token that you can use for future requests. This long-life token should be kept in a secure location, as it grants whoever controls it full access to any available resource that the token owner has access to.



All fields listed below are required:



__Example Request:__



```

{

    "email": "email@example.tld",

    "password": "<password>",

    "name": "<yourApplicationName>""

}

```



If your authentication request is successfull, you'll recieve the following response:



__Example Response:__





```

{

    "status": 200,

    "message": "Your request was successfully fulfilled",

    "response": "<long-life-token>"

}

```



In the event that authentication fails, you'll be presented with a generic 403 error message.



```

{

    "status": 403,

    "message": "Unable to authenticate.",

    "response": false

}

```



Once authenticated, any request that requires authentication can be accessed by including the following headers with your request:



```

X-Auth-Email: user@example.tld

X-Auth-Token: <long-life-token>

```



## [DELETE] [/user/token] Deauthenticating



If for any reason you believe your long-life token has been comprimised, it is advised to immediatly revolk your token.



__Example Response:__



```

{

    "status": 200,

    "message": "Your request was successfully fulfilled",

    "response": true

}

```



------





# Available API Methods



The following API endpoints are made available for testing. This next section will divide the API endpoints by controller/namespace.



## Card

The card API is considered private, and should not be used directly. Do not attempt to directly access the card endpoint.



### [GET] [/card/index] Retrieve Dashboard Cards

A list of all installed dashboard cards for the current user can be retrieved by accessing this endpoint



__Example Response:__



```

{

    "status": 200,

    "message": "Your request was successfully fulfilled",

    "response": {

        "cards": {

            "<id>": "<url>"

        },

        "cardData": {

            "<id>": <json_card_params_object>

        }

    }

}

```



### [POST] [/card/index] Install a new Dashboard Card

This endpoint is used to install a new dashboard card for your user.



__Example Request:__



```

{

    "id": <generated_uuid>,

    "url": <card_endppint_url_fqdn>,

    "details": <json_object>

}

```



### [DELETE] [/card/index] [id] Deletes an installed card

Cards can be deleted from a user by sending a request ot his endppint



### [GET] [/card/details] [id] Retrieves the card details

This endpoint is automatically called when the card details are loaded in the dashboard.



__Example Request:__



```

{

    "status": 200,

    "message": "Your request was successfully fulfilled",

    "response": <json_params>

}

```



### [POST] [/card/details] [id] Set card details

Card details can be modified by sending a POST request to this endpoint. This endppoint is called when any details for a card change.



### [POST] [/card/rearrange] Rearrange Dashboard

This endpoint allows the dashboard cards to be re-arranged.



## Category

The category endpoints allow users and administrators to view and manage categories.



### [GET] [/category/index] [OPT id] Retrieve categories

Any user may retrieve a public listing of all categories in the categories currently in the system. This endpoint supports pagination via the ```page``` GET parameter, and also supports searching & filtering.



If the ID parameter is provided, this endpoint will only return data about the requested category/



__Example Response:__



```

{

    "status": 200,

    "message": "Your request was successfully fulfilled",

    "response": [{

        "id": "12",

        "name": "Name",

        "slug": "name",

        "created": "1380326400",

        "updated": "1386460800",

        "parent": {

            "id": "1",

            "parent_id": "1",

            "name": "Uncategorized",

            "slug": "uncategorized",

            "created": "1417471909",

            "updated": "1417471909"

        },

        "metadata": []

    }, {

        "id": "10",

        "name": "example2",

        "slug": "example2",

        "created": "1371772800",

        "updated": "1371772800",

        "parent": {

            "id": "1",

            "parent_id": "1",

            "name": "Uncategorized",

            "slug": "uncategorized",

            "created": "1417471909",

            "updated": "1417471909"

        },

        "metadata": []

    }]

}

```



### [POST] [/category/index] [OPT id] Create/Update Category

New categories can by created by sending a POST request to this endpoint. If an ID is provided, the category will be updated if it exists.



__Example Request:__



```

{

    "name": "name",

    "slug": "<uri_slug>",

    "parent_id": <id_of_parent_category> // default of 1

}

```



__Example Response:__



```

{

    "status": 200,

    "message": "Your request was successfully fulfilled",

    "response": [{

        "id": "12",

        "name": "Name",

        "slug": "name",

        "created": "1380326400",

        "updated": "1386460800",

        "parent": {

            "id": "1",

            "parent_id": "1",

            "name": "Uncategorized",

            "slug": "uncategorized",

            "created": "1417471909",

            "updated": "1417471909"

        },

        "metadata": []

    }]

}

```



### [DELETE] [/category/index] [id] Delete a Category

Categories can be deleted by sending a DELETE request to this endpoint.



__Limitations:__



1. The root category "Uncategorized" cannot be deleted

2. When a category is deleted, all child categories will be reassigned to the deleted categories parent category.



## Comment

The following endpoints are made available to create, edit, and manage CiiMS' internal comment system. Note, that if any other comment system is enabled, all of these endpoints will return a 403 error.

CiiMS' comment system is fairly basic. Users can add new comments, update them, and remove them, and administrators can manage these comments at a high level. In order to facilitate useful discussions, this comment system also implements a reputation system. Each user starts with 100 reputation, and as they comment their reputation will increase or decrease (completely transparently to the end user). Certain actions will increase a user's reputation, while others will decrease it.

If at any point the user's reputation drops below a certain threshold, all comments for that user will immediatly be shadow-banned. The user will still be able to comment as usual, but no other users will see their entries.

In future iterations of CiiMS this sytem will provide a way to up and downvote comments, and allow individual comments to be shadow-banned.

### [GET] [/comment/comments] [id] Retrieves comments for a given content id
Retrieves comments for a given content id

__Example Response:__

```
{
    "status": 200,
    "message": null,
    "response": [{
        "id": <comment_id>,
        "content_id": <id>,
        "author_id": <user_id>,
        "user": {
            "email": "",
            "firstName": "",
            "lastName": "",
            "username": ""
        },
        "content": {
            "id": <content_id>,
            "slug": <slug>,
            "title": <title>
        },
        "comment": <comment>,
        "created": 1237519512,
        "updated": 1237519512
    }, {
        "id": <comment_id>,
        "content_id": <id>,
        "author_id": <user_id>,
        "user": {
            "email": "",
            "firstName": "",
            "lastName": "",
            "username": ""
        },
        "content": {
            "id": <content_id>,
            "slug": <slug>,
            "title": <title>
        },
        "comment": <comment>,
        "created": 1237519512,
        "updated": 1237519512
    }]
}
```


### [GET] [/comment/user] [id] Retrieves comments for a given user
Retrieves comments for a given user

```
__Example Response:__

```
{
    "status": 200,
    "message": null,
    "response": [{
        "id": <comment_id>,
        "content_id": <id>,
        "author_id": <user_id>,
        "user": {
            "email": "",
            "firstName": "",
            "lastName": "",
            "username": ""
        },
        "content": {
            "id": <content_id>,
            "slug": <slug>,
            "title": <title>
        },
        "comment": <comment>,
        "created": 1237519512,
        "updated": 1237519512
    }, {
        "id": <comment_id>,
        "content_id": <id>,
        "author_id": <user_id>,
        "user": {
            "email": "",
            "firstName": "",
            "lastName": "",
            "username": ""
        },
        "content": {
            "id": <content_id>,
            "slug": <slug>,
            "title": <title>
        },
        "comment": <comment>,
        "created": 1237519512,
        "updated": 1237519512
    }]
}
```


### [POST] [/comment/count] Retrieves a count of comments for a list of entries
Retrieves comment countes for the requested entries.

__Example Request:__

```
{
    "ids": []
}
```

__Example Response:__

```
{
    id: count,
    id2: count2,
    [...]
}
```

### [POST] [/comment/index] [OPT id] Create or Update a comment
Allows a user to create or modify a comment if an ID is provided.

__Example Request:__

```
{

    "id": <comment_id>,

    "content_id": <id>,

    "author_id": <user_id>,

    "comment": <comment>,

}

```

__Example Response:__

```
{
    "status": 200,
    "message": null,
    "response": [{
        "id": <comment_id>,
        "content_id": <id>,
        "author_id": <user_id>,
        "user": {
            "email": "",
            "firstName": "",
            "lastName": "",
            "username": ""
        },
        "content": {
            "id": <content_id>,
            "slug": <slug>,
            "title": <title>
        },
        "comment": <comment>,
        "created": 1237519512,
        "updated": 1237519512
    }]
}
```

### [DELETE] [/comment/index] [id] Deletes a comment
For when you say something you shouldn't in a public setting. = )

### [POST] [/comment/flag] [id] Flag a comment
Flags a comment for review/as contriversial.


## Content



## Default

The following endpoints are available on the default endpoint.



### [GET] [/default/index | /index | /] Retrieves API status

This endpoint will return the current status of the API. Curently this checks the connectivity of the database.



__Example Response:__ 



```

{

    "status": 200,

    "message": "Your request was successfully fulfilled",

    "response": true

}

```



### [POST] [/default/jsonProxy] JSON Proxy

A JavaScript frontend presents some limitations when attempting to access data that doesn't provide appropriate CORS headers, or is not available over HTTPS when SSL is used. This endpoint can be utilized to get around these restrictions for authenticated users with a role >= 5 (Collaborators or any higher role)



__WARNING:__



This API endpoint can be vulnerable to abuse by any authenticated user. Therefore access to this endpoint is restricted to authenticated users with collaboration abilities or higher (Role >= 5).



__Limitations:__



Due to the nature of this endpoint, there are several limitations to its use.



1. Access to limited to authenticated users with collaboration ability or _higher_.

2. Data will be cached server side fo 10 minutes.

3. This endppint can only process JSON response

4. This endpoint will only request data from the provided URL, and will not pass on any headers or other information.



__Example Request:__



```

{

    "url": <url_of_remote_json_resource>

}

```



The response of this result will be cached for 10 minutes to prevent abuse, and will dump the result of the cURL request in the response parameter of the response object.



## Event

The Event API endpoint allows CiiMS' internal ```analytics.js``` plugin to track page views and other data. This information is used to display recent page views for content.



### [GET] [/event/index] Search events

Allows users to search recent events. This method is available to users with collaboration status or higher



This method supports basic Yii search GET parameters and pagination via the ```Event``` model.



### [POST] [/event/index] Record an event

Records an event. This endpoint is open ended and will allow developers to record custom events.



__Example Request:__



```

{

    "event": <your_event_name>,

    "uri": <event_uri>,

    //"content_id": <content_id_of_event>,

    "event_data": <json_data_to_store_with_event>

}

```



### [POST] [/event/count] Retrieve event counts

Retreives the ```_pageview``` event counts for the last 24 hours period for the provided ids. This method is available to users with collaboration status or higher.



__Example Request:__



```

{

    "ids": []

}

```



__Example Response:__



```

{

    "status": 200,

    "message": null,

    "response": {

        "<id>": <count>

    }

}

```



## Setting

The setting endpoint can be used to view and manage all settings that CiiMS stores and uses.



__Note:__



New settings may be added at any time, and this documentation may not reflect the latest setting. To retrieve the latest list of available settings please reference the models listed in [CiiMS::protected/models/settings](https://github.com/charlesportwoodii/CiiMS/tree/master/protected/models/settings)



The following section outlines the available settings for that endpoint. Each section will list the available settings for both the GET and POST endpoints. Each endpoint will return the listed settings as shown (relative to your instance) for both the GET and POST responses. The listed JSON object is what is used for the POST body for modification.



### General Settings



The following endpoints allow admins to manage general settings



__Endpoints:__



```

[GET] [/setting/index]

[POST] [/setting/index]

```



__JSON Body:__



```

{

    "name": "[Site Name]",

    "dateFormat": "F jS, Y",

    "timeFormat": "H:i",

    "defaultLanguage": "en_US",

    "forceSecureSSL": "1",

    "offline": "0",

    "bcrypt_cost": "13",

    "searchPaginationSize": "10",

    "categoryPaginationSize": "10",

    "contentPaginationSize": "10",

    "useDisqusComments": "1",

    "disqus_shortname": "disqus_shortname",

    "useOpenstackCDN": "0",

    "useRackspaceCDN": "0",

    "openstack_identity": null,

    "openstack_username": null,

    "openstack_apikey": ,

    "openstack_region": null,

    "openstack_container": null

}

```



### Analytics Settings



The following endpoints allow admins to manage analytics settings



__Endpoints:__



```

[GET] [/setting/analytics]

[POST] [/setting/analytics]

```



__JSON Body:__



```

{

    "analyticsjs_Google__Analytics_enabled": 0,

    "analyticsjs_Google__Analytics_domain": null,

    "analyticsjs_Google__Analytics_trackingId": null,

    "analyticsjs_Google__Analytics_universalClient": 1,

    "analyticsjs_Pingdom_enabled": false,

    "analyticsjs_Pingdom_id": null,

    "analyticsjs_Piwik_enabled": 0,

    "analyticsjs_Piwik_url": null,

    "analyticsjs_Piwik_siteId":null

}

```



### Email Settings



The following endpoints allow admins manage email settings.



When left empty, CiiMS' SMTP adapter will attempt to send emails via the ```sendmail``` method. If any SMTP attributes are not empty, then CiiMS will attempt to send information using that driver. For testing, CiiMS provide



__Endpoints:__



```

[GET] [/setting/email]

[POST] [/setting/email]

```



__JSON Body:__



```

{
    "SMTPHost": null,

    "SMTPPort": null,

    "SMTPUser": null,

    "SMTPPass": null,

    "notifyName": null,

    "notifyEmail": null,

    "useTLS": 0,

    "useSSL": 0

}

```



#### [GET] [/setting/emailtest] Test email settings



After adjusting your email settings, it's __strongly__ advised to verify that your settings work. You can test this by sending a GET request to this endpoint, which will attempt to send an email to your currently logged in user.



### Social Settings



The following endpoints allow admins manage social settings. Most of these settings specifically apply to the HybridAuth module for social authentication, it can be used to store general social settings for use in other modules/themes.



__Endpoints:__



```

[GET] [/setting/social]

[POST] [/setting/social]

```



__JSON Body:__


```

{

    "ha_twitter_enabled": false,

    "ha_twitter_key": null,

    "ha_twitter_secret": null,

    "ha_twitter_accessToken": null,

    "ha_twitter_accessTokenSecret": null,

    "ha_facebook_enabled": false,

    "ha_facebook_id": null,

    "ha_facebook_secret": null,

    "ha_facebook_scope": null,

    "ha_google_enabled": false,

    "ha_google_id": null,

    "ha_google_secret": null,

    "ha_google_scope": null,

    "google_plus_public_server_key": null,

    "ha_linkedin_enabled": false,

    "ha_linkedin_key": null,

    "ha_linkedin_secret": null,

    "addThisPublisherID": null

}

```



### Theme Settings



The following endpoints allow admins manage theme settings.



__Endpoints:__



```

[GET] [/setting/theme]

[POST] [/setting/theme]

```



__JSON Body:__



The JSON body for this endpoint will vary depending upon the currently installed theme.



## Theme


The following API endpoints are made available for managing themes.

### Callbacks

For themes that provide public callbacks, those callbacks can be accessed via this endpoint. Callbacks support both GET and POST callbacks. Data will be passed as-is to the method (eg GET will past $_GET data where POST will pass $_POST data to the method).

```
[GET] [/theme/callback] [theme, method]
[POST] [/theme/callback] [theme, method]
```

### [GET] [/theme/installed] Lists installed themes

This endpoint returns a list of installed themes.

__Example Response:__

```
{
    "status": 200,
    "message": "Your request was successfully fulfilled",
    "response": {
        "<theme_name>": {
            "path": "<system_path>",
            "name": "<composer.json namespace>"
        },
        "<theme_name>": {
            "path": "<system_path>",
            "name": "<composer.json namespace>"
        },
        "<theme_name>": {
            "path": "<system_path>",
            "name": "<composer.json namespace>"
        }
    }
}
```

### [GET] [/theme/install] [name] Change Theme

Attempts to install a theme with a given ```name``` and returns ```true``` or ```false``` if a theme with a given ```name``` was installed succesfully.

### [GET] [/theme/changetheme] [name] Change Theme

Allows administrators to change the current them to an installed them.

### [GET] [/theme/update] [name] Updates a theme

Attempts to update a theme with a given ```name``` and returns ```true``` or ```false``` if a theme with a given ```name``` was updated succesfully.

### [GET] [/theme/updatecheck] [name] Checks if a theme has an update.

Returns ```true``` or ```false``` if a theme with a given ```name``` has an update available. This information is based upon the ```VERSION``` file that is written during installation. Consequently theme with a custom installation, or themes that are installed by default will always appear as requiring an update until they are updated and the theme is installed through CiiMS' internal theme installer.

### [GET] [/theme/uninstall] [name] Uninstalls a theme

Attempts to uninstall a theme with a given ```name``` and returns ```true``` or ```false``` if a theme with a given ```name``` was uninstall succesfully.

### [GET] [/theme/list] Lists themes available to install

themes.ciims.io broadcasts a list of themes are are free for public installation. This endpoint will list all of those themes.

__Example Response:__

```
{
    "status": 200,
    "message": "Your request was successfully fulfilled",
    "response": {
        "Default": {
            "name": "ciims-themes\/default",
            "version": "3.0.11",
            "repository": "https:\/\/github.com\/charlesportwoodii\/ciims-themes-default"
        },
        "Spectre": {
            "name": "ciims-themes\/spectre",
            "version": "2.0.2",
            "repository": "https:\/\/github.com\/charlesportwoodii\/ciims-themes-spectre"
        }
    }
}
```

### [GET] [/theme/isInstalled] [name] Determines if a theme is installed

Returns ```true``` or ```false``` if a theme with a given ```name``` is installed.

### [GET] [/theme/details] [name] Lists details for a given theme

Returns the details for a given theme. This information is ultimatly pulled from the composer.json/packagist details for a given theme.

__Example Response:__

```
{
    "status": 200,
    "message": "Your request was successfully fulfilled",
    "response": {
        "name": "ciims-themes\/default",
        "description": "The default theme that comes with CiiMS",
        "repository": "https:\/\/github.com\/charlesportwoodii\/ciims-themes-default",
        "maintainers": [{
            "name": "charlesportwoodii",
            "email": null,
            "homepage": null
        }],
        "latest-version": "3.0.13",
        "sha": "1da64831967ef8f55a03694e2290786118995f4d",
        "file": "https:\/\/github.com\/charlesportwoodii\/ciims-themes-default\/archive\/3.0.13.zip",
        "downloads": {
            "total": 567,
            "monthly": 106,
            "daily": 1
        }
    }
}
```



## User


The following API endpoints are made available for manipulating and managing user data.



### [GET] [/user/index] [OPT: id=user_id] Retrieving User Information



Authenticated users can retrieve information about themselves by querying this endpoint. This endpoint will dump all available data that is allowed by the user's role.



Note that this endpoint will only permit the user to access their own information. Only administrators can access information about another user. Authenticated administrators will see a full listing of all users in the system if the ```id``` parameter is not specified.



__Additional Params:__

For administrators, this endpoint supports basic Yii ```model->search()``` parameters, allowing for searching & filtering by any GET parameters.



```

GET /user/index?User[username]=username

```



This endpoint supports pagination via the ```page``` GET variable.



__Example Response:__



```

{

    "status": 200,

    "message": "Your request was successfully fulfilled",

    "response": [{

        "id": "1",

        "email": "email@example.tld",

        "username": "username",

        "user_role": "9",

        "status": "1",

        "created": "1420134690",

        "updated": "1420134690",

        "role": {

            "id": "9",

            "name": "Administrator",

            "created": "1417471909",

            "updated": "1417471909"

        },

        "metadata": []

    }]

}

```



### [POST] [/user/index] [id] Update a User



Authenticated users can update their own information by sending a POST request to this endpoint, while administrators can update information for any user.



If the ID parameters is not provided, then an administrative POST to this endpoint will create a new user with the information provided. For creation of new users, all user fields are required. For updating users, you only need to provide the fields you want to update



__Example Request:__



```

{
    "email": "email@example.tld",
    "username": "username",
    "user_role": "9",
    "status": "1",
    "password": "password"
}

```



__Example Response:__



```

{
    "status": 200,
    "message": "Your request was successfully fulfilled",
    "response": {
        "id": "1",
        "email": "email@example.tld",
        "username": "username",
        "user_role": "9",
        "status": "1",
        "created": "1420134690",
        "updated": "1420134690",
        "role": {
            "id": "9",
            "name": "Administrator",
            "created": "1417471909",
            "updated": "1417471909"
        },
        "metadata": []
    }
}

```



### [POST] [/user/register] Register a new User

Unauthenticated users can register a new user to the site.



__Example Request:__



```

{
    "email": "user@example.tld",
    "password": "password",
    "password_repeat": "password",
    "username": "username"
}

```



### [POST] [/user/invite] Invite a User



Administrators can invite new users to the platform by sending a POST request to this endpoint



__Example Request:__



```

{
    "email": "user@example.tld"
}

```