<?php

use Kirby\Cms\App;
use Kirby\Cms\Field;
use Kirby\Cms\Page;

function protectCodeBlocks(string $text, array &$protected): string {
  // Protect HTML <code> tags
  $text = preg_replace_callback('/<code[^>]*>(.*?)<\/code>/s', function($matches) use (&$protected) {
    $key = '###PROTECTED_HTML_' . count($protected) . '###';
    $protected[$key] = $matches[0];
    return $key;
  }, $text);
  
  // Protect triple backtick code blocks
  $text = preg_replace_callback('/```(?:[a-z]*\n)?(.*?)```/s', function($matches) use (&$protected) {
    $key = '###PROTECTED_TRIPLE_' . count($protected) . '###';
    $protected[$key] = $matches[0];
    return $key;
  }, $text);
  
  // Protect single backtick code
  $text = preg_replace_callback('/`([^`]*)`/', function($matches) use (&$protected) {
    $key = '###PROTECTED_SINGLE_' . count($protected) . '###';
    $protected[$key] = $matches[0];
    return $key;
  }, $text);
  
  return $text;
}

function restoreProtectedContent(string $text, array $protected): string {
  foreach ($protected as $key => $value) {
    $text = str_replace($key, $value, $text);
  }
  return $text;
}

function getPluginOptions(): array {
  return [
    'enabled' => option('alienlebarge.handle.enabled', true),
    'maxTextLength' => option('alienlebarge.handle.maxTextLength', 100000),
    'logErrors' => option('alienlebarge.handle.logErrors', true),
    'enableFediverse' => option('alienlebarge.handle.enableFediverse', true),
    'services' => option('alienlebarge.handle.services', [])
  ];
}

function logError(string $message): void {
  $options = getPluginOptions();
  if ($options['logErrors']) {
    error_log("Handle plugin: {$message}");
  }
}

function validateServiceConfig(array $config, string $service): bool {
  $required = ['urlPrefix', 'class'];
  
  // Check if service is disabled
  if (isset($config['enabled']) && !$config['enabled']) {
    return false;
  }
  
  foreach ($required as $field) {
    if (!isset($config[$field]) || !is_string($config[$field]) || empty(trim($config[$field]))) {
      logError("Invalid {$field} for service {$service}");
      return false;
    }
  }
  
  // Enhanced URL validation
  $testUrl = $config['urlPrefix'] . 'test' . ($config['urlSuffix'] ?? '');
  if (!filter_var($testUrl, FILTER_VALIDATE_URL)) {
    logError("Invalid URL format for service {$service}: {$testUrl}");
    return false;
  }
  
  // Validate URL scheme (must be https or http)
  $parsedUrl = parse_url($config['urlPrefix']);
  if (!isset($parsedUrl['scheme']) || !in_array($parsedUrl['scheme'], ['http', 'https'])) {
    logError("Invalid URL scheme for service {$service}. Only http and https are allowed.");
    return false;
  }
  
  // Validate class name (CSS class format)
  if (!preg_match('/^[a-zA-Z][a-zA-Z0-9\-_]*$/', $config['class'])) {
    logError("Invalid CSS class format for service {$service}: {$config['class']}");
    return false;
  }
  
  return true;
}

function sanitizeHandleInput(string $input): string {
  // Remove potentially dangerous characters while preserving Unicode
  $sanitized = preg_replace('/[<>"\']/', '', $input);
  return htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
}

function isValidHandle(string $user, string $instance): bool {
  // Allow Unicode letters, numbers, underscore, dot, hyphen
  if (!preg_match('/^[\p{L}\p{N}_.-]+$/u', $user) || strlen($user) > 64) {
    return false;
  }
  
  // Validate domain format with Unicode support
  if (!preg_match('/^[\p{L}\p{N}.-]+$/u', $instance) || strlen($instance) > 253) {
    return false;
  }
  
  return true;
}

