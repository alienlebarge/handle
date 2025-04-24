# Handle Plugin for Kirby CMS

This plugin automatically transforms social handles (like `@user@instance`) into links to their corresponding profiles.

## Installation

### Download

Download and place this plugin in `/site/plugins/handle`.

### Composer

```
composer require alienlebarge/handle
```

## Usage

### Field Method

```php
<?= $page->text()->kirbytext()->handleLinks() ?>
```

### Automatic

The plugin automatically processes all texts passed through kirbytext.

### KirbyTag

```
(handle: @user@instance)
```

or

```
(handle: user: username instance: bsky.app)
```

## Configuration

You can configure the services in your `config.php` file:

```php
return [
  'handle.services' => [
    'bsky.app' => [
      'urlPrefix' => 'https://bsky.app/profile/',
      'urlSuffix' => '',
      'class' => 'bsky-link'
    ],
    // Other services...
  ]
];
```

## Default Supported Services

- Fediverse (Mastodon, etc.)
- Bluesky (bsky.app)
- Flickr
- GitHub
- Instagram
- LinkedIn
- Micro.blog
- Reddit
- YouTube
- Vimeo

## License

MIT

## Usage

### In your templates

You can use the plugin in several ways:

1. **Automatically** (via the kirbytags:after hook):
   ```php
   <?= $page->text()->kirbytext() ?>
   ```

2. **Manually** (via the field method):
   ```php
   <?= $page->text()->kirbytext()->handleLinks() ?>
   ```

3. **Via KirbyTag**:
   ```
   (handle: @heydon@social.lol)
   ```
   or
   ```
   (handle: user: heydon instance: social.lol)
   ```
