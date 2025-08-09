# Treyworks Search Plugin

A WordPress plugin that enhances your site's search functionality by providing AI-powered summaries of search results.

## Description

Treyworks Search Plugin is a powerful WordPress plugin that integrates with AI services (OpenAI or Google Gemini) to provide concise, relevant summaries of search results. This helps users quickly find the content they're looking for without having to click through multiple pages.

### How It Works

1. Extracts Optimized Keywords
The plugin analyzes the user’s search query and identifies the most relevant keywords to refine the search.

2️. Runs WordPress Search
It performs a search using WordPress’s built-in search functionality to gather the most relevant results.

3️. Generates AI-Powered Summaries
Using OpenAI or Google Gemini, the plugin creates a concise, AI-generated summary that directly answers the user’s question.

## Features

- AI-powered search result summaries
- Support for multiple AI providers (OpenAI and Google Gemini)
- Customizable summary generation
- Easy-to-use settings interface
- Seamless integration with WordPress search
- Comprehensive database logging system
- Admin interface for monitoring and managing logs

## Installation

1. Upload the `treyworks-search` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Treyworks Search > Settings in your WordPress admin panel to configure your API keys and preferences

## Configuration

1. Navigate to Treyworks Search > Settings in your WordPress admin panel
2. Enter your API key(s) for your preferred AI service (OpenAI or Google Gemini)
3. Customize any additional settings as needed
4. Save your changes

### Logging Configuration

1. In the Settings page, find the Logging Settings section
2. Enable or disable logging as needed
3. Set whether to delete the logs database table during plugin uninstall
4. View, filter, and manage logs in the Treyworks Search > Logs admin page

## Shortcodes

Treyworks Search Plugin provides two shortcodes that you can use to integrate the search functionality into your posts, pages, or custom templates:

### [treyworks_search]

This shortcode displays a search form that allows users to enter a search query and get AI-powered summaries of the results.

**Usage:**
```
[treyworks_search]
```

**Example:**
```php
<?php echo do_shortcode('[treyworks_search]'); ?>
```

### [treyworks_answer]

This shortcode displays a question form that allows users to ask questions and get AI-generated answers. You can optionally limit the search to specific posts.

**Usage:**
```
[treyworks_answer]
```

**Parameters:**
- `post_ids` (optional): Comma-separated list of post IDs to limit the search to specific posts

**Example with post_ids:**
```
[treyworks_answer post_ids="1,2,3,4"]
```

**Example in PHP:**
```php
<?php echo do_shortcode('[treyworks_answer post_ids="1,2,3,4"]'); ?>
```

## API Integration

Treyworks Search Plugin provides an API endpoint for integrating with external applications. This allows you to leverage the plugin's AI-powered search and answer capabilities from other systems.

### Setup

1. Go to Treyworks Search > Settings in your WordPress admin panel
2. Generate an integration token using the "Generate New Token" button in the API Settings section
3. Save your settings to store the generated token

### Endpoint

The plugin exposes the following endpoint:

```
/wp-json/treyworks-search/v1/get_answer
```

### Authentication

All API requests require the integration token to be included in the request headers:

```
treyworks-search-token: YOUR_INTEGRATION_TOKEN
```

### Examples

#### POST Request

```bash
curl -X POST \
  "https://treyworks.local/wp-json/treyworks-search/v1/get_answer" \
  -H "Content-Type: application/json" \
  -H "treyworks-search-token: YOUR_INTEGRATION_TOKEN" \
  -d '{"search_query": "How do I use WordPress?"}'
```

#### GET Request

```bash
curl -X GET \
  "https://your-site.com/wp-json/treyworks-search/v1/get_answer?search_query=How%20do%20I%20use%20WordPress%3F" \
  -H "Content-Type: application/json" \
  -H "treyworks-search-token: YOUR_INTEGRATION_TOKEN"
```

### Response

The endpoint returns a concise, structured response optimized for integration with AI agents and other systems. The response format is plain text that can be directly passed to other AI systems.

## Requirements

- WordPress 6.6 or higher
- PHP 8.1 or higher
- Valid API key for either OpenAI or Google Gemini

## Support

For support, feature requests, or bug reports, please visit our [support page](https://github.com/treyworks/treyworks-search/issues).

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by [Clarence Pearson](https://clarencepearson.com) and sponsored by [TreyWorks LLC](https://treyworks.com).

## Admin Interface

Treyworks Search Plugin provides a comprehensive admin interface accessible through the WordPress admin panel:

- **Dashboard** (Treyworks Search): Overview of the plugin with quick links to settings and logs
- **Settings** (Treyworks Search > Settings): Configure API keys, search options, and logging preferences
- **Logs** (Treyworks Search > Logs): View, filter, and manage database logs

## Logging System

Treyworks Search comes with a powerful database logging system to help you monitor and troubleshoot your search functionality:

### Log Features

- Multiple log levels: info, warning, error, debug
- Database-backed logging for persistence
- Contextual data including referring URLs
- Filtering by log level, date range, and search terms
- Bulk and individual log deletion

### Using Logs

1. Enable logging in Treyworks Search > Settings
2. Access logs through Treyworks Search > Logs in the admin panel
3. Use filters to find specific log entries
4. View detailed context data for each log entry (like search terms, results, etc.)

### Developer Integration

Developers can use the `treyworks_log()` function to add custom logs:

```php
treyworks_log($message, $level = 'info', $context = []);
```

Example:
```php
treyworks_log('Custom search processed', 'info', [
    'query' => $search_query,
    'results' => count($results)
]);
```

## Changelog

### 1.2.1
- Added LLM model selection for extraction and generative prompts
- Rollback custom fields hooks for further performance optimization

### 1.2.0
- Added database logging system
- Added admin interface for logs
- Consolidated admin menus under "Treyworks Search"
- Added referer URL tracking for API requests
- Improved plugin initialization and error handling

### 1.0.4
- Added API integration token
- Added API endpoint documentation
- Added answer REST API endpoint

### 1.0.3
- Added common questions setting
- Added dismiss button for common questions

### 1.0.2
- Added search input placeholder setting
- Code refactoring
- Added support for post types in search results

### 1.0.1
- Mask API key input for added security
- Only focus chat modal input on large devices

### 1.0.0
- Initial release
- Basic search result summarization
- Support for OpenAI and Google Gemini
- Settings page for API configuration
