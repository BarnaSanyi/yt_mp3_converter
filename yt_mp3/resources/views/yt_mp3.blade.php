<!-- index.php -->
<?php

session_start();

function convertYoutubeToMp3($youtubeUrl) {
    // Validate YouTube URL
    if (!preg_match('/^(https?\:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.+$/', $youtubeUrl)) {
        return "Invalid YouTube URL";
    }

    try {
        $safeUrl = escapeshellarg($youtubeUrl);



        // Define the output directory in vps
        // sudo mkdir -p /var/www/html/downloads
        // sudo chown www-data:www-data /var/www/html/downloads
        // sudo chmod 755 /var/www/html/downloads



        // // Define the output directory within the web root
        // $outputDir = $_SERVER['DOCUMENT_ROOT'] . '/downloads/';
    
        // // Create the directory if it doesn't exist
        // if (!file_exists($outputDir)) {
        //     mkdir($outputDir, 0755, true); // 0755 permissions for web server access
        // }

        $outputDir = '~/repos/yt_mp3_converter/yt_mp3/public/downloads/';

        // Ensure the output directory exists
        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        // Define the output template with sanitization
        $outputTemplate = $outputDir . '%(title)s';
        $outputTemplate = escapeshellarg($outputTemplate);

        // -x -> extract audio
        // --audio-format mp3 -> to convert to mp3
        // -o -> output file
        // --restrict-filenames -> to restrict filenames to ASCII characters only
        $command = "~/repos/yt_mp3_converter/venv/bin/yt-dlp -x --audio-format mp3 -o " . $outputTemplate . " --restrict-filenames $safeUrl 2>&1";

        // Execute the command and capture output
        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            // Parse the output to find the exact file path
            $filePath = null;
            foreach ($output as $line) {
                if (strpos($line, '[ExtractAudio] Destination: ') === 0) {
                    $filePath = substr($line, strlen('[ExtractAudio] Destination: '));
                    break;
                }
            }

            // Check if the file was found and exists
            if ($filePath && file_exists($filePath)) {
                $_SESSION['file'] = $filePath;
                return [
                    'success' => true,
                    'file' => $filePath,
                    'message' => 'Conversion successful! Click below to download your MP3.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Could not find the converted file.'
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'Conversion failed: ' . implode("\n", $output)
            ];
        }

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

function downloadFile() {
    $file = $_SESSION['file']; // Replace with the actual file path

    // Check if the file exists
    if (file_exists($file)) {
        // Set headers to indicate a file download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        
        // Clear any output buffering
        ob_clean();
        flush();
        
        // Read the file and output it to the client
        readfile($file);
        // Delete the file after download
        unlink($file);
        exit;
    } else {
        // If the file doesn't exist, show an error message
        echo "File not found!";
    }
}

if(isset($_GET['download'])) {
    downloadFile();
}

$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['youtube_url'])) {
    $result = convertYoutubeToMp3($_GET['youtube_url']);
    // if ($result['success']) {
    //     // Convert file system path to URL path
    //     $filePath = $result['file'];
    //     $fileUrl = str_replace($_SERVER['DOCUMENT_ROOT'], '', $filePath);
    //     $result['fileUrl'] = $fileUrl; // Add URL to the result array
    // }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTube to MP3 Converter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #1a1a1a;
            color: #ffffff;
        }
        .converter-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #2d2d2d;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-bottom: 1px solid #404040;
        }
        .form-control {
            background-color: #2d2d2d;
            border-color: #404040;
            color: #ffffff;
        }
        .form-control:focus {
            background-color: #2d2d2d;
            border-color: #dc3545;
            color: #ffffff;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
        }
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #bb2d3b;
            border-color: #b02a37;
        }
        .alert-success {
            background-color: #2d6a4f;
            border-color: #40916c;
            color: #ffffff;
        }
        .alert-danger {
            background-color: #641220;
            border-color: #dc3545;
            color: #ffffff;
        }
        .btn-success {
            background-color: #2d6a4f;
            border-color: #40916c;
        }
        .btn-success:hover {
            background-color: #245c3f;
            border-color: #347d5a;
        }
        footer {
            background-color: #2d2d2d;
            border-top: 1px solid #404040;
        }

        .bold {
            font-weight: bold;
        }

        #youtube_url::placeholder {
            color: gray;
        }
    </style>
</head>
<body>
    <header class="header text-center">
        <h1 class="display-3 bold">YouTube to MP3 Converter</h1>
        <p class="lead bold">Convert your favorite YouTube videos to MP3 format</p>
    </header>

    <div class="converter-container">
        <form method="get" class="mb-4">
            <div class="mb-3">
                <label for="youtube_url" class="form-label">YouTube URL</label>
                <input type="text" 
                       class="form-control" 
                       id="youtube_url" 
                       name="youtube_url" 
                       placeholder="https://www.youtube.com/watch?v=..." 
                       required>
            </div>
            <button type="submit" class="btn btn-danger w-100 bold">Convert to MP3</button>
        </form>

        <?php if ($result): ?>
            <div class="alert <?php echo $result['success'] ? 'alert-success' : 'alert-danger'; ?>" role="alert">
                <?php echo $result['message']; ?>
                <?php if ($result['success']): ?> 
                    <form action="" method="get" class="mt-2 d-flex justify-content-center">
                        <button type="submit" name="download" class="btn btn-success w-100 bold" >Download MP3</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>