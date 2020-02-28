# ReleaseNotesBundle

### Installation
Check `Resources/config/services.yaml` for example configuration.

### Usage
```
bin/console confluence:send-release-notes <version-tag>
bin/console confluence:send-release-notes v0.0.2
```

First, this command will get all tags from the repository.
Then it will search for the defined version/tag and the tag, which was set just before.
After this, it will get all commits between these two tags.

If you use the first tag in your repo, it will take all commits from the inital commit to the first tag for changelog-creation.

#### Symfony 4.x Configuration 
Make sure to enable the Bundle in `app/config/bundles.php`, e.g. 

```
return [
    \Basilicom\ReleaseNotesBundle\ReleaseNotesBundle::class => ['all' => true],
];
```

#### Symfony 3.x Configuration
Same as above, but the bundle must be added to BundleCollection in `AppKernel.php`, e.g. 

```
if (class_exists('\Basilicom\ReleaseNotesBundle\ReleaseNotesBundle')) {
    $collection->addBundle(new \Basilicom\ReleaseNotesBundle\ReleaseNotesBundle);
}
```
#### Example Configuration 

##### Confluence Publisher
```
Basilicom\ReleaseNotesBundle\Command\ConfluenceReleaseNotesPublisherCommand:    
    public: true
    tags: ['console.command']
    arguments:
        $confluenceUser: 'your-username'
        $confluencePassword: 'your-password'
        $confluenceUrl: 'https://your-confluence.com'
        $pageId: '123'
```
##### Confluence Publisher
You can define as many message parameters as you need.
If you want to provide a dynamic version tag you can append this as a command argument and use the reserved
`version` key to use it. e.g. `release-notes:send-to-rocket-chat v0.2.4`

```
Basilicom\ReleaseNotesBundle\Command\RocketChatReleaseNotesPublisherCommand:
        public: true
        tags: ['console.command']
        arguments:
            $rocketChatUser: '%env(ROCKET_CHAT_USER)%'
            $rocketChatPassword: '%env(ROCKET_CHAT_PASSWORD)%'
            $rocketChatBaseUri: 'https://rocketchat.your-domain.net'
            $rocketChatChannel: 'the rocket chat channel you want to post to'
            $message: "
                Value 1 will be inserted here -> {key1}                       \n
                
                Version: {version}                  \n
                Verantwortlich: #basilicom          \n
                Datum: {date}
            "
            $messageParameters:
                key1: 'value1'
                date: 'd.M.Y H:i:s'
                version: ''

```
    
