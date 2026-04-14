<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, profile_icon, is_admin FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['profile_icon'] = $user['profile_icon'];
                $_SESSION['is_admin'] = $user['is_admin'];
                header("Location: index.php");
                exit();
            } else {
                $error = "Invalid credentials.";
            }
        } else {
            $error = "Invalid credentials.";
        }
        $stmt->close();
    }
}
?>

<main class="container" style="max-width: 500px; margin-top: 5rem;">
    <div class="glass-card" style="padding: 2.5rem;">
        <h2 style="font-size: 2rem; margin-bottom: 2rem; text-align: center;">Welcome Back</h2>

        <?php if ($error): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="username" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Username</label>
                <input type="text" id="username" name="username" class="form-control" required
                    style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid #d6d3d1;">
            </div>

            <div class="form-group" style="margin-bottom: 2rem;">
                <label for="password" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Password</label>
                <input type="password" id="password" name="password" class="form-control" required
                    style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid #d6d3d1;">
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; justify-content: center;">Login</button>
        </form>

        <p style="text-align: center; margin-top: 1.5rem; color: var(--stone-muted);">
            Don't have an account? <a href="register.php"
                style="color: var(--green-primary); font-weight: 600;">Register here</a>.
        </p>
    </div>
</main>

<script>
    lucide.createIcons();
</script>
<?php require_once 'includes/footer.php'; ?>