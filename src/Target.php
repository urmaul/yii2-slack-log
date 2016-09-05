<?php

namespace urmaul\yii2\log\slack;

use Yii;
use yii\base\Exception as YiiException;
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
     * Bot icon url
     * @var string 
     */
    public $icon_url = null;
    /**
     * Bot icon emoji
     * @var string 
     */
    public $icon_emoji = ':beetle:';
    
    /**
     * Message prefix
     * @var string 
     */
    public $prefix;
    
    public $colors = [
        Logger::LEVEL_ERROR => 'danger',
        Logger::LEVEL_WARNING => 'warning',
        Logger::LEVEL_INFO => '#5bc0de',
    ];
    
    /**
     * Initializes the route.
     * This method is invoked after the route is created by the route manager.
     */
    public function init()
    {
        parent::init();
        
        if (!$this->webhookUrl)
            $this->enabled = false;
        
        if (!$this->username)
            $this->username = Yii::$app->name;
        
        // Not pushing Slackbot request errors to slack.
        if (isset(Yii::$app->request->userAgent) && preg_match('/^Slackbot-/', Yii::$app->request->userAgent))
            $this->enabled = false;
    }
    
    /**
     * Pushes log messages to slack.
     */
    public function export()
    {
        if (!$this->messages || !$this->webhookUrl)
            return;
        
        list($text, $attachments) = $this->formatMessages();
        
        $body = json_encode([
            'username' => $this->username,
            'icon_url' => $this->icon_url,
            'icon_emoji' => $this->icon_emoji,
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
        
        $currentUrl = null;
        try {
            $currentUrl = Url::to('', true);
            $text .= 'Current URL: <' . $currentUrl . '>' . "\n";
        } catch (\Exception $exc) {}
        
        try {
            $text .= $this->getMessagePrefix(null) . "\n";
        } catch (\Exception $exc) {}
        
        foreach ($this->messages as $message) {
            $attachments[] = $this->formatAttachment($message) + [
                'color' => $this->getColor($message[1]),
                'title_link' => $currentUrl,
            ];
        }
        
        return [$text, $attachments];
    }
    
    /**
     * Formats a log message for display as a string.
     * @param array $message the log message to be formatted.
     * The message structure follows that in [[Logger::messages]].
     * @return string the formatted message
     */
    protected function formatAttachment($message)
    {
        list($body, $level) = $message;
        
        if ($body instanceof \Exception) {
            $e = $body;
            
            return [
                'fallback' => (string) $e,
                'title' => 
                    ($e instanceof YiiException ? $e->getName() : get_class($e)) .
                    ($e->getMessage() ? ': ' . $e->getMessage() : ''),
                'text' => 
                    'in ' . $e->getFile() . ':' . $e->getLine() . "\n" .
                    '```' . "\n" . $e->getTraceAsString() . "\n" . '```',
                'mrkdwn_in' => ['text'],
            ];

        } elseif ($level === Logger::LEVEL_INFO && is_string($body)) {
            return [
                'fallback' => $body,
                'text' => $body,
            ];

        } else {
            return [
                'fallback' => $this->formatMessage($message),
                'text' => $this->formatMessage($message),
            ];
        }
    }
    
    protected function getColor($level)
    {
        return isset($this->colors[$level]) ? $this->colors[$level] : null;
    }
}
