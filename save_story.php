<?php
// save_story.php : Secure Data Ingestion Pipeline

require_once '../includes/db.php';

// Verify HTTP protocol and submit intent to prevent direct script execution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    
    // 1. Input Sanitization Phase
    // Strip trailing whitespace from string payloads
    $title = trim($_POST['title']);
    $location = trim($_POST['location']);
    $category = trim($_POST['category']);
    $excerpt = trim($_POST['excerpt']);
    $stats = trim($_POST['stats']);

    // 2. Environment Variable Configuration
    $relative_upload_dir = 'assets/uploads/';
    $absolute_upload_dir = '../' . $relative_upload_dir;
    $max_size = 2 * 1024 * 1024; // 2MB constraint parameter [28]
    
    // Directory provisioning: Ensure storage destination exists
    if (!is_dir($absolute_upload_dir)) {
        mkdir($absolute_upload_dir, 0755, true);
    }

    // 3. Payload Integrity Verification
    if (!isset($_FILES['image']['error']) || is_array($_FILES['image']['error'])) {
        die("Security Exception: Invalid or malformed file upload parameters.");
    }

    // Interrogate internal PHP transmission matrices
    switch ($_FILES['image']['error']) {
        case UPLOAD_ERR_OK: 
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE: 
            die("Policy Violation: Exceeded server filesize constraints.");
        default: 
            die("System Exception: Unknown upload transmission error.");
    }

    // 4. Resource Exhaustion Defense (DoS Mitigation) [28]
    if ($_FILES['image']['size'] > $max_size) {
        die("Policy Violation: Binary payload exceeds the 2MB allocation limit.");
    }

    // 5. Cryptographic File Signature Verification (MIME Analysis) [27]
    // Reads binary magic bytes, neutralizing dual-extension obfuscation attacks
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($_FILES['image']['tmp_name']);
    
    // Strict MIME whitelist dictionary
    $allowed_mimes = [
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp'
    ];

    // Correlate detected binary signature against the whitelist
    $ext = array_search($mime_type, $allowed_mimes, true);
    if ($ext === false) {
        die("Security Exception: Invalid file architecture. Only JPG, PNG, and WebP are permitted.");
    }

    // 6. Cryptographic Obfuscation and Path Mapping [28]
    // Utilizes microsecond entropy to guarantee file collision avoidance
    $new_filename = uniqid('cw_node_', true). '.'. $ext;
    $file_path = $absolute_upload_dir . $new_filename;
    $db_path = $relative_upload_dir . $new_filename;

    // 7. Kernel-Level File Relocation
    // Transfers binary from temporary temp/ directory to the persistent asset volume
    if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
        
        // 8. SQL Injection Defense: Prepared Statement Compilation 
        $stmt = $conn->prepare("INSERT INTO stories (title, location, category, excerpt, stats, image_path) VALUES (?,?,?,?,?,?)");
        
        // Parameter Binding ("ssssss" indicates 6 string variables expected)
        $stmt->bind_param("ssssss", $title, $location, $category, $excerpt, $stats, $db_path);
        
        // Execute Transaction
        if ($stmt->execute()) {
            // Transaction Success: Issue 302 Redirect back to presentation controller 
            header("Location: ../index.php");
            exit();
        } else {
            error_log("Database Execution Failure: ". $stmt->error);
            echo "A critical error occurred while committing to the persistence layer.";
        }
        
        // Graceful termination of statement object
        $stmt->close();
    } else {
        echo "System Exception: File system rejected the relocation of the binary payload.";
    }
} else {
    // Punitive routing for unauthorized direct GET access to the ingestion script
    header("Location: ../index.php");
    exit();
}
?>