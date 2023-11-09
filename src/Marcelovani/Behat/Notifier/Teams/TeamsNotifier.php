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
            $msg = "MS Teams returned invalid response code for webhook $url. Response code $response_code.";
            var_dump($msg);
            throw new Exception($msg);
        }
    }




}
