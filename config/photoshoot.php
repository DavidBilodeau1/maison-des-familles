<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Branding
    |--------------------------------------------------------------------------
    |
    | These values control the branding and appearance of your photoshoot
    | application. Customize these to match your organization's identity.
    |
    */

    'branding' => [
        // Application name displayed in the header
        'app_name' => env('PHOTOSHOOT_APP_NAME', 'Photoshoot'),

        // Organization name displayed below the app name
        'organization_name' => env('PHOTOSHOOT_ORG_NAME', 'Maison des Familles'),

        // Organization description for footer
        'organization_description' => env('PHOTOSHOOT_ORG_DESC', 'Organisme à but non lucratif dédié aux familles'),

        // Organization tagline for footer
        'organization_tagline' => env('PHOTOSHOOT_ORG_TAGLINE', 'Aidant les familles dans le besoin avec amour et compassion ❤️'),

        // Path to logo image (relative to public directory)
        'logo_path' => env('PHOTOSHOOT_LOGO_PATH', 'images/logo.png'),

        // Logo height in Tailwind CSS classes (e.g., h-12, h-14, h-16)
        'logo_height' => env('PHOTOSHOOT_LOGO_HEIGHT', 'h-14'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Color Scheme
    |--------------------------------------------------------------------------
    |
    | Define your application's color scheme. These use Tailwind CSS color
    | names. You can use any Tailwind color like: purple, blue, pink, green,
    | red, yellow, indigo, etc.
    |
    */

    'colors' => [
        // Primary color for main UI elements
        'primary' => env('PHOTOSHOOT_COLOR_PRIMARY', 'purple'),

        // Secondary/accent color
        'secondary' => env('PHOTOSHOOT_COLOR_SECONDARY', 'pink'),

        // Gradient start color (for buttons and special elements)
        'gradient_start' => env('PHOTOSHOOT_GRADIENT_START', '#667eea'),

        // Gradient end color
        'gradient_end' => env('PHOTOSHOOT_GRADIENT_END', '#764ba2'),

        // Warm gradient start (alternative gradient)
        'warm_gradient_start' => env('PHOTOSHOOT_WARM_GRADIENT_START', '#f093fb'),

        // Warm gradient end
        'warm_gradient_end' => env('PHOTOSHOOT_WARM_GRADIENT_END', '#f5576c'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Settings
    |--------------------------------------------------------------------------
    |
    | Configure the photo selection session behavior.
    |
    */

    'session' => [
        // Duration of photo selection session in minutes
        'duration_minutes' => env('PHOTOSHOOT_SESSION_DURATION', 30),

        // PIN length (number of digits)
        'pin_length' => env('PHOTOSHOOT_PIN_LENGTH', 8),
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Settings
    |--------------------------------------------------------------------------
    |
    | Configure admin panel settings.
    |
    */

    'admin' => [
        // Default admin password (CHANGE THIS IN PRODUCTION!)
        'password' => env('PHOTOSHOOT_ADMIN_PASSWORD', 'admin123'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Photo Storage
    |--------------------------------------------------------------------------
    |
    | Configure photo storage paths.
    |
    */

    'storage' => [
        // Base path for photos (relative to storage/app)
        'base_path' => env('PHOTOSHOOT_STORAGE_BASE', 'photos'),

        // Subdirectory for uploaded photos
        'uploads_dir' => env('PHOTOSHOOT_STORAGE_UPLOADS', 'uploads'),

        // Subdirectory for final selected photos
        'final_dir' => env('PHOTOSHOOT_STORAGE_FINAL', 'final_choices'),

        // Allowed photo extensions
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif'],

        // External base URL for photos (e.g. a Cloudflare Tunnel URL).
        // When set, photo URLs point here instead of being served through the app.
        // The server at this URL must serve uploads/{family}/{filename} at /{family}/{filename}.
        'photos_url' => env('PHOTOSHOOT_PHOTOS_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Text & Messages
    |--------------------------------------------------------------------------
    |
    | Customize the text and messages displayed throughout the application.
    |
    */

    'messages' => [
        // Welcome message on family login page
        'welcome_title' => env('PHOTOSHOOT_WELCOME_TITLE', 'Bienvenue! 👋'),

        // Welcome subtitle
        'welcome_subtitle' => env('PHOTOSHOOT_WELCOME_SUBTITLE', 'Sélection de Photos Familiales'),

        // Instructions on login page
        'login_instructions' => env('PHOTOSHOOT_LOGIN_INSTRUCTIONS', '📸 C\'est le moment de choisir vos photos préférées!'),

        // Button text for viewing photos
        'view_photos_button' => env('PHOTOSHOOT_VIEW_PHOTOS_BTN', '🎉 Voir Mes Photos'),

        // Submit button text
        'submit_button' => env('PHOTOSHOOT_SUBMIT_BTN', 'Soumettre mes Sélections'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific features.
    |
    */

    'features' => [
        // Show organization footer
        'show_footer' => env('PHOTOSHOOT_SHOW_FOOTER', true),

        // Enable emojis in UI
        'enable_emojis' => env('PHOTOSHOOT_ENABLE_EMOJIS', true),

        // Show timer on photo selection page
        'show_timer' => env('PHOTOSHOOT_SHOW_TIMER', true),
    ],

];
