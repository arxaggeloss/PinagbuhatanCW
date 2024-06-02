<?php
session_start();

// Initialize variables to avoid "undefined" notices
$username = $address = $birthday = $age = $gender = "";
$notifications = [];

// Check if the user is logged in
if (isset($_SESSION['loggedin_user_id'])) {
    // Database connection parameters
    $servername = "pinagbuhatancw.mysql.database.azure.com";
    $username_db = "pinagbuhatancw";
    $password_db = 'pa$$word1';
    $database = "tandaandb";

    // Create a connection to the database
    $conn = new mysqli($servername, $username_db, $password_db, $database);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Fetch user information based on the logged-in user's ID
    $stmt = $conn->prepare("SELECT * FROM user WHERE userid = ?");
    $stmt->bind_param("i", $_SESSION['loggedin_user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        die("Error executing the query: " . $conn->error);
    }

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $username = $username ?: $row['inputname'];
        $address = $address ?: $row['address'];
        $birthday = $birthday ?: $row['birthday'];
        $age = $age ?: $row['age'];
        $gender = $gender ?: $row['gender'];
        $email = $row['email']; // Store email to fetch notifications later
    } else {
        echo "User not found!<br>";
    }

    // Fetch notifications for the logged-in user
    $stmt_notifications = $conn->prepare("
        SELECT type, message, created_at
        FROM notifications
        WHERE email = ? AND (type = 'medical_assistance' OR type = 'helpdesk')
        ORDER BY created_at DESC
    ");
    $stmt_notifications->bind_param("s", $email);
    $stmt_notifications->execute();
    $result_notifications = $stmt_notifications->get_result();

    while ($notification = $result_notifications->fetch_assoc()) {
        $notifications[] = $notification;
    }

    $stmt->close();
    $stmt_notifications->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <style>
        /* Header styling */
        .header {
            display: flex; /* Use flexbox */
            align-items: center; /* Align items vertically */
            background-color: #252D6F;
            color: #fff;
            padding: 20px 20px;
            position: relative;
        }

        .header .icon {
            color: #fff;
            font-size: 24px;
            margin-right: -49px; /* Adjust negative margin */
            position: relative; /* Set position to relative */
            z-index: 1; /* Ensure logo is above the title */
        }

        .header .title {
            display: flex;
            flex-direction: column;
            justify-content: center; /* Center vertically */
            margin-left: 17px; /* Adjust the margin */
            background-color: #9eacb4; /* Light blue background */
            color: #FFB802; /* Orange text color */
            padding: 10px;
            border-radius: 15px;
            border: 2px solid orangered; /* Orange-red border */
            position: relative;
            z-index: 0; /* Ensure title is below the logo */
        }

        .header .title h2 {
            margin-left: 20px;
            font-size: 47px;
            font-weight: bold;
        }

        .header .title p {
            margin-left: 20px;
            font-size: 27px;
        }

        .header .buttons-container {
            display: flex;
            margin-left: auto; /* Push buttons to the right */
            padding-right: 0px; /* Add some padding on the right */
            background-color: #e0f2f1; /* Light blue background */
            border-radius: 5px;
            border: 3px solid white; /* Orange-red border */
        }

        .header .buttons {
            display: flex;
            gap: 0; /* Remove the gap between buttons */
        }

        .header .buttons button {
            background-color: orange;
            color: white;
            border: none;
            border-radius: 2px; /* Rounder corners */
            padding: 20px 20px; /* Increased padding */
            cursor: pointer;
            font-size: 16px; /* Increased font size */
            font-weight: bold;
            display: flex; /* Use flexbox */
            flex-direction: column; /* Arrange icon and text vertically */
            align-items: center; /* Center items horizontally */
        }

        .header .buttons button:last-child {
            margin-right: 0; /* Remove margin from last button */
        }

        .header .buttons button img {
            width: 30px; /* Increased icon size */
            height: auto;
            margin-bottom: 5px; /* Add margin between icon and text */
        }

        /* CSS styles for the user profile */
        body {
            font-family: "Arial", sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
            color: #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .user-profile {
            margin-left: 150px;
            margin-top: 100px;
            padding-top: 0px;
            width: 80%;
            background-color: #fff; /* White background */
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-gap: 0;
            padding-bottom: 0px;
        }

        .profile-left {
            padding-top: 0px;
            margin-left: 0px;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px;
            text-align: center;
        }

        .profile-left::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background: linear-gradient(to bottom, #000, rgba(0, 0, 0, 0));
            opacity: 0.5;
        }

        .profile-left img {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 20px;
        }

        .profile-left h2 {
            font-size: 48px;
            color: #FFB802;
            position: relative;
            z-index: 1;
        }

        .profile-right {
            background-color: #FFB802; /* Orange background */
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .user-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
            width: 100%;
            max-width: 500px;
        }

        .user-form input,
        .user-form select {
            padding: 10px;
            font-size: 18px;
            border-radius: 5px;
            border: 2px solid #333;
        }

        .user-form input[type="submit"] {
            background-color: #333;
            color: #fff;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .user-form input[type="submit"]:hover {
            background-color: #555;
        }

        .upload-btn {
            background-color: #3498db;
            color: #fff;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 18px;
        }

        .upload-btn:hover {
            background-color: #2980b9;
        }

        /* Media query for responsiveness */
        @media (max-width: 768px) {
            .user-profile {
                width: 90%;
                grid-template-columns: 1fr;
            }

            .profile-left,
            .profile-right {
                padding: 40px;
                text-align: center;
            }

            .profile-left img {
                width: 150px;
                height: 150px;
            }

            .profile-left h2 {
                font-size: 36px;
            }
        }

        .logo {
            position: absolute;
            top: 20px;
            left: 20px;
            bottom: 20px;
            right: 20px;
            cursor: pointer;
        }

        /* Style for the login button and link */
        .login-btn {
            padding: 15px 30px;
            background-color: #FF6B6B; /* Red button background */
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 1px;
            text-decoration: none;
            display: inline-block;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .login-btn:hover {
            background-color: #FF8E8E; /* Lighter red on hover */
        }

        .notification-tray {
            position: fixed;
            top: 80px;
            right: 20px;
            width: 300px;
            max-height: 400px;
            overflow-y: auto;
            background-color: #fff;
            border: 1px solid #ccc;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            padding: 10px;
        }

        .notification-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-type {
            font-weight: bold;
        }

        .notification-message {
            margin: 5px 0;
        }

        .notification-timestamp {
            font-size: 0.85em;
            color: #888;
        }
    </style>
</head>
<body>
<div class="header">
    <div class="icon">
        <img src="images/Pasig.png" alt="Icon" style="width: 100px; height: auto;">
    </div>
    <div class="title">
        <h2>Barangay Pinagbuhatan</h2>
        <p>Community Website</p>
    </div>
    <div class="buttons-container">
        <div class="buttons">
            <button class="news-button" onclick="goToNewsPage()"><img src="images/index.png"> News & Updates</button>
            <button class="medical-assistance-button" onclick="goToMedicalAssistancePage()"><img src="images/medical.png"> Medical Assistance</button>
            <button class="helpdesk-button" onclick="goToHelpdeskPage()"><img src="images/helpdesk.png"> Helpdesk</button>
            <button class="profile-button" onclick="goToProfilePage()"><img src="images/user.png"> User profile</button>
        </div>
    </div>
</div>
<div class="user-profile">
    <div class="profile-left">
        <h2>User Profile</h2>
        <?php
        if (isset($row['profile_image']) && !empty($row['profile_image'])) {
            echo '<img src="' . htmlspecialchars($row['profile_image']) . '" alt="Profile Picture">';
        } else {
            echo '<img src="default_profile.jpg" alt="Profile Picture">';
        }
        ?>
    </div>

    <div class="profile-right">
        <form class="user-form" action="update_profile.php" method="post" enctype="multipart/form-data">
            <input type="text" name="username" placeholder="Username" value="<?php echo htmlspecialchars($username); ?>">
            <input type="text" name="address" placeholder="Address" value="<?php echo htmlspecialchars($address); ?>">
            <input type="date" name="birthday" placeholder="Birthday" value="<?php echo htmlspecialchars($birthday); ?>">
            <input type="number" name="age" placeholder="Age" value="<?php echo htmlspecialchars($age); ?>">
            <select name="gender">
                <option value="" disabled>Select Gender</option>
                <option value="male" <?php if($gender === 'male') echo 'selected'; ?>>Male</option>
                <option value="female" <?php if($gender === 'female') echo 'selected'; ?>>Female</option>
                <option value="other" <?php if($gender === 'other') echo 'selected'; ?>>Other</option>
            </select>
            <input type="file" name="fileToUpload" id="fileToUpload">
            <input type="submit" value="Update Profile" name="submit" class="upload-btn">
        </form>

        <!-- Display user details -->
        <div class="user-details">
            <?php if (isset($username)) : ?>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($address); ?></p>
                <p><strong>Birthday:</strong> <?php echo htmlspecialchars($birthday); ?></p>
                <p><strong>Age:</strong> <?php echo htmlspecialchars($age); ?></p>
                <p><strong>Gender:</strong> <?php echo htmlspecialchars($gender); ?></p>
            <?php else : ?>
                <p>User data not available.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<div class="login-section" style="text-align: center; margin-top: 860px;">
    <p><a href="login.php" class="login-btn">Sign Out</a> Go Back to Login</p>
</div>
<div class="notification-tray">
    <?php foreach ($notifications as $notification) : ?>
        <div class="notification-item">
            <div class="notification-type"><?php echo htmlspecialchars($notification['type']); ?></div>
            <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
            <div class="notification-timestamp"><?php echo htmlspecialchars($notification['created_at']); ?></div>
        </div>
    <?php endforeach; ?>
</div>
<script>
    function goToNewsPage() {
        window.location.href = "index.html";
    }

    function goToMedicalAssistancePage() {
        window.location.href = "medicalassistance.html";
    }

    function goToHelpdeskPage() {
        window.location.href = "helpdesk.html";
    }

    function goToProfilePage() {
        window.location.href = "user_profile.php";
    }
</script>
</body>
</html>
