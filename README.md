  ## Project Outline:
  #### Video Walk-through:
  https://www.youtube.com/watch?v=1JbmpJeKqo0
### Choice Justification:
I designed the system to meet the core requirements of the brief and to demonstrate good software engineering practice. It includes:
- **Structured MVC pattern for maintainability** – clear separation between controllers, models, and views so the project is easy to extend and debug.
- **Full use of API data** – loads and processes external tick sighting data, transforming it into a format suitable for mapping and filtering.
- **Merging external data with user-submitted reports** – combines dataset sightings with new reports from the “Report a sighting” form, so the map always reflects both API and user-generated data.
- **Interactive front-end experience** – map markers, filters, and a dynamic sidebar all make it easier to explore the data.
- **Server-side validation and secure data handling** – validates form input on the backend, sanitises user data, and stores it safely in the database.
- **Good UX, clean UI, and accessibility features** – responsive layout for mobile and desktop (Using Bootstrap), clear visual hierarchy, ARIA attributes, and High Contrast mode for improved colour visibility

### Tech Stack Used
- PHP 8.5
- HTML5
- CSS3
- Bootstrap 5
- Leaflet.js
- Vanilla JavaScript
- SQLite
- MVC architecture (Controllers / Models / Views)
- cURL for API communication
- Server-side validation

### Wireframes of UI 
<img width="538" height="377" alt="Home wf" src="https://github.com/user-attachments/assets/51fb312d-3127-41b0-aed0-e4df8034e88a" />
<img width="343" height="251" alt="sighting wf" src="https://github.com/user-attachments/assets/096871dd-e5bf-4655-ade6-9657a4678ae6" />
<img width="337" height="397" alt="Info wf" src="https://github.com/user-attachments/assets/6fed4abd-229e-4d67-8432-49e1a670578e" />
<img width="355" height="254" alt="prevention wf" src="https://github.com/user-attachments/assets/a2270ba0-4916-4e53-8990-81cbb3a7fa81" />

## Features:
### Interactive Tick Map
Built with Leaflet.js
- Markers represent individual sighting from API endpoint
- Each sighting is represented individually (spread out around city centres since the API provides only city-level coordinates)
- Markers coloured by severity (Green = Low, Yellow = Medium, Red = High). Assumption made based on species type, since API does not provide severity levels
- Click a marker to see:
1) Data & Time of reported sighting
2) Species breakdown and Severity
3) Timeline of activity (last reported sightings within that city)
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
- Optional image upload which is stored in /images/uploads/ displayed in the map side panel
- Stores data locally in SQLite (database table called user_sightings)
- Normalised so user sightings match API data structure, and are shown on map

### Information Page (or education.phtml)
- Species information and details
- Peak month & top cities per species (via API endpoints)
- Seasonal activity chart which clearly shows the number of sightings per month (with filters for city, year, species)

### Prevention Page
- General Before/after outdoor safety tips
- Safe tick removal guidance

### Accessibility
- Fully responsive layout
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

## To visit the application Open this in your browser:
http://localhost:8000/index.php

