<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Kirby\Content\Field;

class HandleTest extends TestCase
{
    protected $services;

    protected function setUp(): void
    {
        // Configuration des services pour les tests
        $this->services = [
            'bsky.app' => [
                'urlPrefix' => 'https://bsky.app/profile/',
                'urlSuffix' => '',
                'class' => 'bsky-link',
                'displayUsername' => true
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
            'social.lol' => [
                'urlPrefix' => 'https://social.lol/@',
                'urlSuffix' => '',
                'class' => 'fediverse-link',
                'displayUsername' => true
            ]
        ];
    }

    protected function transformHandle($text)
    {
        $result = $text;
        
        // Processing for specific services
        foreach ($this->services as $domain => $config) {
            $pattern = '/@([a-zA-Z0-9_.-]+)@' . str_replace('.', '\.', $domain) . '/';
            
            $displayText = isset($config['displayUsername']) && $config['displayUsername'] 
                ? '@$1' 
                : '@$1@' . $domain;
                
            $replacement = '<a href="' . $config['urlPrefix'] . '$1' . $config['urlSuffix'] . '" ' .
                          'title="@$1\'s profil on ' . $domain . '" ' .
                          'class="handle-link ' . $config['class'] . '">' . 
                          $displayText . '</a>';
                          
            $result = preg_replace($pattern, $replacement, $result);
        }
        
        // Generic processing for Fediverse instances (Mastodon, etc.)
        $result = preg_replace(
            '/@([a-zA-Z0-9_]+)@([a-zA-Z0-9.\-]+)/',
            '<a href="https://$2/@$1" title="@$1\'s profil on $2" class="handle-link fediverse-link">@$1</a>',
            $result
        );
        
        return $result;
    }

    public function testBlueskyHandle()
    {
        $text = '@alienlebarge@bsky.app';
        $result = $this->transformHandle($text);
        $expected = '<a href="https://bsky.app/profile/alienlebarge" title="@alienlebarge\'s profil on bsky.app" class="handle-link bsky-link">@alienlebarge</a>';
        $this->assertEquals($expected, $result);
    }

    public function testFlickrHandle()
    {
        $text = '@alienlebarge@flickr.com';
        $result = $this->transformHandle($text);
        $expected = '<a href="https://flickr.com/photos/alienlebarge" title="@alienlebarge\'s profil on flickr.com" class="handle-link flickr-link">@alienlebarge</a>';
        $this->assertEquals($expected, $result);
    }

    public function testGithubHandle()
    {
        $text = '@alienlebarge@github.com';
        $result = $this->transformHandle($text);
        $expected = '<a href="https://github.com/alienlebarge" title="@alienlebarge\'s profil on github.com" class="handle-link github-link">@alienlebarge</a>';
        $this->assertEquals($expected, $result);
    }

    public function testInstagramHandle()
    {
        $text = '@alienlebarge@instagram.com';
        $result = $this->transformHandle($text);
        $expected = '<a href="https://instagram.com/alienlebarge" title="@alienlebarge\'s profil on instagram.com" class="handle-link instagram-link">@alienlebarge</a>';
        $this->assertEquals($expected, $result);
    }

    public function testLinkedinHandle()
    {
        $text = '@alienlebarge@linkedin.com';
        $result = $this->transformHandle($text);
        $expected = '<a href="https://linkedin.com/in/alienlebarge" title="@alienlebarge\'s profil on linkedin.com" class="handle-link linkedin-link">@alienlebarge</a>';
        $this->assertEquals($expected, $result);
    }

    public function testMicroBlogHandle()
    {
        $text = '@alienlebarge@micro.blog';
        $result = $this->transformHandle($text);
        $expected = '<a href="https://micro.blog/alienlebarge" title="@alienlebarge\'s profil on micro.blog" class="handle-link microblog-link">@alienlebarge</a>';
        $this->assertEquals($expected, $result);
    }

    public function testRedditHandle()
    {
        $text = '@alienlebarge@reddit.com';
        $result = $this->transformHandle($text);
        $expected = '<a href="https://reddit.com/user/alienlebarge" title="@alienlebarge\'s profil on reddit.com" class="handle-link reddit-link">@alienlebarge</a>';
        $this->assertEquals($expected, $result);
    }

    public function testYoutubeHandle()
    {
        $text = '@alienlebarge@youtube.com';
        $result = $this->transformHandle($text);
        $expected = '<a href="https://youtube.com/alienlebarge" title="@alienlebarge\'s profil on youtube.com" class="handle-link youtube-link">@alienlebarge</a>';
        $this->assertEquals($expected, $result);
    }

    public function testVimeoHandle()
    {
        $text = '@alienlebarge@vimeo.com';
        $result = $this->transformHandle($text);
        $expected = '<a href="https://vimeo.com/alienlebarge" title="@alienlebarge\'s profil on vimeo.com" class="handle-link vimeo-link">@alienlebarge</a>';
        $this->assertEquals($expected, $result);
    }

    public function testFediverseHandle()
    {
        $text = '@alienlebarge@social.lol';
        $result = $this->transformHandle($text);
        $expected = '<a href="https://social.lol/@alienlebarge" title="@alienlebarge\'s profil on social.lol" class="handle-link fediverse-link">@alienlebarge</a>';
        $this->assertEquals($expected, $result);
    }

    public function testMastodonSocialHandle()
    {
        $text = '@alienlebarge@mastodon.social';
        $result = $this->transformHandle($text);
        $expected = '<a href="https://mastodon.social/@alienlebarge" title="@alienlebarge\'s profil on mastodon.social" class="handle-link fediverse-link">@alienlebarge</a>';
        $this->assertEquals($expected, $result);
    }

    public function testMastodonXyzHandle()
    {
        $text = '@alienlebarge@mastodon.xyz';
        $result = $this->transformHandle($text);
        $expected = '<a href="https://mastodon.xyz/@alienlebarge" title="@alienlebarge\'s profil on mastodon.xyz" class="handle-link fediverse-link">@alienlebarge</a>';
        $this->assertEquals($expected, $result);
    }

    public function testMasToHandle()
    {
        $text = '@alienlebarge@mas.to';
        $result = $this->transformHandle($text);
        $expected = '<a href="https://mas.to/@alienlebarge" title="@alienlebarge\'s profil on mas.to" class="handle-link fediverse-link">@alienlebarge</a>';
        $this->assertEquals($expected, $result);
    }

    public function testSocialVivaldiHandle()
    {
        $text = '@alienlebarge@social.vivaldi.net';
        $result = $this->transformHandle($text);
        $expected = '<a href="https://social.vivaldi.net/@alienlebarge" title="@alienlebarge\'s profil on social.vivaldi.net" class="handle-link fediverse-link">@alienlebarge</a>';
        $this->assertEquals($expected, $result);
    }

    public function testMultipleHandlesInText()
    {
        $text = "Voici mes handles: @alienlebarge@bsky.app, @alienlebarge@github.com et @alienlebarge@social.lol";
        $result = $this->transformHandle($text);
        
        $this->assertStringContainsString('href="https://bsky.app/profile/alienlebarge"', $result);
        $this->assertStringContainsString('href="https://github.com/alienlebarge"', $result);
        $this->assertStringContainsString('href="https://social.lol/@alienlebarge"', $result);
    }

    public function testInvalidHandleFormat()
    {
        $text = 'This is not a valid handle';
        $result = $this->transformHandle($text);
        $this->assertEquals($text, $result);
    }

    public function testHandleWithSpecialCharacters()
    {
        $text = '@alien-lebarge@social.lol';
        $result = $this->transformHandle($text);
        $expected = '<a href="https://social.lol/@alien-lebarge" title="@alien-lebarge\'s profil on social.lol" class="handle-link fediverse-link">@alien-lebarge</a>';
        $this->assertEquals($expected, $result);
    }
} 
