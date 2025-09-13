<?php
session_start();

// Database connection details
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "blog";

// Create connection
$conn = new mysqli($host, $user, $pass);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
$conn->select_db($dbname);

// Create tables if not exist
$conn->query("
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
)");

$conn->query("
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Handle logout
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header("Location: blog.php");
    exit;
}

// Handle registration
if (isset($_POST['register'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (username, password) VALUES ('$username', '$password')");
    echo "Registration successful! <a href='blog.php'>Login here</a>";
    exit;
}

// Handle login
if (isset($_POST['login'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE username='$username'");
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header("Location: blog.php");
            exit;
        } else {
            echo "Invalid password.";
            exit;
        }
    } else {
        echo "User not found.";
        exit;
    }
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    if (isset($_GET['register'])) {
        ?>
        <h2>Register</h2>
        <form method="POST">
            Username: <input type="text" name="username" required><br>
            Password: <input type="password" name="password" required><br>
            <input type="submit" name="register" value="Register">
        </form>
        <a href="blog.php">Already have an account? Login</a>
        <?php
    } else {
        ?>
        <h2>Login</h2>
        <form method="POST">
            Username: <input type="text" name="username" required><br>
            Password: <input type="password" name="password" required><br>
            <input type="submit" name="login" value="Login">
        </form>
        <a href="blog.php?register=1">Register here</a>
        <?php
    }
    exit;
}

// Handle creating a post
if (isset($_POST['create'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $conn->query("INSERT INTO posts (title, content) VALUES ('$title', '$content')");
    header("Location: blog.php");
    exit;
}

// Handle editing a post
if (isset($_POST['edit'])) {
    $id = intval($_POST['id']);
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $conn->query("UPDATE posts SET title='$title', content='$content' WHERE id=$id");
    header("Location: blog.php");
    exit;
}

// Handle deleting a post
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM posts WHERE id=$id");
    header("Location: blog.php");
    exit;
}

?>
<h2>Welcome to the Blog</h2>
<a href="blog.php?action=logout">Logout</a> | 
<a href="blog.php">Home</a> | 
<a href="blog.php?create=1">Add New Post</a>
<hr>

<?php
// Show create post form
if (isset($_GET['create'])) {
    ?>
    <h3>Create Post</h3>
    <form method="POST">
        Title: <input type="text" name="title" required><br>
        Content: <textarea name="content" required></textarea><br>
        <input type="submit" name="create" value="Add Post">
    </form>
    <?php
    exit;
}

// Show edit form
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM posts WHERE id=$id");
    if ($result->num_rows > 0) {
        $post = $result->fetch_assoc();
        ?>
        <h3>Edit Post</h3>
        <form method="POST">
            <input type="hidden" name="id" value="<?= $post['id'] ?>">
            Title: <input type="text" name="title" value="<?= htmlspecialchars($post['title']) ?>" required><br>
            Content: <textarea name="content" required><?= htmlspecialchars($post['content']) ?></textarea><br>
            <input type="submit" name="edit" value="Update Post">
        </form>
        <?php
    } else {
        echo "Post not found.";
    }
    exit;
}

// Show all posts
$result = $conn->query("SELECT * FROM posts ORDER BY created_at DESC");
?>
<h3>All Posts</h3>
<table border="1" cellpadding="5">
    <tr>
        <th>Title</th>
        <th>Content</th>
        <th>Created At</th>
        <th>Actions</th>
    </tr>
    <?php while ($post = $result->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($post['title']) ?></td>
        <td><?= htmlspecialchars($post['content']) ?></td>
        <td><?= $post['created_at'] ?></td>
        <td>
            <a href="blog.php?edit=<?= $post['id'] ?>">Edit</a> | 
            <a href="blog.php?delete=<?= $post['id'] ?>" onclick="return confirm('Delete this post?')">Delete</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
