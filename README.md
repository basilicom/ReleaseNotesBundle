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