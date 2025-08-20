# TFT-Tracker

TFTrack is a web-based statistic tracker for Teamfight Tactics (TFT), created as a learning project for PHP and API usage. It fetches your recent TFT match history using the Riot Games API and generates custom graphs to visualize your placements.

## Features

- Search for a TFT summoner by name and tagline
- Fetch and display recent match placements
- Generate a graph of placements for the last 10, 15, 20, or 25 games
- Show average placement and number of first-place finishes

## Setup

1. **Clone the repository** and place it in your web server directory (e.g., `htdocs` for XAMPP).
2. **Install PHP and a web server** (e.g., XAMPP).
3. **Get a Riot Games API key** and add it to [`models/API.php`](models/API.php).
4. **Configure your database** in [`models/dbconfig.ini`](models/dbconfig.ini) (optional, if you use database features).
5. **Start your web server** and navigate to `index.php` in your browser.

## Usage

- Enter your summoner name and tagline, then click "Search".
- View your average placement and first-place count.
- Select the number of matches to visualize.
- See your placement graph.

## File Structure

- [`index.php`](index.php): Main tracker page
- [`about.php`](about.php): About the project
- [`models/model_api.php`](models/model_api.php): API logic
- [`models/API.php`](models/API.php): Riot API key
- [`includes/header.php`](includes/header.php), [`includes/footer.php`](includes/footer.php): Page layout
- [`includes/style.css`](includes/style.css): Styling

## Author

Cooper Graves

## License

This project is for educational purposes.
