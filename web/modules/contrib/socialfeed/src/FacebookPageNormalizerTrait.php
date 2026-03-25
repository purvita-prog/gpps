<?php

namespace Drupal\socialfeed;

/**
 * Provides Facebook page input normalization.
 *
 * Normalizes page names, URLs, and numeric IDs into a consistent format
 * for use with the Facebook Graph API.
 */
trait FacebookPageNormalizerTrait {

  /**
   * Normalizes a Facebook page input (URL/slug/ID) into a slug or numeric ID.
   *
   * Handles the following input formats:
   * - Numeric page ID (e.g., "123456789")
   * - Facebook URL (e.g., "https://www.facebook.com/pagename")
   * - Profile URL with ID (e.g., "facebook.com/profile.php?id=123")
   * - Simple page slug (e.g., "pagename")
   *
   * @param string|null $input
   *   The raw page name, URL, or ID input.
   *
   * @return string
   *   The normalized page slug or numeric ID.
   */
  protected function normalizePageInput(?string $input): string {
    $raw = trim((string) $input);
    if ($raw === '') {
      return '';
    }

    // Numeric ID provided.
    if (preg_match('/^\d+$/', $raw)) {
      return $raw;
    }

    // Full URL provided.
    if (str_contains($raw, 'facebook.com') || str_contains($raw, 'fb.com')) {
      $candidate = preg_match('#^https?://#i', $raw) ? $raw : ('https://' . ltrim($raw, '/'));
      $path = (string) (parse_url($candidate, PHP_URL_PATH) ?? '');
      $query = (string) (parse_url($candidate, PHP_URL_QUERY) ?? '');

      // profile.php?id=12345 pattern.
      if (str_ends_with($path, '/profile.php') && $query) {
        parse_str($query, $params);
        if (!empty($params['id']) && preg_match('/^\d+$/', (string) $params['id'])) {
          return (string) $params['id'];
        }
      }

      // Otherwise use first path segment as slug.
      $segments = array_values(array_filter(explode('/', trim($path, '/'))));
      if (!empty($segments[0])) {
        return $segments[0];
      }
    }

    // Default: return trimmed slug.
    return $raw;
  }

}
