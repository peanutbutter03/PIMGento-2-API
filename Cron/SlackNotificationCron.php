<?php

namespace Pimgento\Api\Cron;

use Pimgento\Api\Job\RunSlackMessage;
use Psr\Log\LoggerInterface;

class SlackNotificationCron {

    protected $runSlackMessage;

    public function __construct(RunSlackMessage $runSlackMessage) {
        $this->runSlackMessage = $runSlackMessage;
    }

    /**
     * Write to system.log
     *
     * @return void
     */
    public function execute() {
        $this->runSlackMessage->execute();
    }
}
