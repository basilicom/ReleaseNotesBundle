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

#### Todos
* `APP_ENV` should be a parameter for the command + a valid tag
* the `GitChangelog.sh` should get the starting tag, so that you can run this command for any tag/version 
