<?php

namespace Pimgento\Api\Job;

use Magento\Store\Model\StoreManagerInterface;
use Pimgento\Api\Api\Data\ImportInterface;
use Pimgento\Api\Model\ResourceModel\Log\Collection;

class SlackMessage
{
    protected $store;

    public function __construct(StoreManagerInterface $store)
    {
        $this->store = $store->getStore();
    }

    /**
     * @return string
     */
    public function success()
    {
        return ':white_check_mark: All of today\'s Akeneo imports in *' . $this->store->getName()
            . '* have been successfully completed.';
    }

    /**
     * @param Collection $errorLogs
     * @param Collection $processingLogs
     * @return string
     */
    public function warning(Collection $errorLogs = null, Collection $processingLogs = null)
    {
        $message = ':warning: *Warning!* There\'s a problem with today’s Akeneo imports in *' . $this->store->getName()
            . "*.\n\n";

        $message .= $errorLogs
            ? $this->logList($errorLogs, ImportInterface::IMPORT_ERROR)
            : '';

        $message .= $processingLogs
            ? $this->logList($processingLogs, ImportInterface::IMPORT_PROCESSING)
            : '';

        return $message;
    }

    /**
     * @param Collection $logs
     * @param int $status
     * @return string
     */
    protected function logList(Collection $logs, int $status)
    {
        $message = '';
        switch ($status) {
            case $status == ImportInterface::IMPORT_ERROR:
                $message = "The following imports have failed:\n\n";
                break;
            case ImportInterface::IMPORT_PROCESSING:
                $message = "The following imports are still in process:\n\n";
                break;
        }

        foreach ($logs->getData() as $log) {
            $message .= $this->formatList(date('H:i:s', strtotime($log['created_at'])), $log['name']);
        }

        return $message . "\n";
    }

    /**
     * @return string
     */
    public function noImports()
    {
        return $this->warning() . 'No imports have been made today.';
    }

    /**
     * @param string $dateTime
     * @param string $name
     * @return string
     */
    protected function formatList(string $dateTime, string $name)
    {
        return '> •  _' . $dateTime . '_ *' . $name . "*\n\n";
    }
}

