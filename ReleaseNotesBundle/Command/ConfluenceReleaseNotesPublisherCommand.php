<?php

namespace Basilicom\ReleaseNotesBundle\Command;

use Exception;
use GuzzleHttp\Client as HttpClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfluenceReleaseNotesPublisherCommand extends Command
{
    private const HTTP_OK = 200;
    private const INPUT_PARAM_VERSION_TAG = 'version-tag';

    /**
     * @var string
     */
    private $pageTitle = '';

    /**
     * @var string
     */
    private $body = '';

    /**
     * @var string
     */
    private $pageId = '';

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
    private $confluenceUser;

    /**
     * @var string
     */
    private $confluencePassword;

    /**
     * @var string
     */
    private $confluenceUrl;

    /**
     * @param string $confluenceUser
     * @param string $confluencePassword
     * @param string $confluenceUrl
     * @param string $pageId
     */
    public function __construct(
        string $confluenceUser,
        string $confluencePassword,
        string $confluenceUrl,
        string $pageId
    ) {
        parent::__construct();
        $this->confluenceUser = $confluenceUser;
        $this->confluencePassword = $confluencePassword;
        $this->confluenceUrl = $confluenceUrl;
        $this->pageId = $pageId;
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
                    'auth' => [
                        $this->confluenceUser,
                        $this->confluencePassword,
                    ],
                    'base_uri' => rtrim($this->confluenceUrl, '/') . '/rest/api/content/',
                    'timeout' => 2,
                ]
            );
        }

        return $this->client;
    }

    protected function configure(): void
    {
        $this
            ->setName('confluence:send-release-notes')
            ->setDescription(
                'Creating changelog information based on the difference between two git tags. Writes it to a confluence page.'
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
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->versionTag = $input->getArgument(self::INPUT_PARAM_VERSION_TAG);

        $this->retrieveDocumentInformation();
        if (stripos($this->body, $this->versionTag) === false) {
            $this->updateDocumentContent();
            $this->preparePayloadAndSendToConfluence();
        } else {
            $output->writeln('The app version is already part of the changelog.');
        }

        return 0;
    }

    /**
     * @param string $command
     *
     * @return array
     */
    private function getCommandOutput(string $command): array
    {
        return explode(PHP_EOL, shell_exec($command));
    }

    /**
     * @return string
     * @throws Exception
     */
    private function extractAndPrepareWholeChangelog(): string
    {
        $bashPath = dirname(__DIR__) . '/' . basename(__DIR__);
        $command = '/bin/bash ' . $bashPath . '/GitChangelog.sh ' . $this->versionTag;
        $fileContents = $this->getCommandOutput($command);

        $content = '';
        foreach ($fileContents as $commitMessage) {
            $content .= '<li>' . htmlspecialchars($commitMessage) . '</li>';
        }

        return '
            <ac:structured-macro ac:name="expand" ac:schema-version="1">
                <ac:parameter ac:name= "title">Gesamtes Changelog einblenden</ac:parameter>    
                    <ac:rich-text-body>
                        <ul>' . $content . '</ul>              
                    </ac:rich-text-body>
            </ac:structured-macro>';
    }


    /**
     * @return int
     *
     * @throws Exception
     */
    private function getNextDocumentVersion(): int
    {
        $response = $this->getClient()->request('GET', $this->pageId);

        if ($response->getStatusCode() !== self::HTTP_OK) {
            throw new Exception('Could not get response');
        }

        $content = json_decode((string)$response->getBody(), true);

        return (int)$content['version']['number'] + 1;
    }

    /**
     * Retrieves page title and body
     *
     * @throws Exception
     */
    private function retrieveDocumentInformation()
    {
        $response = $this->getClient()->request('GET', $this->pageId . '?expand=body.storage');
        if ($response->getStatusCode() !== self::HTTP_OK) {
            throw new Exception('Could not get response');
        }

        $content = json_decode((string)$response->getBody(), true);

        $this->pageTitle = (string)$content['title'];
        $this->body = (string)$content['body']['storage']['value'];
    }

    /**
     * @throws Exception
     */
    private function updateDocumentContent()
    {
        $headline = '<h2>' . $this->versionTag . ' - ' . date('Y-m-d H:i', time()) . '</h2>';
        $ticketList = '<ul>';
        $extractedTickets = $this->getTicketIds();
        foreach ($extractedTickets as $ticket) {
            $ticketList .= '
            <li>
                <ac:structured-macro ac:name="jira" ac:schema-version="1">
                    <ac:parameter ac:name="server">Jira f&uuml;r DB Connect</ac:parameter>
                    <ac:parameter ac:name="key">' . $ticket . '</ac:parameter>
                </ac:structured-macro>                  
            </li>';
        }
        $ticketList .= '</ul>';
        $changelog = $this->extractAndPrepareWholeChangelog();

        $body = '<p>';
        $body .= $headline;
        $body .= $ticketList;
        $body .= '</p>';
        $body .= $changelog;
        $body .= $this->body;

        $this->body = $body;
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

    /**
     * @throws Exception
     */
    private function preparePayloadAndSendToConfluence()
    {
        $payload = [
            'id' => $this->pageId,
            'type' => 'page',
            'title' => $this->pageTitle,
            'body' => [
                'storage' => [
                    'value' => $this->body,
                    'representation' => 'storage',
                ],
            ],
            'version' => [
                'number' => $this->getNextDocumentVersion(),
            ],
        ];

        $response = $this->getClient()->request(
            'PUT',
            $this->pageId,
            [
                'body' => json_encode($payload),
            ]
        );

        if ($response->getStatusCode() !== self::HTTP_OK) {
            $this->output->writeln('Could not send new version.');
        }
    }
}

