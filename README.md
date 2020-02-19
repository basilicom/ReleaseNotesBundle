# ReleaseNotesBundle

### Installation
Check `Resources/config/services.yaml` for example configuration.

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
    $collection->addBundle(new \Basilicom\ReleaseNotesBundle\ReleaseNotesBundle());
}
```

#### Todos
* `APP_ENV` should be a parameter for the command + a valid tag
* the `GitChangelog.sh` should get the starting tag, so that you can run this command for any tag/version 
