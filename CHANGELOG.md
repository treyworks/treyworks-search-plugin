# Changelog

## 1.4.1
- **Prompt Architecture Refactor**: Changed search prompts from editable fields to built-in constants with optional tone/branding customization
- **Extraction Prompt Enhancement**: Updated to generate up to 3 comma-separated search query variations for better coverage
- **Summary Prompt Improvement**: Removed duplicate "Top Results" section, expanded to provide comprehensive 300-500 word answers
- **Security Hardening**: Added comprehensive safeguards to all prompts (prompt injection protection, content safety, hallucination prevention)
- **Admin UI Enhancement**: Added grouped "Built-in System Prompts" viewer with modal display for extraction, summary, and answer prompts
- **Search Ranking**: Improved result ranking by aggregating matches across extracted terms
- **Results Display**: Replaced accordion with visible result cards showing excerpts and citations

## 1.4.0
- Simplified the plugin to Google Gemini-only support.
- Removed OpenAI settings, client code, and dependency wiring.
- Refreshed Composer dependencies for the Gemini-only release.

## 1.3.1
- Added multi-term search aggregation and ranking.
- Reduced default max search results to 3.
- Added streamed search progress states in the UI.
