<?php

namespace Basilicom\ReleaseNotesBundle\Command;

use Exception;
use GuzzleHttp\Client as HttpClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReleaseNotesPublisherCommand extends Command
{
    public const HTTP_OK = 200;

    /**
     * @var HttpClient
     */
    private $client;

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
        $this->client = new HttpClient([
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'auth' => [
                $confluenceUser,
                $confluencePassword,
            ],
            'base_uri' => rtrim($confluenceUrl, '/') . '/rest/api/content/',
            'timeout' => 2,
        ]);
        $this->pageId = $pageId;
    }

    protected function configure(): void
    {
        $this->setName('confluence:send-release-notes')
            ->setDescription('Send Version and Changelog of actual Release to Confluence');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $appVersion = getenv('APP_VERSION');

        $this->retrieveDocumentInformation();

        if (stripos($this->body, $appVersion) === true) {
            $tickets = $this->extractTickets();
            $completeChangelog = $this->extractAndPrepareWholeChangelog();
            $this->updateDocumentContent($appVersion, $tickets, $completeChangelog);
            $this->preparePayloadAndSendToConfluence();
        }
    }

    /**
     * @param $fileName
     *
     * @return array
     * @throws Exception
     */
    private function extractTickets(): array
    {
        $onlyWebTickets = explode(PHP_EOL,
            shell_exec('/bin/bash ' . dirname(__DIR__) . '/' . basename(__DIR__) . '/GitChangelog.sh | grep -Eo \'([A-Z]{3,}-)([0-9]+)\' | uniq'));
        $onlyWebTickets = array_unique(array_filter($onlyWebTickets, function ($value) {
                return stripos($value, 'web-0000') === false && !empty($value);
            })
        );

        return $onlyWebTickets;
    }

    /**
     * @param $fileName
     *
     * @return string
     * @throws Exception
     */
    private function extractAndPrepareWholeChangelog(): string
    {
        $content = '';
        $fileContents = explode(PHP_EOL,
            shell_exec('/bin/bash ' . dirname(__DIR__) . '/' . basename(__DIR__) . '/GitChangelog.sh '));

        foreach ($fileContents as $commit) {
            $content .= '<li>' . $commit . '</li>';
        }
        $body = '
            <ac:structured-macro ac:name="expand" ac:schema-version="1">
                <ac:parameter ac:name= "title">Gesamtes Changelog einblenden</ac:parameter>    
                    <ac:rich-text-body>
                        <ul>' . $content . '</ul>              
                    </ac:rich-text-body>
            </ac:structured-macro>';

        return $body;
    }


    /**
     * @return int
     *
     * @throws Exception
     */
    private function getNextDocumentVersion(): int
    {
        $response = $this->client->request('GET', $this->pageId);

        if ($response->getStatusCode() !== self::HTTP_OK) {
            throw new Exception('Could not get response');
        }

        $content = json_decode((string) $response->getBody(), true);

        return (int) $content['version']['number'] + 1;
    }

    /**
     * Retrieves page title and body
     *
     * @throws Exception
     */
    private function retrieveDocumentInformation()
    {
        $response = $this->client->request('GET', $this->pageId . '?expand=body.storage');
        if ($response->getStatusCode() !== self::HTTP_OK) {
            throw new Exception('Could not get response');
        }

        $content = json_decode((string) $response->getBody(), true);

        $this->pageTitle = (string) $content['title'];
        $this->body = (string) $content['body']['storage']['value'];
    }

    /**
     * @param string $appVersion
     * @param array  $tickets
     * @param string $completeChangelog
     */
    private function updateDocumentContent(string $appVersion, array $tickets, string $completeChangelog)
    {
        $ticketList = '<ul>';
        foreach (array_unique($tickets) as $ticket) {
            if (strtoupper($ticket) !== 'WEB-0000') {
                $ticketList .= '
                <li>
                    <ac:structured-macro ac:name="jira" ac:schema-version="1">
                        <ac:parameter ac:name="server">Jira f&uuml;r DB Connect</ac:parameter>
                        <ac:parameter ac:name="key">' . $ticket . '</ac:parameter>
                    </ac:structured-macro>                  
                </li>';
            }
        }
        $ticketList .= '</ul>';

        $this->body = '<p><h2>' . $appVersion . '</h2>' . $ticketList . '</p>' . $completeChangelog . $this->body;
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

        $response = $this->client->request('PUT', $this->pageId, [
            'body' => json_encode($payload),
        ]);

        if ($response->getStatusCode() !== self::HTTP_OK) {
            throw new Exception('Could not send new version');
        }
    }
}

