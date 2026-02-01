<?php

/**
 * Cache cleanup utility for SPCast Analytics
 * Removes old cache files to prevent disk space issues
 */

function cleanupCacheFiles($cacheDir = 'cache/', $maxAgeHours = 24)
{
    try {
        if (!is_dir($cacheDir)) {
            return;
        }
        
        $files = glob($cacheDir . '*.json');
        $currentTime = time();
        $maxAgeSeconds = $maxAgeHours * 3600;
        $deletedCount = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $fileAge = $currentTime - filemtime($file);
                if ($fileAge > $maxAgeSeconds) {
                    if (unlink($file)) {
                        $deletedCount++;
                        error_log("Deleted old cache file: " . basename($file));
                    } else {
                        error_log("Failed to delete cache file: " . basename($file));
                    }
                }
            }
        }
        
        if ($deletedCount > 0) {
            error_log("Cache cleanup completed: Deleted $deletedCount old files");
        }
    } catch (Exception $e) {
        error_log('Cache cleanup error: ' . $e->getMessage());
    }
}

// Run cleanup with some probability to avoid performance impact
if (rand(1, 100) <= 5) { // 5% chance
    cleanupCacheFiles();
}

?>
