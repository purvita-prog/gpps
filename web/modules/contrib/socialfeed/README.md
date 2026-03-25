# Socialfeed Module

Displays social media feeds from Facebook, X (formerly Twitter), and Instagram
as Drupal blocks.

## Requirements

- **PHP**: 8.2+
- **Drupal**: 10.3+ or 11.x
- **Dependencies**:
    - `noweh/twitter-api-v2-php` (^3.7)
    - `facebook/php-business-sdk` (^24.0)
    - `nesbot/carbon` (^2.59 || ^3.0)

## Installation

```bash
composer require drupal/socialfeed
drush en socialfeed -y
drush cache:rebuild
```

## Configuration

**Admin > Configuration > Services > Socialfeed**
(`/admin/config/services/socialfeed`)

### Facebook

Configure at `/admin/config/socialfeed/facebook`.

- Displays posts from a Facebook **Page** (not personal profiles)
- Filter by post type, configure post count, date format, text trimming,
  hashtag display
- **Test connection** button resolves page name to numeric ID and stores a
  page access token
- Optional built-in UI styling

### Instagram

Configure at `/admin/config/socialfeed/instagram`.

**Note**: Instagram Basic Display API was discontinued December 4, 2024. Only
the **Instagram Graph API** is supported, requiring a Professional (Creator or
Business) account.

- Displays images, videos, and carousel albums
- OAuth flow at `/socialfeed/instagram/auth` exchanges tokens automatically
- Long-lived tokens (60 days) are auto-refreshed after 50 days at the global
  level; block-level token overrides must be renewed manually

### X (formerly Twitter)

Configure at `/admin/config/socialfeed/twitter`.

- Displays posts via `noweh/twitter-api-v2-php` (X API v2)
- Requires Bearer Token and numeric Account ID (available from the
  [X Developer Console](https://console.x.com))
- Configurable post count, hashtag/mention linking, relative timestamps
- API responses are cached for 1 hour to minimize credit usage; cache is
  invalidated when X settings are saved
- **Paid API credits required** — the free tier does not support reading posts.
  See [X API pricing](https://developer.x.com/#pricing)

## Blocks

Place blocks via **Structure > Block Layout**:

- **Facebook Post Block**
- **Instagram Post Block**
- **X (formerly Twitter) Post Block**

All blocks support per-block credential and display overrides via the
**Customize Feed** checkbox (requires `administer socialfeed` permission).

## Theming

Templates in `templates/` organized by platform:

- `socialfeed_facebook_post` supports theme suggestions by `status_type`
- `socialfeed_instagram_post_image`
- `socialfeed_instagram_post_video`
- `socialfeed_instagram_post_carousel_album`
- `socialfeed_twitter_post`

Each platform has a preprocess file handling hashtag linking, URL conversion,
time formatting, and text trimming. Optional CSS libraries (`facebook_style`,
`twitter_style`, `instagram_style`) are attached when "Use Default UI Style"
is enabled.

## Permissions

- `administer socialfeed` access configuration and block overrides

## Known Limitations

- **X API free tier cannot read posts**: The free tier only allows posting.
  Reading posts (timeline endpoint) requires purchased API credits via
  [pay-per-usage pricing](https://developer.x.com/#pricing).
- **X API post reads capped at 2M/month**: Standard pay-per-usage plans have
  a monthly cap of 2 million post reads. Enterprise plans are available for
  higher volumes.
- **Instagram tokens**: Expire after 60 days. Auto-refreshed at global level
  only.

## Testing

```bash
phpunit -c web/modules/contrib/socialfeed/phpunit.xml.dist
```

Covers unit, functional, and kernel tests for blocks, services, factories,
config schema, and form validation.

## License

GPL-2.0+

## Support

- **Issues**: https://www.drupal.org/project/issues/socialfeed
- **Repository**: https://git.drupalcode.org/project/socialfeed
- **Project Page**: https://www.drupal.org/project/socialfeed
