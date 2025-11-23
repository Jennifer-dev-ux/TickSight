
# Project Outline
I designed the system to meet the core requirements of the brief and to demonstrate good software engineering practice. It includes:
- **Structured MVC pattern for maintainability** – clear separation between controllers, models, and views so the project is easy to extend and debug.
- **Full use of API data** – loads and processes external tick sighting data, transforming it into a format suitable for mapping and filtering.
- **Merging external data with user-submitted reports** – combines dataset sightings with new reports from the “Report a sighting” form, so the map always reflects both API and user-generated data.
- **Interactive front-end experience** – map markers, filters, and a dynamic sidebar all make it easier to explore the data.
- **Server-side validation and secure data handling** – validates form input on the backend, sanitises user data, and stores it safely in the database.
- **Good UX, clean UI, and accessibility features** – responsive layout for mobile and desktop, clear visual hierarchy, ARIA attributes, and High Contrast mode for better visibility.

### Interactive Tick Map
Built with Leaflet.js
- Markers represent individual sighting from API endpoint
- Each sighting is represented individually (spread out around city centres since the API provides only city-level coordinates)
- Markers coloured by severity (Green = Low, Yellow = Medium, Red = High) Based on species type (I decided) since API does nt provide severity levels
- Click a marker to see:
1) Data & Time of reported sighting
2) Species breakdown and Severity
3) Timeline of activity (last reported sightings withing that city)
4) Quick actions:
   - Report a sighting (redirects user to "report a sighting" form)
   - Get directions (redirects user to new page; googlemaps.com without closing site)
   - Share (copies link of sighting from site URL)

### Filtering System
Users can filter data by:
- Tick species
- Date range 
- Severity level

### Report a Sighting
- Validates date, time, city, and ensures (eg. no future dates or dates like 31st November)
- Only accepted UK cities are allowed
- Optional image upload which stores it in a folder and a path to it is stored on the database
- Stores data locally in SQLite (database table called user_sightings)

### Education Page
- Species information and details
- Peak month & top cities per species (via API endpoints)
- Seasonal activity chart which clearly shows the number of sightings per month (with filters for city, year, species)

### Prevention Page
- General Before/after outdoor safety tips
- Safe tick removal guidance

### Accessibility
- High-contrast mode toggle / Improved colour visibility
- Enlarged markers with white outlines

## How to Run this Project
1) Clone the repository
   ```bash
   git clone https://github.com/Jennifer-dev-ux/TickSight.git
   cd TickSight
2)  Place the project in a server-accessible folder (For XAMPP: htdocs/).
3)  Start a PHP development server
   Ensure PHP 8+ and SQLite are installed and enabled.
Run locally: 
  ```bash
      php -S localhost:8000

4) Visit the application; Open this in your browser:
http://localhost:8000/index.php
