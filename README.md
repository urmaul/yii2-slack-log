# yii2-slack-log

Yii2 log route that pushes logs to Slack channel.

## How to install

1. Add "Incoming WebHook" to slack.
2. Attach log route.
    
    ```
    composer require urmaul/yii2-slack-log '~1.0'
    ```
    
3. Add this route to log targets.
    
    ```
    'log' => [
        'targets' => [
            [
                'class' => 'urmaul\yii2\log\slack\Target',
                'levels' => ['error'], // Send message on errors
                'except' => ['yii\web\HttpException:403', 'yii\web\HttpException:404'], // ...except 403 and 404
                'webhookUrl' => 'YOUR_WEBHOOK_URL_FROM_SLACK',
                //'username' => 'MYBOT', // Bot username. Defaults to app name
                //'icon_url' => null, // Bot icon URL
                //'icon_emoji' => ':beetle:', // Bot icon emoji
                //'prefix' => '', // Any text prefix. As a sample, you can mention @yourself.
            ],
        ],
    ],
    ```
