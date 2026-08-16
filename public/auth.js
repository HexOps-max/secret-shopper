// Signup Handler
function handleSignup(e) {
    e.preventDefault();
    const email = document.getElementById('signup-email').value;
    const password = document.getElementById('signup-password').value;
    const fullName = document.getElementById('signup-name')?.value || "User";

    auth.createUserWithEmailAndPassword(email, password)
        .then((userCredential) => {
            const user = userCredential.user;
            
            // Save extra user profile data & assignment bucket to Firestore
            return db.collection("users").doc(user.uid).set({
                uid: user.uid,
                name: fullName,
                email: email,
                assignedTask: "Pending Assignment",
                createdAt: firebase.firestore.FieldValue.serverTimestamp()
            }).then(() => {
                notifyTelegram(`🚀 *New User Signup!*\nName: ${fullName}\nEmail: ${email}`);
                window.location.href = "dashboard.html";
            });
        })
        .catch((error) => {
            alert("Signup Error: " + error.message);
        });
}

// Login Handler
function handleLogin(e) {
    e.preventDefault();
    const email = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;

    auth.signInWithEmailAndPassword(email, password)
        .then((userCredential) => {
            notifyTelegram(`🔐 *User Logged In:* ${email}`);
            window.location.href = "dashboard.html";
        })
        .catch((error) => {
            alert("Login Error: " + error.message);
        });
}