function createHandleLink(string $user, string $instance): string {
  // Validate input
  if (!isValidHandle($user, $instance)) {
    logError("Invalid handle format @{$user}@{$instance}");
    return '@' . htmlspecialchars($user, ENT_QUOTES, 'UTF-8') . '@' . htmlspecialchars($instance, ENT_QUOTES, 'UTF-8');
  }
  
  $options = getPluginOptions();
  $services = $options['services'];
  
  // Check if instance is in configured services
  if (isset($services[$instance])) {
    $config = $services[$instance];
    
    // Validate service configuration
    if (!validateServiceConfig($config, $instance)) {
      // Fallback to generic Fediverse
      return createGenericFediverseLink($user, $instance);
    }
    
    $displayText = isset($config['displayUsername']) && $config['displayUsername'] 
      ? '@' . $user 
      : '@' . $user . '@' . $instance;
      
    $safeUser = sanitizeHandleInput($user);
    $safeInstance = sanitizeHandleInput($instance);
    $safeDisplayText = sanitizeHandleInput($displayText);
    
    return '<a href="' . htmlspecialchars($config['urlPrefix'] . $user . ($config['urlSuffix'] ?? ''), ENT_QUOTES, 'UTF-8') . '" ' .
           'title="@' . $safeUser . '\'s profile on ' . $safeInstance . '" ' .
           'class="handle-link ' . htmlspecialchars($config['class'], ENT_QUOTES, 'UTF-8') . '">' . 
           $safeDisplayText . '</a>';
  } else {
    return createGenericFediverseLink($user, $instance);
  }
}

function createGenericFediverseLink(string $user, string $instance): string {
  $safeUser = sanitizeHandleInput($user);
  $safeInstance = sanitizeHandleInput($instance);
  
  return '<a href="https://' . htmlspecialchars($instance, ENT_QUOTES, 'UTF-8') . '/@' . htmlspecialchars($user, ENT_QUOTES, 'UTF-8') . '" ' .
         'title="@' . $safeUser . '\'s profile on ' . $safeInstance . '" ' .
         'class="handle-link fediverse-link">@' . $safeUser . '</a>';
}

function transformHandles(string $text): string {
  $options = getPluginOptions();
  $services = $options['services'];
  
  // Early return for empty text or no services
  if (empty($text) || empty($services)) {
    return $text;
  }
  
  // Performance optimization: only process if text contains @ symbols
  if (strpos($text, '@') === false) {
    return $text;
  }
  
  // Process specific services first - with Unicode support
  foreach ($services as $domain => $config) {
    if (!validateServiceConfig($config, $domain)) {
      continue; // Skip invalid or disabled configurations
    }
    
    $pattern = '/@([\p{L}\p{N}_.-]+)@' . str_replace('.', '\.', $domain) . '/u';
    
    $text = preg_replace_callback($pattern, function($matches) use ($config, $domain) {
      $user = $matches[1];
      
      // Validate handle before processing
      if (!isValidHandle($user, $domain)) {
        return $matches[0]; // Return original if invalid
      }
      
      $displayText = isset($config['displayUsername']) && $config['displayUsername'] 
        ? '@' . $user 
        : '@' . $user . '@' . $domain;
        
      $safeUser = sanitizeHandleInput($user);
      $safeDomain = sanitizeHandleInput($domain);
      $safeDisplayText = sanitizeHandleInput($displayText);
      
      return '<a href="' . htmlspecialchars($config['urlPrefix'] . $user . ($config['urlSuffix'] ?? ''), ENT_QUOTES, 'UTF-8') . '" ' .
             'title="@' . $safeUser . '\'s profile on ' . $safeDomain . '" ' .
             'class="handle-link ' . htmlspecialchars($config['class'], ENT_QUOTES, 'UTF-8') . '">' . 
             $safeDisplayText . '</a>';
    }, $text);
  }
  
  // Generic processing for Fediverse instances - with Unicode support
  if ($options['enableFediverse']) {
    $text = preg_replace_callback(
      '/@([\p{L}\p{N}_]+)@([\p{L}\p{N}.\-]+)/u',
      function($matches) {
        $user = $matches[1];
        $instance = $matches[2];
        
        // Validate handle before processing
        if (!isValidHandle($user, $instance)) {
          return $matches[0]; // Return original if invalid
        }
        
        return createGenericFediverseLink($user, $instance);
      },
      $text
    );
  }
  
  return $text;
}

