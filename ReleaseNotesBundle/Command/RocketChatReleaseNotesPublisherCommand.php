<?php

namespace Basilicom\ReleaseNotesBundle\Command;

use Exception;
use GuzzleHttp\Client as HttpClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RocketChatReleaseNotesPublisherCommand extends Command
{
    /**
     * @var HttpClient
     */
    private $client;

    /**
     * @var string
     */
    private $versionTag;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var string
     */
    private $rocketChatUser;

    /**
     * @var string
     */
    private $rocketChatPassword;

    /**
     * @var string
     */
    private $rocketChatBaseUri;

    private $rocketChatLoginUrl;

    /**
     * @var string
     */
    private $rocketChatChannel;


    /**
     * RocketChatReleaseNotesPublisherCommand constructor.
     *
     * @param string $rocketChatUser
     * @param string $rocketChatPassword
     * @param string $rocketChatBaseUri
     */
    public function __construct(
        string $rocketChatUser,
        string $rocketChatPassword,
        string $rocketChatBaseUri
    ) {
        parent::__construct();
        $this->rocketChatUser = $rocketChatUser;
        $this->rocketChatPassword = $rocketChatPassword;
        $this->rocketChatBaseUri = $rocketChatBaseUri;
    }

    /**
     * @return HttpClient
     */
    private function getClient()
    {
        if (!$this->client) {
            $this->client = new HttpClient(
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'base_uri' => rtrim($this->rocketChatBaseUri, '/') . '/api/v1/',
                    'timeout' => 2,
                ]
            );
        }

        return $this->client;
    }

    protected function configure(): void
    {
        $this
            ->setName('rocketChat:send-release-notes')
            ->setDescription(
                'Creating changelog information based on the difference between two git tags. Sends it to Rocket Chat Channel.'
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->setCredentials();
        $this->authenticateUser();


        return 0;
    }

    public function authenticateUser()
    {
        $loginData = [
            "user" => $this->rocketChatUser,
            "password" => $this->rocketChatPassword,
        ];

        $result = $this->getClient()->request(
            'POST',
            'login',
            [
                'body' => json_encode($loginData),
            ]
        );

        if ($result->getStatusCode() !== 200) {
            $this->output->writeln('Could\'nt establish connection, please check your credentials');
        }
    }

    private function setCredentials()
    {
        $this->rocketChatUser = "upsource-bot";
        $this->rocketChatPassword = "eb_i7F<(sW'T6?yo@E[)n~SNX^";
        $this->rocketChatBaseUri = "https://rocketchat.service.dbrent.net";
    }


    /**
     * @return array
     * @throws Exception
     */
    private function getTicketIds(): array
    {
        $bashPath = dirname(__DIR__) . '/' . basename(__DIR__);
        $command = '/bin/bash ' . $bashPath . '/GitChangelog.sh ' . $this->versionTag . ' | grep -Eo \'([A-Z]{3,}-)([0-9]+)\' | uniq';

        $onlyWebTickets = $this->getCommandOutput($command);
        $onlyWebTickets = array_unique(
            array_filter(
                $onlyWebTickets,
                function ($value) {
                    return !empty($value);
                }
            )
        );

        return $onlyWebTickets;
    }


}

