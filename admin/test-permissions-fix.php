<?php
/**
 * Test script to validate Weak File Permissions fixes
 * This script tests the implementation without requiring web authentication
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/security/functions.php';

echo "=== Weak File Permissions Fix Validation ===\n\n";

// Test 1: Check current status
echo "Test 1: Current File Permissions Status\n";
echo str_repeat("-", 50) . "\n";
$status = getFilePermissionsStatus();
foreach ($status as $fileName => $info) {
    echo "File: $fileName\n";
    echo "  Exists: " . ($info['exists'] ? 'YES' : 'NO') . "\n";
    echo "  Permissions: " . $info['permissions'] . "\n";
    echo "  Readable: " . ($info['readable'] ? 'YES' : 'NO') . "\n";
    echo "  Writable: " . ($info['writable'] ? 'YES' : 'NO') . "\n";
    echo "  Expected Current: " . $info['expected_current'] . "\n";
    echo "  Matches Expected: " . ($info['matches_expected'] ? 'YES' : 'NO') . "\n";
    if ($info['error']) {
        echo "  Error: " . $info['error'] . "\n";
    }
    echo "\n";
}

// Test 2: Test Secure Mode
echo "\nTest 2: Testing Secure Mode (disable vulnerability)\n";
echo str_repeat("-", 50) . "\n";
$vulnEnabled = isVulnerabilityEnabled('weak_file_permissions');
echo "Current vulnerability state: " . ($vulnEnabled ? 'ENABLED' : 'DISABLED') . "\n";

if ($vulnEnabled) {
    echo "Disabling vulnerability...\n";
    $result = disableVulnerability('weak_file_permissions');
    echo "Result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
    
    // Check status after disable
    sleep(1);
    $statusAfter = getFilePermissionsStatus();
    echo "\nStatus after disable:\n";
    foreach ($statusAfter as $fileName => $info) {
        echo "  $fileName: " . $info['permissions'] . " (Expected: 0640) - " . ($info['matches_expected'] ? 'PASS' : 'FAIL') . "\n";
    }
} else {
    echo "Vulnerability already disabled. Testing enable...\n";
    $result = enableVulnerability('weak_file_permissions');
    echo "Result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
    
    // Check status after enable
    sleep(1);
    $statusAfter = getFilePermissionsStatus();
    echo "\nStatus after enable:\n";
    foreach ($statusAfter as $fileName => $info) {
        echo "  $fileName: " . $info['permissions'] . " (Expected: 0666) - " . ($info['matches_expected'] ? 'PASS' : 'FAIL') . "\n";
    }
    
    // Now disable again to test secure mode
    echo "\nDisabling again to test secure mode...\n";
    $result = disableVulnerability('weak_file_permissions');
    echo "Result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
    
    sleep(1);
    $statusAfter = getFilePermissionsStatus();
    echo "\nStatus after disable:\n";
    foreach ($statusAfter as $fileName => $info) {
        echo "  $fileName: " . $info['permissions'] . " (Expected: 0640) - " . ($info['matches_expected'] ? 'PASS' : 'FAIL') . "\n";
    }
}

// Test 3: Test Vulnerable Mode
echo "\nTest 3: Testing Vulnerable Mode (enable vulnerability)\n";
echo str_repeat("-", 50) . "\n";
$result = enableVulnerability('weak_file_permissions');
echo "Result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";

sleep(1);
$statusAfter = getFilePermissionsStatus();
echo "\nStatus after enable:\n";
foreach ($statusAfter as $fileName => $info) {
    echo "  $fileName: " . $info['permissions'] . " (Expected: 0666) - " . ($info['matches_expected'] ? 'PASS' : 'FAIL') . "\n";
}

// Test 4: Check for error messages
echo "\nTest 4: Error Message Check\n";
echo str_repeat("-", 50) . "\n";
if (isset($_SESSION['file_permissions_error'])) {
    echo "Error message: " . $_SESSION['file_permissions_error'] . "\n";
} else {
    echo "No error message in session\n";
}

// Test 5: Final status check
echo "\nTest 5: Final Status\n";
echo str_repeat("-", 50) . "\n";
$finalStatus = getFilePermissionsStatus();
$allMatch = true;
foreach ($finalStatus as $fileName => $info) {
    if (!$info['matches_expected']) {
        $allMatch = false;
        echo "FAIL: $fileName does not match expected state\n";
    }
}

if ($allMatch) {
    echo "PASS: All files match their expected security state\n";
} else {
    echo "FAIL: Some files do not match expected state\n";
}

echo "\n=== Validation Complete ===\n";