function processHandleText(string $text): string {
  $options = getPluginOptions();
  
  // Check if plugin is enabled
  if (!$options['enabled']) {
    return $text;
  }
  
  // Early performance checks
  if (empty($text) || strlen($text) > $options['maxTextLength']) {
    if (strlen($text) > $options['maxTextLength']) {
      logError("Text too large (" . strlen($text) . " chars), skipping processing");
    }
    return $text;
  }
  
  // Performance optimization: only process if text contains @ symbols
  if (strpos($text, '@') === false) {
    return $text;
  }
  
  $protected = [];
  
  // Protect code blocks
  $text = protectCodeBlocks($text, $protected);
  
  // Transform handles
  $text = transformHandles($text);
  
  // Restore protected content
  $text = restoreProtectedContent($text, $protected);
  
  return $text;
}

return [
  'options' => [
    // Global plugin settings
    'alienlebarge.handle.enabled' => true,
    'alienlebarge.handle.maxTextLength' => 100000,
    'alienlebarge.handle.logErrors' => true,
    'alienlebarge.handle.enableFediverse' => true,
    
    // Default service configurations
    'alienlebarge.handle.services' => [
      'bsky.app' => [
        'enabled' => true,
        'urlPrefix' => 'https://bsky.app/profile/',
        'urlSuffix' => '',
        'class' => 'bsky-link',
        'displayUsername' => true,
        'name' => 'Bluesky'
      ],
      'flickr.com' => [
        'enabled' => true,
        'urlPrefix' => 'https://flickr.com/photos/',
        'urlSuffix' => '',
        'class' => 'flickr-link',
        'displayUsername' => true,
        'name' => 'Flickr'
      ],
      'github.com' => [
        'enabled' => true,
        'urlPrefix' => 'https://github.com/',
        'urlSuffix' => '',
        'class' => 'github-link',
        'displayUsername' => true,
        'name' => 'GitHub'
      ],
      'instagram.com' => [
        'enabled' => true,
        'urlPrefix' => 'https://instagram.com/',
        'urlSuffix' => '',
        'class' => 'instagram-link',
        'displayUsername' => true,
        'name' => 'Instagram'
      ],
      'linkedin.com' => [
        'enabled' => true,
        'urlPrefix' => 'https://linkedin.com/in/',
        'urlSuffix' => '',
        'class' => 'linkedin-link',
        'displayUsername' => true,
        'name' => 'LinkedIn'
      ],
      'micro.blog' => [
        'enabled' => true,
        'urlPrefix' => 'https://micro.blog/',
        'urlSuffix' => '',
        'class' => 'microblog-link',
        'displayUsername' => true,
        'name' => 'Micro.blog'
      ],
      'reddit.com' => [
        'enabled' => true,
        'urlPrefix' => 'https://reddit.com/user/',
        'urlSuffix' => '',
        'class' => 'reddit-link',
        'displayUsername' => true,
        'name' => 'Reddit'
      ],
      'youtube.com' => [
        'enabled' => true,
        'urlPrefix' => 'https://youtube.com/',
        'urlSuffix' => '',
        'class' => 'youtube-link',
        'displayUsername' => true,
        'name' => 'YouTube'
      ],
      'vimeo.com' => [
        'enabled' => true,
        'urlPrefix' => 'https://vimeo.com/',
        'urlSuffix' => '',
        'class' => 'vimeo-link',
        'displayUsername' => true,
        'name' => 'Vimeo'
      ],
    ]
  ],
  'fieldMethods' => [
    'handleLinks' => function(Field $field): string {
      $text = $field->value();
      return processHandleText($text);
    }
  ],
  'hooks' => [
    'kirbytext:before' => function(string|null $text): string {
      if ($text === null || !is_string($text)) {
        return '';
      }
      
      return processHandleText($text);
    }
  ],
  'tags' => [
    'handle' => [
      'attr' => [
        'user',
        'instance'
      ],
      'html' => function($tag): string {
        $user = $tag->attr('user');
        $instance = $tag->attr('instance');
        
        if (!$user || !$instance) {
          // If the format is @user@instance in the content - with Unicode support
          $content = $tag->value;
          if (preg_match('/@([\p{L}\p{N}_.-]+)@([\p{L}\p{N}.\-]+)/u', $content, $matches)) {
            $user = $matches[1];
            $instance = $matches[2];
          } else {
            return htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
          }
        }
        
        return createHandleLink($user, $instance);
      }
    ]
  ]
];
