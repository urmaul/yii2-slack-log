<?php

namespace urmaul\yii2\log\slack;

class Target extends \yii\log\Target
{
    /**
     * Value "Webhook URL" from slack.
     * @var string
     */
    public $webhookUrl;
    
    /**
     * Initializes the route.
     * This method is invoked after the route is created by the route manager.
     */
    public function init()
    {
        parent::init();
        
        if (!$this->webhookUrl)
            throw new InvalidConfigException("Unable to append to log file: {$this->logFile}");
    }
    
    /**
     * Pushes log messages to slack.
     */
    public function export()
    {
        $text = implode("\n", array_map([$this, 'formatMessage'], $this->messages)) . "\n";
        die('<pre>' . var_export($text, true) . "</pre>\n");
    }
}
