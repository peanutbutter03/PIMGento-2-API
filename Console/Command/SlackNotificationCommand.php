<?php

namespace Pimgento\Api\Console\Command;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Pimgento\Api\Helper\SlackHelper;
use Pimgento\Api\Job\SlackMessage;
use Pimgento\Api\Api\Data\ImportInterface;
use Pimgento\Api\Model\ResourceModel\Log;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SlackNotificationCommand
 * @package Pimgento\Api\Console\Command
 */
class SlackNotificationCommand extends Command
{
    protected $client;
    protected $helperData;
    protected $logCollection;
    protected $logs;
    protected $slackMessage;

    public function __construct(Client $client, Data $helperData, Log\Collection $logCollection, SlackMessage $slackMessage, $name = null)
    {
        $this->client = $client;
        $this->helperData = $helperData;
        $this->logCollection = $logCollection;
        $this->logs = $this->getLogs();
        $this->slackMessage = $slackMessage;


        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('slack:imports');
        $this->setDescription(
            'This will check the status of today\'s Pimgento imports and send the results as a notification message to Slack'
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln($this->send($this->getMessage()));
    }

    /**
     * Gets a collection of all Import logs of today
     * @return Log\Collection
     */
    protected function getLogs()
    {
        return $this->logCollection
            ->addFieldToFilter('created_at', ['gteq' => date('Y-m-d')])
            ->addFieldToFilter('created_at', ['lt' => date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day'))]);
    }

    /**
     * Gets a collection of all Import logs of today with a specific status
     * @param int $status
     * @return Log\Collection
     */
    protected function getLogsByStatus(int $status)
    {
        $logs = clone $this->logs;

        return $logs->addFieldToFilter('status', $status);
    }

    /**
     * Checks if a log with a specific status exists in the collection
     * @param int $status
     * @return bool
     */
    protected function checkLogStatus(int $status)
    {
        foreach ($this->logs as $log) {
            if ($log->getStatus() == $status) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the message to be sent
     * @return string
     */
    protected function getMessage()
    {
        if (!$this->logs->getData()) {

            return $this->slackMessage->noImports();
        } elseif ($this->checkLogStatus(ImportInterface::IMPORT_ERROR) ||
            $this->checkLogStatus(ImportInterface::IMPORT_PROCESSING)) {

            return $this->slackMessage->warning(
                $this->getLogsByStatus(ImportInterface::IMPORT_ERROR),
                $this->getLogsByStatus(ImportInterface::IMPORT_PROCESSING)
            );
        }

        return $this->slackMessage->success();
    }

    /**
     * Sends the message to Slack
     * @param string $message
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function send(string $message)
    {
        try {
            $this->client->request('POST', 'https://slack.com/api/chat.postMessage', [
                'form_params' => [
                    'token' => $this->helperData->getGeneralConfig('slack_token'),
                    'channel' => $this->helperData->getGeneralConfig('slack_channel'),
                    'text' => $message,
                    'username' => $this->helperData->getGeneralConfig('slack_username')
                ]]);

            return '<info>✅ Message has been send to Slack channel: '
                . $this->helperData->getGeneralConfig('slack_channel') . '</info>';

        } catch (RequestException $e) {
            $response =
                '<fg=red>⚠️  There\'s a problem with sending the message to Slack channel: '
                . $this->helperData->getGeneralConfig('slack_channel') . " \n\n"
                . 'The following exception appeared:</>'
                . '<error>' . "\n\n" . $e->getResponse() . '</error>';

            return $response;
        }
    }
}
