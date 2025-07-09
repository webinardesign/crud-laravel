<?php include 'config.php'; ?>

<?php
// Insert
if (isset($_POST['submit'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $conn->query("INSERT INTO users (name, email) VALUES ('$name', '$email')");
    header("Location: index.php");
    exit;
}

// Update
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $conn->query("UPDATE users SET name='$name', email='$email' WHERE id=$id");
    header("Location: index.php");
    exit;
}

// Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM users WHERE id=$id");
    header("Location: index.php");
    exit;
}

// Edit
$edit = false;
$name = "";
$email = "";
$id = 0;

if (isset($_GET['edit'])) {
    $edit = true;
    $id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM users WHERE id=$id");
    $row = $result->fetch_assoc();
    $name = $row['name'];
    $email = $row['email'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Simple CRUD in Core PHP</title>
</head>
<body>
    <h2><?php echo $edit ? "Edit User" : "Add User"; ?></h2>
    <form method="POST">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <label>Name:</label><br>
        <input type="text" name="name" value="<?php echo $name; ?>" required><br><br>
        <label>Email:</label><br>
        <input type="email" name="email" value="<?php echo $email; ?>" required><br><br>
        <input type="submit" name="<?php echo $edit ? "update" : "submit"; ?>" value="<?php echo $edit ? "Update" : "Save"; ?>">
    </form>

    <h2>Users List</h2>
    <table border="1" cellpadding="10">
        <tr>
            <th>ID</th><th>Name</th><th>Email</th><th>Actions</th>
        </tr>
        <?php
        $result = $conn->query("SELECT * FROM users ORDER BY id DESC");
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                <td>".$row['id']."</td>
                <td>".$row['name']."</td>
                <td>".$row['email']."</td>
                <td>
                    <a href='index.php?edit=".$row['id']."'>Edit</a> |
                    <a href='index.php?delete=".$row['id']."' onclick=\"return confirm('Are you sure?')\">Delete</a>
                </td>
            </tr>";
        }
        ?>
    </table>
</body>
</html>
