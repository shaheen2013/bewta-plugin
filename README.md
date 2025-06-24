# Bewta Plugin

## Overview

The **Bewta Plugin** is a lightweight WordPress plugin that enables seamless API integration between your site and an external service. It automatically captures user data from supported forms and transmits it securely to a designated endpoint.

## Installation & Activation

1. Download the `bewta-plugin.zip` file.
2. In your WordPress admin dashboard, go to **Plugins > Add New > Upload Plugin**.
3. Upload the ZIP file and click **Install Now**.
4. After installation, click **Activate** to enable the plugin.

## Configuration

1. After activation, navigate to **Bewta Plugin Settings** from the WordPress dashboard.
2. Enter your provided **API Key** in the input field.
3. Click **Save Changes** to apply the configuration.

## Usage Instructions

Once the plugin is activated and the API key is saved, it will automatically work with any form on your website that includes the following three fields:

- **First Name**
- **Email**
- **Phone Number**

Whenever such a form is submitted, the plugin collects the input data and sends it to the main server via the API.

## Supported Form Plugins

The Bewta Plugin has been successfully tested with:

- **Fluent Forms**

Other form plugins may also be compatible as long as the required fields are included.

## Notes

- Ensure that your form fields are properly labeled or identified to reflect **first name**, **email**, and **phone number**, so the plugin can detect and process them correctly.
- The plugin does not affect your form layout or frontend display.
- All data is securely transmitted to the target API endpoint.

## Support

For any issues or feature requests, please contact the development team or open a support ticket through the plugin repository.
