# Quick Search Summarizer

A WordPress plugin that enhances your site's search functionality by providing AI-powered summaries of search results.

## Description

Quick Search Summarizer is a powerful WordPress plugin that integrates with AI services (OpenAI or Google Gemini) to provide concise, relevant summaries of search results. This helps users quickly find the content they're looking for without having to click through multiple pages.

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

## Installation

1. Upload the `quick-search-summarizer` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Quick Search Summarizer to configure your API keys and preferences

## Configuration

1. Navigate to Settings > Quick Search Summarizer in your WordPress admin panel
2. Enter your API key(s) for your preferred AI service (OpenAI or Google Gemini)
3. Customize any additional settings as needed
4. Save your changes

## Requirements

- WordPress 6.6 or higher
- PHP 8.1 or higher
- Valid API key for either OpenAI or Google Gemini

## Support

For support, feature requests, or bug reports, please visit our [support page](https://github.com/treyworks/quick-search-summarizer/issues).

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by [Clarence Pearson](https://clarencepearson.com) and sponsored by [TreyWorks LLC](https://treyworks.com).

## Changelog

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
