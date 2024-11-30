#!/usr/bin/php
<?php

// Function to display usage
function display_usage() {
    echo "Usage:\n";
    echo "  php sites.php --add <domain> [proxy_pass_url]\n";
    echo "  php sites.php --remove <domain>\n";
    exit(1);
}

// Function to check root privileges
function check_root_privileges() {
    if (posix_getuid() !== 0) {
        echo "This script must be run with sudo/root privileges\n";
        exit(1);
    }
}

// Function to test and reload Nginx
function test_and_reload_nginx() {
    exec('nginx -t', $output, $return_var);
    if ($return_var !== 0) {
        echo "Nginx configuration test failed\n";
        return false;
    }

    exec('systemctl reload nginx', $output, $return_var);
    if ($return_var !== 0) {
        echo "Failed to reload Nginx\n";
        return false;
    }

    return true;
}

// Function to sanitize domain for filename
function sanitize_domain_filename($domain) {
    // Replace dots with underscores and remove any non-alphanumeric characters
    return preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace('.', '_', $domain)) . '.conf';
}

// Check if correct number of arguments are provided
if ($argc < 3) {
    display_usage();
}

// Check root privileges
check_root_privileges();

// Path to Nginx sites-enabled directory
$sites_enabled_path = '/etc/nginx/sites-enabled/';
$default_config_path = '/etc/nginx/sites-available/default';

// Parse arguments
$action = $argv[1];
$domain = $argv[2];

// Sanitize domain for filename
$config_filename = sanitize_domain_filename($domain);

switch ($action) {
    case '--add':
        // Determine proxy_pass (optional)
        $proxy_pass = $argv[3] ?? null;

        // Generate new config path
        $new_config_path = $sites_enabled_path . $config_filename;

        // Read default configuration
        if (!file_exists($default_config_path)) {
            echo "Default Nginx configuration not found\n";
            exit(1);
        }

        $config_content = file_get_contents($default_config_path);

        // Replace server_name
        $config_content = preg_replace('/server_name\s+[^;]+;/', "server_name $domain;", $config_content);

        // Replace proxy_pass if provided
        if ($proxy_pass) {
            // Check if proxy_pass replacement is needed in the default config
            if (strpos($config_content, 'proxy_pass') !== false) {
                $config_content = preg_replace('/proxy_pass\s+[^;]+;/', "proxy_pass $proxy_pass;", $config_content);
            } else {
                // If no existing proxy_pass, add a basic location block
                $proxy_block = "\n    location / {\n        proxy_pass $proxy_pass;\n        proxy_set_header Host \$host;\n        proxy_set_header X-Real-IP \$remote_addr;\n        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;\n        proxy_set_header X-Forwarded-Proto \$scheme;\n    }\n";
                
                // Insert proxy block before the last closing bracket
                $config_content = preg_replace('/}[\s]*$/', $proxy_block . '}', $config_content);
            }
        }

        // Write configuration directly to sites-enabled
        if (file_put_contents($new_config_path, $config_content) === false) {
            echo "Failed to create new configuration file\n";
            exit(1);
        }

        // Test and reload Nginx
        if (test_and_reload_nginx()) {
            echo "Successfully added configuration for $domain (file: $config_filename)\n";
        } else {
            // Cleanup on failure
            unlink($new_config_path);
            exit(1);
        }
        break;

    case '--remove':
        $config_path = $sites_enabled_path . $config_filename;

        // Check if configuration exists
        if (!file_exists($config_path)) {
            echo "Configuration for $domain not found\n";
            exit(1);
        }

        // Remove configuration
        if (unlink($config_path) === false) {
            echo "Failed to remove configuration\n";
            exit(1);
        }

        // Test and reload Nginx
        if (test_and_reload_nginx()) {
            echo "Successfully removed configuration for $domain (file: $config_filename)\n";
            exit(0);
        } else {
            exit(1);
        }
        break;

    default:
        display_usage();
}
