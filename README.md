# CSV to Posts Plugin

CSV to Posts is a WordPress plugin that allows you to import posts from a CSV file into your WordPress website. It also includes features for generating content using the OpenAI API and scraping data from Google Places.

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Advanced Features](#advanced-features)
- [Contributing](#contributing)
- [License](#license)

## Installation

1. Download the latest release from the [CSV TO POSTS](https://github.com/kamilKrygier/Generate_posts_from_google_maps_CSV.git).
2. Upload the plugin folder to your WordPress site's `wp-content/plugins/` directory.
3. Activate the "CSV to Posts" plugin through the WordPress admin dashboard.

## Configuration

Before using the plugin, you need to configure some settings.

1. Obtain API keys for the following services:
   - Google Maps Static API
   - Google Places API
   - OpenAI API

2. Edit the `functions.php` file in the plugin directory and replace the placeholder API keys with your actual API keys:

```php
define('MAPS_STATIC_API_KEY', 'Your Google Maps Static API Key');
define('MAPS_STATIC_API_SECRET', 'Your Google Maps Static API Secret');
define('GOOGLE_PLACES_API_KEY', 'Your Google Places API Key');
define('OPENAI_API_KEY', 'Your OpenAI API Key');
```

3. Install GuzzleHttp, see [More info](https://docs.guzzlephp.org/en/stable/overview.html)

4. Save your changes.

## Usage

### Uploading Posts from CSV

1. Go to the WordPress admin dashboard.
2. Navigate to "CSV to Posts" in the menu.
3. Use the provided form to upload a CSV file containing post data.
4. Click the "Upload" button.

### Generating Content with OpenAI

1. Go to the WordPress admin dashboard.
2. Navigate to "CSV to Posts" in the menu.
3. Select one or more posts from the list.
4. Choose "Generate content with AI" from the bulk actions dropdown or wait till website will process it by itself.
5. Click the "Apply" button to generate AI content for the selected posts.

### Scraping Data from Google Places

1. Go to the WordPress admin dashboard.
2. Navigate to "CSV to Posts" in the menu.
3. Access the "Google Places Scrapper" page.
4. Choose a category, and click the "Scrape CSV from Google Places" button.

## Advanced Features

### Scheduled Content Generation

The plugin supports scheduled content generation using WordPress Cron. It will generate AI content for posts that don't already have AI-generated content.

To configure scheduled content generation, follow these steps:

1. Edit the `functions.php` file in the plugin directory.

2. Set up the WordPress Cron schedule interval as needed:

```php
function custom_cron_job_recurrence($schedules){
    $schedules['every_hour'] = array(
        'interval'  => 3600, // Change the interval as needed (in seconds)
        'display'   => 'Every Hour'
    );
    return $schedules;
}
add_filter('cron_schedules', 'custom_cron_job_recurrence');
```

3. Activate the scheduled content generation by adding the following code to `functions.php`:

```php
if (!wp_next_scheduled('run_ai_generation_for_posts')) {
    wp_schedule_event(time(), 'every_hour', 'run_ai_generation_for_posts');
}

function handle_ai_generation_for_posts() {
    // Content generation logic here
}
add_action('run_ai_generation_for_posts', 'handle_ai_generation_for_posts');
```

### Customizing Content Generation Prompts

You can customize the content generation prompts in the `functions.php` file. Modify the `$prompt` variable in the `handle_ai_generation_for_posts` function.
Later on there will be added functionality to do it inside Wordpress Admin 

### Upload from CSV

The `upload_from_csv.php` script is part of the CSV to Posts plugin and allows you to upload CSV files containing location data and create WordPress posts based on the CSV data.

#### Usage

To use this script, follow these steps:

1. Access the WordPress admin dashboard.

2. Navigate to the "CSV to Posts" menu in the admin sidebar.

3. You will find an upload form to select and upload a CSV file.

4. Make sure your CSV file meets the following requirements:
   - It should contain the following columns: 'Nazwa', 'Telefon', 'Adres', 'Godziny otwarcia', 'Strona internetowa', 'Opinia 1', 'Opinia 2', 'Opinia 3', 'Opinia 4', 'Opinia 5', 'Opinia 6', 'Typ', 'Wysokość cen', 'Latitude', 'Longitude'.
   - The CSV file should have the appropriate data in each of these columns.

5. Once the CSV file is uploaded, the script will process the data in batches.
   - The number of rows per batch is determined by the `$batch_size` variable in the script.

6. For each row in the CSV file, the script performs the following actions:
   - Validates and processes the data from the CSV.
   - Checks if a post with the same place name already exists. If not, it creates a new post.

7. The script creates WordPress posts based on the data in the CSV file. Each post is assigned a category based on the 'Typ' column in the CSV file.

8. If a category doesn't already exist, the script creates it.

9. The script also includes an option to set a post thumbnail (featured image). You can specify the `$placeholder_id` variable to set a default image for posts that don't have a featured image.

10. The processed data includes the following details:
    - Place name
    - Address
    - Phone number
    - Opening hours
    - Website URL
    - Reviews
    - Business type
    - Price range
    - Location coordinates (latitude and longitude)

11. The script also generates a Google Static Map image for each post based on the location coordinates.

12. After processing all batches, the script will display a message indicating that the process is complete.

Please ensure that you have configured your Google Maps and other API keys as defined in the `functions.php` file to use location-related features.

**Note**: Be cautious when using this script with a large CSV file, as it may take some time to process all the data.

### Google Places Scrape Script

The `maps_scrape.php` script is designed to scrape information from Google Places based on specific categories and target cities. It fetches data such as place name, phone number, address, opening hours, website, reviews, types, price level, latitude, and longitude, and stores this data in a CSV file.

#### Usage

To use this script, follow these steps:

1. Make sure you have the necessary Google Places API key configured. This key is required to make requests to the Google Places API.

2. Access the script via a web browser or by executing it on your web server.

3. You will need to submit a form to initiate the scraping process. The form includes the following fields:
   - **Place Category**: Select the category of places you want to scrape. The available categories are based on the Google Places categories as of October 2023.

4. Once you submit the form, the script will start scraping Google Places data for the specified category in a list of target cities.

5. The script fetches data for each place, including details such as name, phone number, address, opening hours, website, reviews, types, price level, latitude, and longitude.

6. The scraped data is organized and stored in a CSV file. Each row in the CSV file represents information about a place.

7. The CSV file is saved in the `exported_csv` directory with a filename based on the selected place category.

8. The script provides progress updates and logs as it scrapes data from Google Places.

9. After the scraping process is complete, the script displays a message indicating that the process has finished.

#### Customization

You can customize the script according to your needs by modifying the following variables:

- `$place_category`: This array contains all the main Google Places categories as of October 2023. You can update this list to include specific categories of interest.

- `$cities`: This array contains the names of target cities for scraping. You can replace these cities with your own list of target locations.

- The script uses the Google Places API to fetch data. Make sure you have a valid API key and configure it in the `GOOGLE_PLACES_API_KEY` constant.

- The script automatically generates a CSV file based on the selected place category and stores it in the `exported_csv` directory. You can customize the directory and file naming conventions as needed.

- The script includes error handling and logging to ensure smooth operation. You can modify the logging behavior to suit your requirements.

Please ensure that you comply with Google's Terms of Service and usage policies when scraping data from Google Places.

**Note**: Be cautious when using this script, as excessive scraping may lead to rate limiting or restrictions by the Google Places API. Use it responsibly and consider implementing rate limiting to avoid overloading the API.

For additional documentation and support related to the Google Places API or this script, refer to the Google Places API documentation or seek assistance from the API provider.

### `post-content.php` Template

The `post-content.php` script is a template used to generate the content for WordPress posts created by the `maps_scrape.php` script. This template formats and displays business or company information, including details such as phone number, address, opening hours, website, reviews, category, and price level. It also includes a Google Maps image and a link for directions.

#### Usage

The template is included within the `maps_scrape.php` script and is used to generate the content for each WordPress post created during the scraping process. Here's how the template is structured and what it does:

1. **Business/Company Info Section**: This section displays key information about the business or company. It includes details such as phone number, website, address, opening hours, category, and price level.

   - **Phone**: If a phone number is available, it is displayed as a clickable link for easy dialing.
   - **Website**: If a website URL is available, it is displayed as a clickable link to the website.
   - **Address**: The address of the business or company is displayed.
   - **Opening Hours**: The opening hours are displayed in a formatted list.
   - **Category**: The category of the business is displayed.
   - **Price Level**: The price level is displayed.

2. **Latest Reviews Section**: This section displays the latest reviews about the business. Up to six reviews are displayed if available.

3. **Google Static Maps Section**: This section displays a Google Static Maps image showing the location of the business on a map. It also includes a link to Google Maps for directions.

#### Customization

You can customize the appearance and content of the template as needed:

- You can modify the HTML and CSS styles to change the layout and styling of the displayed information.

- If you want to display additional or different information, you can edit the PHP code in this template. For example, you can add more details or change the way information is presented.

- The template is designed to be used within the `maps_scrape.php` script and automatically populates the content based on the data fetched from Google Places.

- Ensure that the content formatting is consistent with your WordPress theme and requirements.

This template is designed to be a starting point for displaying scraped business or company information in WordPress posts. You can further enhance and customize the template to suit your specific needs and design preferences.

Please note that the template relies on data fetched from Google Places, so the availability and accuracy of the displayed information depend on the Google Places API and the data provided by Google.


## Contributing

If you would like to contribute to the development of this plugin, please follow these guidelines:

1. Fork the repository.

2. Create a new branch for your feature or bug fix.

3. Make your changes and commit them with clear, descriptive messages.

4. Create a pull request with a detailed explanation of your changes.

5. Your pull request will be reviewed, and changes may be requested before merging.

## License

This plugin is licensed under the [GPL-3.0 license](LICENSE) License.

---

For more information, issues, and support, please visit the [CSV TO POSTS](https://github.com/kamilKrygier/Generate_posts_from_google_maps_CSV.git).

