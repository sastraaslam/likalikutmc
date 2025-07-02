<?php
// Database configuration - USER NEEDS TO UPDATE THESE VALUES
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'csdemarino';

// Connect to database
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create table if not exists
$create_table_sql = "CREATE TABLE IF NOT EXISTS aploud (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    unggah VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($create_table_sql)) {
    die("Error creating table: " . $conn->error);
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image'])) {
    $target_dir = "uploads/";
    
    // Create uploads directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $target_file = $target_dir . basename($_FILES['image']['name']);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is an actual image
    $check = getimagesize($_FILES['image']['tmp_name']);
    if ($check === false) {
        $error = "File is not an image.";
        $uploadOk = 0;
    }

    // Check file size (5MB max)
    if ($_FILES['image']['size'] > 5000000) {
        $error = "Sorry, your file is too large (max 5MB).";
        $uploadOk = 0;
    }

    // Allow certain file formats
    $allowed_formats = array("jpg", "jpeg", "png", "gif");
    if (!in_array($imageFileType, $allowed_formats)) {
        $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        $error = isset($error) ? $error : "Sorry, your file was not uploaded.";
    } else {
        // Move the uploaded file
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            // Insert into database
            $stmt = $conn->prepare("INSERT INTO aploud (unggah) VALUES (?)");
            $stmt->bind_param("s", $target_file);
            
            if ($stmt->execute()) {
                $success = "The file " . htmlspecialchars(basename($target_file)) . " has been uploaded.";
            } else {
                $error = "Error saving to database: " . $stmt->error;
                unlink($target_file); // Remove the uploaded file if DB insert fails
            }
            
            $stmt->close();
        } else {
            $error = "Sorry, there was an error uploading your file.";
        }
    }
}

// Get all uploaded images from database
$images = array();
$result = $conn->query("SELECT id, unggah, created_at FROM aploud ORDER BY created_at DESC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $images[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Upload System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color:rgb(27, 27, 27);
            color: white;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
        }
        .upload-form {
            border: 1px dashed #ccc;
            padding: 20px;
            margin-bottom: 30px;
            text-align: center;
            background:rgb(41, 41, 41);
            border-radius: 6px;
        }
        .upload-form input[type="file"] {
            display: none;
            margin: 0 auto;
        }
        .upload-form label {
            padding: 12px 20px;
            background: rgba(117, 117, 117, 0.5);
            color: white;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
            display: inline-block;
            margin-bottom: 10px;
        }
        .upload-form label:hover {
            background: #2980b9;
        }
        .upload-form button {
            padding: 12px 24px;
            background: rgba(117, 117, 117, 0.5);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .upload-form button:hover {
            background: #27ae60;
        }
        .image-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .image-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .image-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .image-card img {
            width: 100%;
            height: 90%;
            object-fit: cover;
        }
        .image-info {
            padding: 10px;
            background: white;
        }
        .image-info p {
            margin: 10px 0;
            font-size: 14px;
            color: #666;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .no-images {
            text-align: center;
            color: #666;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Image Upload System</h1>
        
        <!-- Display messages -->
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <!-- Upload Form -->
        <form class="upload-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <h2>Upload New Image</h2>
            <label for="image-upload">Choose Image</label>
            <input type="file" name="image" id="image-upload" required>
            <p id="file-name">No file chosen</p>
            <button type="submit" name="submit">Upload Image</button>
            <button type="submit" name="submit"><a  href="TMC.html" style="text-decoration:none;color:white;">Home</a></button>
        </form>
        
        <!-- Image Gallery -->
        <h2 id="gallery">Uploaded Images</h2>
        <?php if (empty($images)): ?>
            <div class="no-images">
                <p>No images uploaded yet. Upload your first image using the form above.</p>
            </div>
        <?php else: ?>
            <div class="image-gallery">
                <?php foreach ($images as $image): ?>
                    <div class="image-card">
                        <img src="<?php echo htmlspecialchars($image['unggah']); ?>" alt="Uploaded image <?php echo $image['id']; ?>">
                        <div class="image-info">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Display selected filename
        document.getElementById('image-upload').addEventListener('change', function(e) {
            var fileName = e.target.files[0] ? e.target.files[0].name : 'No file chosen';
            document.getElementById('file-name').textContent = fileName;
        });
    </script>
</body>
</html>

