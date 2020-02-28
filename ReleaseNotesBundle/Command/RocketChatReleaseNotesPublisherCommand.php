<?php

namespace Basilicom\ReleaseNotesBundle\Command;

use DateTime;
use Exception;
use GuzzleHttp\Client as HttpClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RocketChatReleaseNotesPublisherCommand extends Command
{
    private const HTTP_OK = 200;
    private const INPUT_PARAM_VERSION_TAG = 'version-tag';

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

    /**
     * @var string
     */
    private $rocketChatChannel;

    /**
     * @var string
     */
    private $userId;

    /**
     * @var string
     */
    private $authToken;
    /**
     * @var string
     */
    private $message;

    /**
     * @var array
     */
    private $parameters = [];


    /**
     * RocketChatReleaseNotesPublisherCommand constructor.
     *
     * @param string $rocketChatUser
     * @param string $rocketChatPassword
     * @param string $rocketChatBaseUri
     * @param string $rocketChatChannel
     * @param string $message
     * @param array  $messageParameters
     */
    public function __construct(
        string $rocketChatUser,
        string $rocketChatPassword,
        string $rocketChatBaseUri,
        string $rocketChatChannel,
        string $message,
        array $messageParameters
    ) {
        parent::__construct();
        $this->rocketChatUser = $rocketChatUser;
        $this->rocketChatPassword = $rocketChatPassword;
        $this->rocketChatBaseUri = $rocketChatBaseUri;
        $this->rocketChatChannel = $rocketChatChannel;
        $this->message = $message;
        $this->parameters = $messageParameters;
    }

    /**
     * @return HttpClient
     */
    private function getClient(): HttpClient
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
            ->setName('release-notes:send-to-rocket-chat')
            ->setDescription(
                'Provides changelog information based on the difference between two git tags. Sends it to Rocket Chat Channel.'
            )
            ->addArgument(self::INPUT_PARAM_VERSION_TAG, InputArgument::REQUIRED, 'The git version-rag');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->versionTag = $input->getArgument(self::INPUT_PARAM_VERSION_TAG);

        $this->authenticateUser();
        $this->createMessage();
        $this->postToChannel();

        return 0;
    }

    /**
     * User Authentication, User stays logged in for 300 sec after that call.
     */
    private function authenticateUser(): void
    {
        $loginData = [
            'user' => $this->rocketChatUser,
            'password' => $this->rocketChatPassword,
        ];

        $response = $this->getClient()->request(
            'POST',
            'login',
            [
                'body' => json_encode($loginData),
            ]
        );

        if ($response->getStatusCode() !== self::HTTP_OK) {
            $this->output->writeln('Could\'nt establish connection, please check your credentials');
        } else {
            $responseBody = (array)json_decode($response->getBody()->getContents(), true);

            $this->userId = $responseBody['data']['userId'];
            $this->authToken = $responseBody['data']['authToken'];
        }
    }


    private function postToChannel(): void
    {
        $payload = [
            'channel' => '#' . $this->rocketChatChannel,
            'text' => $this->message,
        ];

        $response = $this->getClient()->request(
            'POST',
            'chat.postMessage',
            [
                'headers' => [
                    'X-User-Id' => $this->userId,
                    'X-Auth-Token' => $this->authToken,
                ],
                'body' => json_encode($payload),
            ]
        );

        if ($response->getStatusCode() !== self::HTTP_OK) {
            $this->output->writeln('Could\'nt establish connection, please check your credentials');
        }
    }

    private function createMessage()
    {
        foreach ($this->parameters as $parameterName => $parameterValue) {
            if ($parameterName == 'date') {
                $time = new DateTime('now');
                $parameterValue = $time->format($parameterValue);
            }
            if ($parameterName == 'version' && $this->versionTag != '') {
                $parameterValue = $this->versionTag;
            }

            $this->message = str_replace('{' . $parameterName . '}', $parameterValue, $this->message);
        }
    }
}

