# Catapulte-Autoplugin

<p align="center">
  <img src="https://catapultcommunication.com/logo192.png" alt="Catapulte-Autoplugin Logo" width="128">
</p>

Catapulte-Autoplugin is a WordPress plugin that uses AI to assist in generating, fixing, and extending plugins on-demand. It enables users to quickly create functional plugins from simple descriptions, addressing specific needs without unnecessary bloat.

- Generate plugins using AI
- Fix and extend existing plugins
- Full control over the generation process
- Support for multiple AI models, and any OpenAI-compatible custom API
- View the list of generated plugins for easy management

---

Catapulte-Autoplugin offers practical solutions for various WordPress development scenarios:

- **Lightweight Alternatives**: Create simple, focused plugins to replace large, feature-heavy plugins that may slow down your site or include unnecessary features and advertisements.
- **Custom Solutions**: Develop site-specific plugins tailored to your unique requirements, eliminating the need for complex workarounds or multiple plugins.
- **Developer Foundations**: Generate solid base plugins that developers can extend and build upon, streamlining the process of creating complex, custom plugins.
- **Professional Multi-File Plugins**: Create sophisticated plugins with proper file structure, organization, and scalability using complex plugin mode.

## Plugin Highlights

- **Completely Free**: No premium version, no ads, no account required.
- **Privacy-Focused**: No data collection or external communication (except for the AI API you choose).
- **BYOK (Bring Your Own Key)**: Use your own API key from the AI provider of your choice.
- **Flexible AI Models**: Choose from a variety of AI models to suit your needs, or set up custom models.
- **Use in Your Language**: The plugin is fully translatable and has built-in support for 10+ languages.

## How It Works

1. **Describe Your Plugin**: Provide a description of the plugin you want to create.
2. **AI Generation**: Catapulte-Autoplugin uses AI to generate a development plan and write the code.
3. **Review and Install**: Review the generated plan and code, make any necessary changes, and install the plugin with a single click.

You can also use Catapulte-Autoplugin to **fix bugs**, **add new features**, or **explain plugins** you've created with the tool. The **Explain Plugin** feature allows you to ask questions or obtain general overviews of generated plugins, helping you better understand their functionality and structure.

## Complex Plugin Generation

Catapulte-Autoplugin's complex plugin mode enables the creation of sophisticated, multi-file plugins with:

- **Proper File Structure**: Organized directories and file hierarchies
- **Object-Oriented Design**: Well-structured classes and namespaces
- **Scalable Architecture**: Plugins designed for growth and maintenance
- **Professional Standards**: Code that follows WordPress development best practices

## Specialized Models Configuration

Optimize your plugin generation workflow by assigning different AI models to specific tasks:

- **Planner Model**: Handles plugin analysis and extension planning
- **Coder Model**: Focuses on code generation and implementation
- **Reviewer Model**: Provides code explanations and reviews

This approach allows you to:
- Use reasoning models for planning complex architectures
- Employ fast, cost-effective models for simple coding tasks
- Leverage specialized models for code review and explanations
- Optimize both performance and API costs

## Token Usage Tracking

Monitor your API consumption with detailed usage information:
- Real-time token count display during generation
- Per-step token usage breakdown
- Duration of each API request

This helps you:
- Control API costs effectively
- Choose the most cost-efficient models for your needs
- Understand the token impact of different generation modes

## Extend Third-Party Plugins and Themes with Hooks

Catapulte-Autoplugin allows you to easily extend **any plugin** or **theme** directly from the WordPress admin dashboard:

- Click on the "**Extend Plugin**" action link for the plugin you'd like to enhance, or look for the "**Extend**" button on the Appearance > Themes page.
- Catapulte-Autoplugin will analyze the selected plugin or theme, extracting available action and filter hooks along with relevant contextual details.
- Provide a description of the desired extension; Catapulte-Autoplugin assesses the technical feasibility using available hooks.
- A new extension plugin will be generated based on your description, allowing seamless integration with the existing functionality.

## Auto-detect Fatal Errors

When you activate an AI-generated plugin, Catapulte-Autoplugin will automatically detect fatal errors and deactivate the plugin to prevent site crashes. A message with the error details will be displayed, along with a link to fix the issue automatically with AI.

## Supported Models

Catapulte-Autoplugin supports 30+ AI models, including:

- Claude 4.1 Opus
- Claude 4 Sonnet
- Claude 3.7 Sonnet
- Claude 3.5 Sonnet
- Claude 3.5 Haiku
- o3
- o4-mini
- GPT-5
- GPT-5-mini
- GPT-5-nano
- GPT-4.1
- GPT-4.1-mini
- GPT-4.1-nano
- GPT-4o
- GPT-4o mini
- Google Gemini 2.5 Pro
- Google Gemini 2.5 Flash
- Google Gemini 2.5 Flash Lite
- xAI Grok 4

While Catapulte-Autoplugin is free to use, you may need to pay for API usage based on your chosen model.

## Custom Models

Catapulte-Autoplugin supports custom models: you can plug in any OpenAI-compatible API by providing the endpoint URL, model name, and API key. This feature allows you to use any model you have access to, including locally hosted models, or custom models you've trained yourself.

## BYOK (Bring Your Own Key)

To use Catapulte-Autoplugin, you'll need an API key from an AI provider. Insert your key in the plugin settings to get started. Your API key remains on your server and is not shared with anyone.

Some AI platforms currently offer free plans and include SOTA models, like **Gemini 2.5 Pro** through [Google AI Studio](https://aistudio.google.com/). Refer to the respective websites for pricing information.

## AI-Generated Plugins

Plugins created by Catapulte-Autoplugin are standard WordPress plugins:

- They function independently and will continue to work even if Catapulte-Autoplugin is deactivated or deleted.
- You can install them on other WordPress sites without Catapulte-Autoplugin.
- While Catapulte-Autoplugin provides a convenient listing screen for generated plugins, they can also be managed from the standard WordPress Plugins screen.

## Code Quality and Security

Catapulte-Autoplugin aims to generate code that adheres to WordPress coding standards. However, it's important to treat AI-generated code with the same caution you would apply to any third-party code. We strongly recommend:

- Reviewing and testing all generated code before use in a production environment.
- Conducting thorough testing on a staging site before deployment.
- Considering a professional security audit for critical applications.

## Installation

1. Upload the plugin zip file through the 'Plugins' screen in WordPress, or unzip the file and upload the `catapulte-autoplugin` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to the Catapulte-Autoplugin settings page and enter your API key(s).
4. Choose your preferred AI model in the settings.
5. Start generating plugins!

## Translations

Catapulte-Autoplugin is fully translatable. If you would like to contribute a translation, please create a pull request with the translation files. Currently, the plugin includes translations for the following languages:
- English - `en_US`
- Français (French) - `fr_FR`
- Español (Spanish) - `es_ES`
- Deutsch (German) - `de_DE`
- Português (Portuguese) - `pt_PT`
- Italiano (Italian) - `it_IT`
- Magyar (Hungarian) - `hu_HU`
- Nederlands (Dutch) - `nl_NL`
- Polski (Polish) - `pl_PL`
- Türkçe (Turkish) - `tr_TR`
- Русский (Russian) - `ru_RU`

## Licensing

Catapulte-Autoplugin is licensed under the GPLv3 or later.
