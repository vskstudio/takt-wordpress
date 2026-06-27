# Takt Analytics for WordPress

## Unreleased

### Hardening

- WooCommerce: the order is now claimed (the `_takt_tracked` guard is marked and
  saved) **before** the Purchase event is sent, not after, so a re-entrant status
  hook (processing→completed, a plugin re-saving the order, a double admin click)
  counts a purchase at most once.
- Require `vskstudio/takt-core-php` `^0.3.2`, pulling in the S2S header
  sanitization (CR/LF stripped from the forwarded buyer IP/User-Agent and the
  API key), the numeric `Revenue` amount validation, and the re-bundled inline
  tracker.

## 0.1.0

- Initial release: injects the privacy-first Takt browser snippet into
  `wp_head` (inline / CDN / hosted-asset / SDK modes) and reports WooCommerce
  purchases as server-to-server events.
