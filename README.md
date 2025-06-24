# Handle Plugin for Kirby CMS

A Kirby plugin that automatically transforms social handles (like `@user@instance`) into clickable links to their profiles. Supports Unicode characters, custom services, and comprehensive configuration options.

## Installation

### Download

Download and place this plugin in `/site/plugins/handle`.

### Composer

```
composer require alienlebarge/handle
```
### Git

```
git submodule add https://github.com/alienlebarge/handle.git site/plugins/handle
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

Configure the plugin in your `config.php` file:

```php
return [
  // Global plugin settings
  'alienlebarge.handle.enabled' => true,
  'alienlebarge.handle.maxTextLength' => 100000,
  'alienlebarge.handle.logErrors' => true,
  'alienlebarge.handle.enableFediverse' => true,
  
  // Service configurations
  'alienlebarge.handle.services' => [
    'bsky.app' => [
      'enabled' => true,
      'urlPrefix' => 'https://bsky.app/profile/',
      'urlSuffix' => '',
      'class' => 'bsky-link',
      'displayUsername' => true,
      'name' => 'Bluesky'
    ],
    // Add custom services...
  ]
];
```

See [CONFIG.md](CONFIG.md) for detailed configuration options and examples.

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

## Migration from v0.0.3

If upgrading from v0.0.3, update your configuration:

**Before:**
```php
'handle.services' => [
  'github.com' => [
    'urlPrefix' => 'https://github.com/',
    'class' => 'github-link'
  ]
]
```

**After:**
```php
'alienlebarge.handle.services' => [
  'github.com' => [
    'enabled' => true,           // New required field
    'urlPrefix' => 'https://github.com/',
    'class' => 'github-link',
    'displayUsername' => true,   // New optional field
    'name' => 'GitHub'          // New optional field
  ]
]
```

## License

MIT

## Features

- **Automatic Processing**: Transforms handles via `kirbytext:before` hook
- **Unicode Support**: Works with international usernames and domains
- **Security**: XSS protection with input validation and sanitization
- **Performance**: Optimized with early returns and configurable text size limits
- **Extensible**: Easy to add custom services and configure behavior
- **Code Protection**: Preserves handles in code blocks and backticks

## Requirements

- PHP >= 7.4
- Kirby CMS >= 3.0

## Advanced Usage

### In your templates

The plugin works in several ways:

1. **Automatically** (via the `kirbytext:before` hook):
   ```php
   <?= $page->text()->kirbytext() ?>
   ```

2. **Manually** (via the field method):
   ```php
   <?= $page->text()->handleLinks() ?>
   ```

3. **Via KirbyTag**:
   ```
   (handle: @heydon@social.lol)
   ```
   or
   ```
   (handle: user: heydon instance: social.lol)
   ```

### Adding Custom Services

```php
'alienlebarge.handle.services' => [
  'mastodon.social' => [
    'enabled' => true,
    'urlPrefix' => 'https://mastodon.social/@',
    'urlSuffix' => '',
    'class' => 'mastodon-link',
    'displayUsername' => true,
    'name' => 'Mastodon Social'
  ],
  'custom-platform.com' => [
    'enabled' => true,
    'urlPrefix' => 'https://custom-platform.com/users/',
    'urlSuffix' => '/profile',
    'class' => 'custom-platform-link',
    'displayUsername' => false, // Show @username@instance
    'name' => 'Custom Platform'
  ]
]
```

**Service Configuration Options:**
- **`enabled`** (bool): Enable/disable this service
- **`urlPrefix`** (string, required): Base URL for user profiles  
- **`urlSuffix`** (string): Optional suffix added to URLs
- **`class`** (string, required): CSS class for styling links
- **`displayUsername`** (bool): Show only `@username` (true) or `@username@instance` (false)
- **`name`** (string): Human-readable service name for documentation

**Validation Rules:**
- URL Format: Must be valid HTTP/HTTPS URLs
- CSS Classes: Must follow CSS naming conventions (`[a-zA-Z][a-zA-Z0-9\-_]*`)
- Required Fields: `urlPrefix` and `class` are mandatory
