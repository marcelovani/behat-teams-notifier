<?php

namespace Marcelovani\Behat\Notifier\Teams;

use Behat\Testwork\EventDispatcher\Event as TestworkEvent;

/**
 * This class sends notification to Microsoft Teams.
 */
class TeamsNotifier
{

    /**
     * Stores extension params.
     */
    private $params;

    /**
     * The url to be used in the POST request.
     */
    private $webhook;

    /**
     * Keeps a list of failed scenarios.
     */
    private $failedScenarios;

    /**
     * Constructor for Teams Notifier.
     */
    public function __construct($params)
    {
        $this->params = $params;
        $this->webhook = $params['webhook'];
    }

    /**
     * Getter for $url.
     */
    public function getWebhook()
    {
        return $this->webhook;
    }

    /**
     * Helper to do the post request.
     *
     * @param string $url
     *   The Webhook.
     * @param string $json
     *   The message to send in json format.
     * @todo Use guzzle.
     */
    private function doRequest($url, $json)
    {
        $ch = curl_init();

        $header = array();
        $header[] = 'Content-type: application/json';

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        curl_exec($ch);
        $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Check response code.
        if ($response_code != 200) {
            $msg = "MS Teams returned invalid response code for webhook $url. Json $json. Response code $response_code.";
            throw new \Exception($msg);
        }
    }

    /**
     * Helper to build the default message.
     *
     * @return array
     *   Array with message.
     */
    public function getDefaultMessage()
    {
        return [
            '@type' => 'MessageCard',
            '@context' => 'http://schema.org/extensions',
            'themeColor' => '',
            'summary' => '',
            'sections' => [
                [
                    'activityTitle' => '',
                    'activitySubtitle' => '',
                    'markdown' => true,
                ]
            ]
        ];
    }

    /**
     * Posts messsage to MS Teams.
     *
     * @param array $message
     *   Array with message parameters.
     */
    private function postMessage(array $message)
    {
        if (empty($message)) {
            return;
        }
        $message = json_encode($message, JSON_PRETTY_PRINT);
        $webhook = $this->getWebhook();
        if (!empty($webhook)) {
            var_dump(__CLASS__, $message);
            $this->doRequest($webhook, $message);
        }
    }

    /**
     * Prepares and sends the notification.
     *
     * @param array $details
     *   The event details.
     */
    public function notify($details)
    {
        $message = [];
        $event = $details['event'];

        // Send notification.
        switch ($details['eventId']) {
            case 'onBeforeSuiteTested';
                $this->failedScenarios = [];
                $message = $this->getSuiteStartMessage();
                break;

            case 'onAfterSuiteTested';
                $message = $this->getSuiteFinishedMessage($event);
                break;

            case 'onAfterScenarioTested';
                if (!$event->getTestResult()->isPassed()) {

                    // Prepare payload.
                    $payload = [
                        'feature_file' => $event->getFeature()->getFile(),
                        'feature' => $event->getFeature()->getTitle(),
                        'description' => $event->getFeature()->getDescription(),
                        'scenario' => $event->getScenario()->getTitle(),
                        'line' => $event->getScenario()->getLine(),
                    ];

                    /** @var Behat\Gherkin\Node\StepNode $step */
                    foreach ($event->getScenario()->getSteps() as $step) {
                        $payload['steps'][] = $step->getKeyword() . ' ' . $step->getText();
                    }

                    // Attach screenshots.
                    if (!empty($details['screenshotService'])) {
                        $screenshotService = $details['screenshotService'];
                        $files = $screenshotService->getImages();
                        if (!empty($files)) {
                            array_reverse($files);
                        }
                        $payload['screenshots'] = $files;
                    }

                    $message = $this->getFailedScenarioMessage($payload);
                    $this->failedScenarios[] = $payload['feature'];
                }
                break;

            default:
                var_dump("Event $event is not implemented yet.");
        }

        $this->postMessage($message);
    }

    /**
     * Send notification when suite starts.
     *
     * @return array
     *   The message array.
     */
    public function getSuiteStartMessage()
    {
        $message = $this->getDefaultMessage();
        $message['summary'] = "Automation job started";
        $message['sections'][0]['activityTitle'] = $message['summary'];
        $message['themeColor'] = '#ff9933';

        return $message;
    }

    /**
     * Send notification when suite finishes.
     *
     * @param TestworkEvent\SuiteTested $event
     *   The suite event.
     *
     * @return array
     *   The message array.
     */
    public function getSuiteFinishedMessage(TestworkEvent\SuiteTested $event)
    {
        $message = $this->getDefaultMessage();
        $message['summary'] = "Automation job finished";
        $message['sections'][0]['activityTitle'] = $message['summary'];

        // Check for failed scenarios.
        if (!empty($this->failedScenarios)) {
            $message['themeColor'] = '#ff0000';
            $message['sections'][0]['facts'][] = [
                'name' => 'Outcome',
                'value' => 'Failed',
            ];
            foreach ($this->failedScenarios as $item) {
                $message['sections'][0]['facts'][] = [
                    'name' => 'Feature',
                    'value' => $item,
                ];
            }
        } else {
            $message['themeColor'] = '#00ff00';
            $message['sections'][0]['facts'][] = [
                'name' => 'Outcome',
                'value' => 'Passed',
            ];
        }

        return $message;
    }

    /**
     * Send notification when scenarios fail.
     *
     * @param $payload
     *   The payload.
     * @return array
     *   The message array.
     */
    public function getFailedScenarioMessage($payload)
    {
        $message = $this->getDefaultMessage();
        $message['summary'] = "Scenario failed";
        $message['sections'][0]['activityTitle'] = $payload['feature'];
        $message['sections'][0]['activitySubtitle'] = $payload['description'];
        $message['sections'][0]['activityImage'] = 'https://uxwing.com/wp-content/themes/uxwing/download/education-school/failed-icon.png';
        $message['themeColor'] = '#ff0000';
        $message['sections'][0]['facts'][] = [
            'name' => 'Feature file',
            'value' => $payload['feature_file'] . '. Line ' . $payload['line'],
        ];

        return $message;
    }
}
