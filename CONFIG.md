# Configuration Guide

This document explains how to configure the Handle plugin for Kirby CMS.

## Global Settings

You can configure the plugin globally in your `config.php` file:

```php
return [
  // Enable/disable the entire plugin
  'alienlebarge.handle.enabled' => true,
  
  // Maximum text length to process (in characters)
  'alienlebarge.handle.maxTextLength' => 100000,
  
  // Enable/disable error logging
  'alienlebarge.handle.logErrors' => true,
  
  // Enable/disable generic Fediverse support
  'alienlebarge.handle.enableFediverse' => true,
];
```

## Service Configuration

### Disabling Services

To disable specific services:

```php
return [
  'alienlebarge.handle.services' => [
    'github.com' => [
      'enabled' => false, // Disable GitHub handles
    ],
    'instagram.com' => [
      'enabled' => false, // Disable Instagram handles
    ],
  ],
];
```

### Adding Custom Services

To add a new service or override existing ones:

```php
return [
  'alienlebarge.handle.services' => [
    'mastodon.social' => [
      'enabled' => true,
      'urlPrefix' => 'https://mastodon.social/@',
      'urlSuffix' => '',
      'class' => 'mastodon-link',
      'displayUsername' => true, // Show only @username
      'name' => 'Mastodon Social'
    ],
    'custom-platform.com' => [
      'enabled' => true,
      'urlPrefix' => 'https://custom-platform.com/users/',
      'urlSuffix' => '/profile',
      'class' => 'custom-platform-link',
      'displayUsername' => false, // Show @username@instance
      'name' => 'Custom Platform'
    ],
  ],
];
```

### Configuration Options

Each service supports these configuration options:

- **`enabled`** (bool): Enable/disable this service
- **`urlPrefix`** (string, required): Base URL for user profiles
- **`urlSuffix`** (string): Optional suffix added to URLs
- **`class`** (string, required): CSS class for styling links
- **`displayUsername`** (bool): Show only `@username` (true) or `@username@instance` (false)
- **`name`** (string): Human-readable service name for documentation

### Validation Rules

The plugin validates configurations and will skip invalid services:

- **URL Format**: Must be valid HTTP/HTTPS URLs
- **CSS Classes**: Must follow CSS naming conventions (`[a-zA-Z][a-zA-Z0-9\-_]*`)
- **Required Fields**: `urlPrefix` and `class` are mandatory

## Examples

### Minimal Configuration

```php
return [
  // Only enable GitHub and disable everything else
  'alienlebarge.handle.enableFediverse' => false,
  'alienlebarge.handle.services' => [
    'github.com' => [
      'enabled' => true,
    ],
    // All other services will be disabled by default
  ],
];
```

### Custom Fediverse Instance

```php
return [
  'alienlebarge.handle.services' => [
    'fosstodon.org' => [
      'enabled' => true,
      'urlPrefix' => 'https://fosstodon.org/@',
      'urlSuffix' => '',
      'class' => 'fosstodon-link',
      'displayUsername' => true,
      'name' => 'Fosstodon'
    ],
  ],
];
```

### Performance Tuning

```php
return [
  // Reduce max text length for better performance
  'alienlebarge.handle.maxTextLength' => 50000,
  
  // Disable error logging in production
  'alienlebarge.handle.logErrors' => false,
];
```

## Usage

Once configured, the plugin will automatically transform handles in:

1. **Field Methods**: `$field->handleLinks()`
2. **KirbyText**: Automatic processing via `kirbytext:before` hook
3. **Tags**: `(handle: @user@instance)` or `(handle: user:username instance:domain)`

## Troubleshooting

- Check error logs if handles aren't being transformed
- Ensure service configurations are valid
- Verify that the plugin is enabled globally
- Test with a small text sample first