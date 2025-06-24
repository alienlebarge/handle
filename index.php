<?php

use Kirby\Cms\App;
use Kirby\Cms\Field;
use Kirby\Cms\Page;

return [
  'options' => [
    'services' => [
      'bsky.app' => [
        'urlPrefix' => 'https://bsky.app/profile/',
        'urlSuffix' => '',
        'class' => 'bsky-link',
        'displayUsername' => true // Display only @username instead of @username@instance
      ],
      'flickr.com' => [
        'urlPrefix' => 'https://flickr.com/photos/',
        'urlSuffix' => '',
        'class' => 'flickr-link',
        'displayUsername' => true
      ],
      'github.com' => [
        'urlPrefix' => 'https://github.com/',
        'urlSuffix' => '',
        'class' => 'github-link',
        'displayUsername' => true
      ],
      'instagram.com' => [
        'urlPrefix' => 'https://instagram.com/',
        'urlSuffix' => '',
        'class' => 'instagram-link',
        'displayUsername' => true
      ],
      'linkedin.com' => [
        'urlPrefix' => 'https://linkedin.com/in/',
        'urlSuffix' => '',
        'class' => 'linkedin-link',
        'displayUsername' => true
      ],
      'micro.blog' => [
        'urlPrefix' => 'https://micro.blog/',
        'urlSuffix' => '',
        'class' => 'microblog-link',
        'displayUsername' => true
      ],
      'reddit.com' => [
        'urlPrefix' => 'https://reddit.com/user/',
        'urlSuffix' => '',
        'class' => 'reddit-link',
        'displayUsername' => true
      ],
      'youtube.com' => [
        'urlPrefix' => 'https://youtube.com/',
        'urlSuffix' => '',
        'class' => 'youtube-link',
        'displayUsername' => true
      ],
      'vimeo.com' => [
        'urlPrefix' => 'https://vimeo.com/',
        'urlSuffix' => '',
        'class' => 'vimeo-link',
        'displayUsername' => true
      ],
    ]
  ],
  'fieldMethods' => [
    'handleLinks' => function(Field $field): string {
      $text = $field->value();
      $services = option('alienlebarge.handle.services');
      
      // D'abord, protéger le contenu des balises code
      $protected = [];
      $text = preg_replace_callback('/<code>(.*?)<\/code>/s', function($matches) use (&$protected) {
        $key = '###PROTECTED_' . count($protected) . '###';
        $protected[$key] = $matches[0];
        return $key;
      }, $text);
      
      // Ensuite, appliquer les transformations
      foreach ($services as $domain => $config) {
        $pattern = '/@([a-zA-Z0-9_.-]+)@' . str_replace('.', '\.', $domain) . '/';
        
        $displayText = isset($config['displayUsername']) && $config['displayUsername'] 
          ? '@$1' 
          : '@$1@' . $domain;
          
        $replacement = '<a href="' . $config['urlPrefix'] . '$1' . $config['urlSuffix'] . '" ' .
                      'title="@$1\'s profil on ' . $domain . '" ' .
                      'class="handle-link ' . $config['class'] . '">' . 
                      $displayText . '</a>';
                      
        $text = preg_replace($pattern, $replacement, $text);
      }
      
      // Generic processing for Fediverse instances (Mastodon, etc.)
      $text = preg_replace(
        '/@([a-zA-Z0-9_]+)@([a-zA-Z0-9.\-]+)/',
        '<a href="https://$2/@$1" title="@$1\'s profil on $2" class="handle-link fediverse-link">@$1</a>',
        $text
      );
      
      // Enfin, restaurer le contenu protégé
      foreach ($protected as $key => $value) {
        $text = str_replace($key, $value, $text);
      }
      
      return $text;
    }
  ],
  'hooks' => [
    'kirbytext:before' => function(string|null $text): string {
      // Vérifier que $text n'est pas null et est une chaîne
      if ($text === null || !is_string($text)) {
        return '';
      }
      
      // Protéger le contenu entre backticks avant toute transformation
      $protected = [];
      
      // Protéger les blocs de code avec trois backticks
      $text = preg_replace_callback('/```(?:[a-z]*\n)?(.*?)```/s', function($matches) use (&$protected) {
        $key = '###PROTECTED_TRIPLE_' . count($protected) . '###';
        $protected[$key] = $matches[0];
        return $key;
      }, $text);
      
      // Protéger le contenu entre backticks simples
      $text = preg_replace_callback('/`([^`]*)`/', function($matches) use (&$protected) {
        $key = '###PROTECTED_SINGLE_' . count($protected) . '###';
        $protected[$key] = $matches[0];
        return $key;
      }, $text);

      // Appliquer les transformations
      $services = option('alienlebarge.handle.services');
      foreach ($services as $domain => $config) {
        $pattern = '/@([a-zA-Z0-9_.-]+)@' . str_replace('.', '\.', $domain) . '/';
        
        $displayText = isset($config['displayUsername']) && $config['displayUsername'] 
          ? '@$1' 
          : '@$1@' . $domain;
          
        $replacement = '<a href="' . $config['urlPrefix'] . '$1' . $config['urlSuffix'] . '" ' .
                      'title="@$1\'s profil on ' . $domain . '" ' .
                      'class="handle-link ' . $config['class'] . '">' . 
                      $displayText . '</a>';
                      
        $text = preg_replace($pattern, $replacement, $text);
      }
      
      // Generic processing for Fediverse instances (Mastodon, etc.)
      $text = preg_replace(
        '/@([a-zA-Z0-9_]+)@([a-zA-Z0-9.\-]+)/',
        '<a href="https://$2/@$1" title="@$1\'s profil on $2" class="handle-link fediverse-link">@$1</a>',
        $text
      );

      // Restaurer le contenu protégé
      foreach ($protected as $key => $value) {
        $text = str_replace($key, $value, $text);
      }

      return $text;
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
          // If the format is @user@instance in the content
          $content = $tag->value;
          if (preg_match('/@([a-zA-Z0-9_.-]+)@([a-zA-Z0-9.\-]+)/', $content, $matches)) {
            $user = $matches[1];
            $instance = $matches[2];
          } else {
            return $content;
          }
        }
        
        $services = option('alienlebarge.handle.services');
        
        // Check if instance is in configured services
        if (isset($services[$instance])) {
          $config = $services[$instance];
          
          $displayText = isset($config['displayUsername']) && $config['displayUsername'] 
            ? '@' . $user 
            : '@' . $user . '@' . $instance;
            
          return '<a href="' . $config['urlPrefix'] . $user . $config['urlSuffix'] . '" ' .
                 'title="@' . $user . '\'s profil on ' . $instance . '" ' .
                 'class="handle-link ' . $config['class'] . '">' . 
                 $displayText . '</a>';
        } else {
          // Cas par défaut pour Fediverse
          return '<a href="https://' . $instance . '/@' . $user . '" ' .
                 'title="@' . $user . '\'s profil on ' . $instance . '" ' .
                 'class="handle-link fediverse-link">@' . $user . '</a>';
        }
      }
    ]
  ]
];
