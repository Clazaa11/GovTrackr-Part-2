<?php
// !! To be only used for setting up admin password in DB !!
function generatePasswordHash($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Hash Generator - GovTrackr</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #500027;
            border-bottom: 3px solid #500027;
            padding-bottom: 10px;
        }
        .hash-item {
            background: #f9f9f9;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #500027;
            border-radius: 5px;
        }
        .password {
            font-weight: bold;
            color: #500027;
            margin-bottom: 5px;
        }
        .hash {
            font-family: monospace;
            font-size: 12px;
            color: #333;
            word-break: break-all;
            background: white;
            padding: 10px;
            border-radius: 3px;
            border: 1px solid #ddd;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        button {
            background: #500027;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background: #6d0035;
        }
        .note {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Hash Generator (For Admin Password Only)</h1>

        <div class="form-section">
            <h2>Generate Custom Hash:</h2>
            <form method="POST" action="">
                <input 
                    type="text" 
                    name="custom_password" 
                    placeholder="Enter password to hash"
                    required
                >
                <button type="submit" name="generate">Generate Hash</button>
            </form>

            <?php
            if (isset($_POST['generate']) && !empty($_POST['custom_password'])) {
                $customPassword = $_POST['custom_password'];
                $customHash = generatePasswordHash($customPassword);
                echo '<div class="hash-item" style="margin-top: 20px;">';
                echo '<div class="password">Your Password: ' . htmlspecialchars($customPassword) . '</div>';
                echo '<div class="hash">' . htmlspecialchars($customHash) . '</div>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</body>
</html>
