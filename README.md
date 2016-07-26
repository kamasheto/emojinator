# emojinator

Emojinator is a little tool that adds emojis for your Slack team from their avatars. (Until there is a way to do that via API, this would have to do.)

## Setup
In `config.php`:
```
<?php

define('SITE', 'moovel');

define('A', 'A_COOKIE_VALUE');

define('B', 'A_COOKIE_REAL_VALUE');
```

- Change site to reflect your team name (e.g. for `moovel.slack.com` enter `moovel`)
- Change A and B cookies as found in your browser (A is the value of your a cookie, B is the value of your a-{a-value} cookie)

## Run

```
php emojinator.php # adds emojis for all users of your team
php emojinator.php mo tobi # adds emojis only for mo and tobi
```
