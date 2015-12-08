<?php

namespace urmaul\yii2\log\slack;

use Yii;
use yii\log\Logger;
use yii\helpers\Url;
use HttpClient;

/**
 * Log target that pushes logs to Slack channel.
 */
class Target extends \yii\log\Target
{
    /**
     * Value "Webhook URL" from slack.
     * @var string
     */
    public $webhookUrl;
    
    /**
     * Bot username. Defaults to application name.
     * @var string
     */
    public $username = null;
    /**
     * Bot icon emoji
     * @var string 
     */
    public $emoji = ':beetle:';
    
    /**
     * Message prefix
     * @var string 
     */
    public $prefix;
    
    /**
     * Initializes the route.
     * This method is invoked after the route is created by the route manager.
     */
    public function init()
    {
        parent::init();
        
        if (!$this->webhookUrl)
            throw new InvalidConfigException("Unable to append to log file: {$this->logFile}");
        
        if (!$this->username)
            $this->username = Yii::$app->name;
    }
    
    /**
     * Pushes log messages to slack.
     */
    public function export()
    {
        list($text, $attachments) = $this->formatMessages();
        
        $body = json_encode([
            'username' => $this->username,
            'icon_emoji' => $this->emoji,
            'text' => $text,
            'attachments' => $attachments,
        ], JSON_PRETTY_PRINT);
        
        $params = ['headers' => ['Content-Type: application/json']];
        HttpClient::from()->post($this->webhookUrl, $body, $params);
    }
    
    /**
     * Formats all log messages as one big slach message
     * @return array [$text, $attachments]
     */
    protected function formatMessages()
    {
        $text = ($this->prefix ? $this->prefix . "\n" : '');
        $attachments = [];
        
        try {
            $currentUrl = Url::to('', true);
            $text .= '>Current URL: <' . $currentUrl . '>' . "\n";
            
            $attachmentLink = ['title_link' => $currentUrl];
        } catch (\Exception $exc) {}
        
        foreach ($this->messages as $message) {
            if (is_string($message[0]) && $message[1] === Logger::LEVEL_INFO) {
                $attachments[] = [
                    'fallback' => $message[0],
                    'text' => $message[0],
                    'color' => '#439FE0',
                ];
                
            } elseif ($message[0] instanceof \Exception) {
                $exception = $message[0];
                $attachments[] = [
                    'fallback' => (string) $exception,
                    'title' => $message[0]->getMessage(),
                    'text' =>
                        'in ' . $exception->getFile() . ':' . $exception->getLine() . "\n" .
                        '```' . "\n" . $exception->getTraceAsString() . "\n" . '```',
                    'color' => 'danger',
                    'mrkdwn_in' => ['text'],
                ] + $attachmentLink;
                
            } else {
                $text .= $this->formatMessage($message) . "\n";
            }
        }
        
        return [$text, $attachments];
    }
}
