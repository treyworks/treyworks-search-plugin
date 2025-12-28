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
- Comprehensive database logging system with standardized log levels
- Admin interface for monitoring and managing logs
- REST API endpoints for external integrations
- REST API security with trusted domain management
- Custom field search support (including ACF integration)

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

1. In the Settings page, find the General Settings section
2. Enable or disable logging as needed
3. Set whether to delete the logs database table during plugin uninstall
4. View, filter, and manage logs in the Treyworks Search > Logs admin page

### REST API Security Configuration

1. In the Settings page, find the REST API Security section
2. Enable trusted domains to restrict API access to specific domains
3. Add trusted domains (one per line, without http:// or https://)
4. Note: Your WordPress domain is always trusted automatically
5. View and copy API endpoint URLs for integration

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

Treyworks Search Plugin provides REST API endpoints for integrating with external applications. This allows you to leverage the plugin's AI-powered search and answer capabilities from other systems.

### Setup

1. Go to Treyworks Search > Settings in your WordPress admin panel
2. Generate an integration token using the "Generate New Token" button in the API Settings section
3. Configure trusted domains in the REST API Security section (optional but recommended)
4. Save your settings to store the generated token

### Endpoints

The plugin exposes two REST API endpoints:

#### Search Endpoint
```
/wp-json/treyworks-search/v1/search
```
Performs a search and returns AI-generated summaries of the results.

#### Answer Endpoint
```
/wp-json/treyworks-search/v1/get_answer
```
Provides direct AI-generated answers to questions based on site content.

### Authentication

All API requests require the integration token to be included in the request headers:

```
treyworks-search-token: YOUR_INTEGRATION_TOKEN
```

### Security

The REST API includes two security layers:

1. **Integration Token**: Required in the request header for authentication
2. **Trusted Domains** (optional): When enabled, only requests from specified domains are allowed
   - Your WordPress domain is always trusted
   - Additional domains can be configured in Settings > REST API Security

### Examples

#### Search Request (POST)

```bash
curl -X POST \
  "https://your-site.com/wp-json/treyworks-search/v1/search" \
  -H "Content-Type: application/json" \
  -H "treyworks-search-token: YOUR_INTEGRATION_TOKEN" \
  -d '{"search_query": "How do I use WordPress?"}'
```

#### Answer Request (POST)

```bash
curl -X POST \
  "https://your-site.com/wp-json/treyworks-search/v1/get_answer" \
  -H "Content-Type: application/json" \
  -H "treyworks-search-token: YOUR_INTEGRATION_TOKEN" \
  -d '{"search_query": "How do I use WordPress?"}'
```

#### Answer Request (GET)

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
- **Settings** (Treyworks Search > Settings): Configure API keys, AI models, search options, logging preferences, and REST API security
- **Logs** (Treyworks Search > Logs): View, filter, and manage database logs with detailed context viewing

## Logging System

Treyworks Search comes with a powerful database logging system to help you monitor and troubleshoot your search functionality:

### Log Features

- **Standardized log levels**: Uses constants (TREYWORKS_LOG_INFO, TREYWORKS_LOG_WARNING, TREYWORKS_LOG_ERROR, TREYWORKS_LOG_DEBUG)
- Database-backed logging for persistence
- Contextual data including referring URLs and request details
- Filtering by log level, date range, and search terms
- Individual log deletion and bulk operations
- Context viewer with formatted JSON display

### Using Logs

1. Enable logging in Treyworks Search > Settings
2. Access logs through Treyworks Search > Logs in the admin panel
3. Use filters to find specific log entries
4. View detailed context data for each log entry (like search terms, results, etc.)

### Developer Integration

Developers can use the `treyworks_log()` function to add custom logs with standardized log level constants:

```php
treyworks_log($message, $level = TREYWORKS_LOG_INFO, $context = []);
```

**Available Log Level Constants:**
- `TREYWORKS_LOG_INFO` - Informational messages
- `TREYWORKS_LOG_WARNING` - Warning messages
- `TREYWORKS_LOG_ERROR` - Error messages
- `TREYWORKS_LOG_DEBUG` - Debug messages

**Example:**
```php
treyworks_log('Custom search processed', TREYWORKS_LOG_INFO, [
    'query' => $search_query,
    'results' => count($results)
]);

treyworks_log('API rate limit approaching', TREYWORKS_LOG_WARNING, [
    'remaining_requests' => 10
]);
```

## Changelog

### 1.3.0
- Added REST API security with trusted domain management
- Consolidated settings into single unified settings page
- Standardized log levels with constants for consistency
- Enhanced admin UI with improved accordion styles
- Added REST API endpoint URLs display with copy-to-clipboard functionality
- Removed duplicate settings classes for cleaner codebase
- Improved logs table UI (moved context viewer to actions column)
- Improved UI/UX for admin interface
- Added support for GPT-5.2, Google Gemini 3 Flash

### 1.2.3
- Added support for GPT-5.1
- Removed legacy models (o3, 4o, and Gemini 2.0)

### 1.2.2
- Added support for GPT-5 models
- Fix: Added user query to Gemini API call

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
