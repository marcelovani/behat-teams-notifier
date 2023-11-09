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





}
