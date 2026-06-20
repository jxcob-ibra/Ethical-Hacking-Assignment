#!/bin/sh
set -e

# Check if exposed_database access is allowed
ACCESS_CONTROL_FILE="/var/www/html/storage/phpmyadmin_access.php"

if [ -f "$ACCESS_CONTROL_FILE" ]; then
    # Extract the value of $exposed_database_enabled from the PHP file
    EXPOSED_ENABLED=$(grep -oP 'exposed_database_enabled\s*=\s*\K\w+' "$ACCESS_CONTROL_FILE" 2>/dev/null || echo "false")
    
    if [ "$EXPOSED_ENABLED" = "false" ]; then
        # Access denied - display error page
        cat > /var/www/html/html/index.php << 'EOF'
<?php
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - phpMyAdmin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f5f5f5;
        }
        .error-container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
        }
        h1 {
            color: #d32f2f;
            margin-top: 0;
        }
        .error-code {
            font-size: 72px;
            color: #d32f2f;
            margin: 20px 0;
        }
        .message {
            color: #666;
            line-height: 1.6;
        }
        .note {
            margin-top: 20px;
            padding: 15px;
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            color: #856404;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>Access Denied</h1>
        <div class="error-code">403</div>
        <div class="message">
            <p>phpMyAdmin access is currently disabled.</p>
            <p>The database exposure vulnerability has been disabled in Secure Mode.</p>
        </div>
        <div class="note">
            <strong>Note:</strong> This is a security feature. To enable phpMyAdmin access, 
            an administrator must enable the "Exposed Database" vulnerability in the Security Settings panel.
        </div>
    </div>
</body>
</html>
EOF
        # Start Apache with the error page
        exec apache2-foreground
    fi
fi

# Access allowed - run the original phpMyAdmin entrypoint
exec /docker-entrypoint.sh "$@"
